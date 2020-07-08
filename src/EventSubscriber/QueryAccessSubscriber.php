<?php

namespace Drupal\group\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface as CGPII;
use Drupal\group\Access\ChainGroupPermissionCalculatorInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new QueryAccessSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   * @param \Drupal\group\Access\ChainGroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupContentEnablerManagerInterface $plugin_manager, ChainGroupPermissionCalculatorInterface $permission_calculator, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->permissionCalculator = $permission_calculator;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return ['entity.query_access' => 'onQueryAccess'];
  }

  /**
   * Modifies the access conditions for cart orders.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The event.
   */
  public function onQueryAccess(QueryAccessEvent $event) {
    $entity_type_id = $event->getEntityTypeId();
    $conditions = $event->getConditions();
    $operation = $event->getOperation();
    $account = $event->getAccount();

    // Find all of the group content plugins that define access.
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess($entity_type_id);
    if (empty($plugin_ids)) {
      return;
    }

    // Find all of the group content types that define access.
    $group_content_type_ids = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->getQuery()
      ->condition('content_plugin', $plugin_ids)
      ->execute();

    // If any new group content entity is added using any of the retrieved
    // plugins, it might change access.
    $cache_tags = [];
    foreach ($plugin_ids as $plugin_id) {
      $cache_tags[] = "group_content_list:plugin:$plugin_id";
    }
    $conditions->addCacheTags($cache_tags);

    if (empty($group_content_type_ids)) {
      // Because we add cache tags checking for new group content above, we can
      // simply bail out here without adding any group content type related
      // cache tags because a new group content type does not change the
      // permissions until a group content is created using said group content
      // type, at which point the cache tags above kick in.
      return;
    }

    // Find all grouped entity IDs using the plugins retrieved above.
    $grouped_entity_ids = $this->database
      ->select('group_content_field_data', 'gc')
      ->fields('gc', ['entity_id'])
      ->condition('type', $group_content_type_ids, 'IN')
      ->execute()
      ->fetchCol();

    if (empty($grouped_entity_ids)) {
      return;
    }

    // @todo Remove these lines once we kill the bypass permission.
    // If the account can bypass group access, we do not alter the query at all.
    $conditions->addCacheContexts(['user.permissions']);
    if ($account->hasPermission('bypass group access')) {
      return;
    }

    $conditions->addCacheContexts(['user.group_permissions']);
    $calculated_permissions = $this->permissionCalculator->calculatePermissions($account);

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $check_published = $entity_type->entityClassImplements(EntityPublishedInterface::class);
    $id_key = $entity_type->getKey('id');
    $owner_key = $entity_type->getKey('owner');
    $published_key = $entity_type->getKey('published');

    $plugin_id_map = $this->pluginManager->getPluginGroupContentTypeMap();

    $allowed_any_ids = $allowed_own_ids = $allowed_any_by_status_ids = $allowed_own_by_status_ids = $all_ids = [];
    foreach ($plugin_ids as $plugin_id) {
      if (!isset($plugin_id_map[$plugin_id])) {
        continue;
      }
      foreach ($plugin_id_map[$plugin_id] as $group_content_type_id) {
        // For backwards compatibility reasons, if the group content enabler
        // plugin used by the group content type does not specify a permission
        // provider, we do not alter the query for that group content type. In
        // 8.2.x all group content types will get a permission handler by
        // default, so this check can be safely removed then.
        if (!$this->pluginManager->hasHandler($plugin_id, 'permission_provider')) {
          continue;
        }
        $handler = $this->pluginManager->getPermissionProvider($plugin_id);
        $admin_permission = $handler->getAdminPermission();
        $any_permission = $handler->getPermission($operation, 'entity', 'any');
        $own_permission = $handler->getPermission($operation, 'entity', 'own');
        if ($check_published) {
          $any_unpublished_permission = $handler->getPermission("$operation unpublished", 'entity', 'any');
          $own_unpublished_permission = $handler->getPermission("$operation unpublished", 'entity', 'own');
        }

        foreach ($calculated_permissions->getItems() as $item) {
          $all_ids[$item->getScope()][] = $item->getIdentifier();

          // For groups, we need to get the group ID, but for group types, we need
          // to use the group content type ID rather than the group type ID.
          $identifier = $item->getScope() === CGPII::SCOPE_GROUP
            ? $item->getIdentifier()
            : $group_content_type_id;

          if ($admin_permission !== FALSE && $item->hasPermission($admin_permission)) {
            $allowed_any_ids[$item->getScope()][] = $identifier;
          }
          elseif(!$check_published) {
            if ($any_permission !== FALSE && $item->hasPermission($any_permission)) {
              $allowed_any_ids[$item->getScope()][] = $identifier;
            }
            elseif($own_permission !== FALSE && $item->hasPermission($own_permission)) {
              $allowed_own_ids[$item->getScope()][] = $identifier;
            }
          }
          else {
            if ($any_permission !== FALSE && $item->hasPermission($any_permission)) {
              $allowed_any_by_status_ids[$item->getScope()][1][] = $identifier;
            }
            elseif($own_permission !== FALSE && $item->hasPermission($own_permission)) {
              $allowed_own_by_status_ids[$item->getScope()][1][] = $identifier;
            }
            if ($any_unpublished_permission !== FALSE && $item->hasPermission($any_unpublished_permission)) {
              $allowed_any_by_status_ids[$item->getScope()][0][] = $identifier;
            }
            elseif($own_unpublished_permission !== FALSE && $item->hasPermission($own_unpublished_permission)) {
              $allowed_own_by_status_ids[$item->getScope()][0][] = $identifier;
            }
          }
        }
      }
    }

    // If no group type or group gave access, we deny access altogether.
    if (empty($allowed_any_ids) && empty($allowed_own_ids) && empty($allowed_any_by_status_ids) && empty($allowed_own_by_status_ids)) {
      $conditions->addCondition($id_key, $grouped_entity_ids, 'NOT IN');
      return;
    }

    // We might see multiple values in the $all_ids variable because we looped
    // over all calculated permissions multiple times.
    if (!empty($all_ids[CGPII::SCOPE_GROUP])) {
      $all_ids[CGPII::SCOPE_GROUP] = array_unique($all_ids[CGPII::SCOPE_GROUP]);
    }

    // Find all of the grouped entity IDs the user has access to. These are
    // either entities granted access by the admin permission or those the user
    // can access as a non-owner if the entity does not support publishing.
    $any_query_modified = FALSE;
    $any_query = $this->database
      ->select('group_content_field_data', 'gc')
      ->fields('gc', ['entity_id']);

    // Add the allowed group types to the query (if any).
    if (!empty($allowed_any_ids[CGPII::SCOPE_GROUP_TYPE])) {
      $sub_condition = $any_query->andConditionGroup();
      $sub_condition->condition('type', $allowed_any_ids[CGPII::SCOPE_GROUP_TYPE], 'IN');

      // If the user had memberships, we need to make sure they are excluded
      // from group type based matches as the memberships' permissions take
      // precedence.
      if (!empty($all_ids[CGPII::SCOPE_GROUP])) {
        $sub_condition->condition('gid', $all_ids[CGPII::SCOPE_GROUP], 'NOT IN');
      }

      $any_query->condition($sub_condition);
      $any_query_modified = TRUE;
    }

    // Add the memberships with access to the query (if any).
    if (!empty($allowed_any_ids[CGPII::SCOPE_GROUP])) {
      $any_query->condition('gid', $allowed_any_ids[CGPII::SCOPE_GROUP], 'IN');
      $any_query_modified = TRUE;
    }

    // In order to define query access for grouped entities and at the same time
    // leave the ungrouped alone, we need allow access to all entities that:
    // - Do not belong to a group.
    // - Belong to a group and to which:
    //   - The user has any access.
    //   - The user has owner access and is the owner of.
    //
    // In case the entity supports publishing, the last condition is swapped out
    // for the following two:
    // - The entity is published and:
    //   - The user has any access.
    //   - The user has owner access and is the owner of.
    // - The entity is unpublished and:
    //   - The user has any access.
    //   - The user has owner access and is the owner of.
    //
    // In any case, the first two conditions are always the same, so let's add
    // those already.
    $condition_group = new ConditionGroup('OR');
    $condition_group->addCondition($id_key, $grouped_entity_ids, 'NOT IN');
    if ($any_query_modified && $any_ids = $any_query->execute()->fetchCol()) {
      $condition_group->addCondition($id_key, $any_ids);
    }

    // From this point we need to either find the entities the user can access
    // as the owner or the entities accessible as both the owner and non-owner
    // when the entity supports publishing.
    if (!$check_published) {
      $own_query_modified = FALSE;
      $own_query = $this->database
        ->select('group_content_field_data', 'gc')
        ->fields('gc', ['entity_id']);

      // Add the allowed owner group types to the query (if any).
      if (!empty($allowed_own_ids[CGPII::SCOPE_GROUP_TYPE])) {
        $sub_condition = $own_query->andConditionGroup();
        $sub_condition->condition('type', $allowed_own_ids[CGPII::SCOPE_GROUP_TYPE], 'IN');

        // If the user had memberships, we need to make sure they are excluded
        // from group type based matches as the memberships' permissions take
        // precedence.
        if (!empty($all_ids[CGPII::SCOPE_GROUP])) {
          $sub_condition->condition('gid', $all_ids[CGPII::SCOPE_GROUP], 'NOT IN');
        }

        $conditions->addCacheContexts(['user']);
        $own_query->condition($sub_condition);
        $own_query_modified = TRUE;
      }

      // Add the owner memberships with access to the query (if any).
      if (!empty($allowed_own_ids[CGPII::SCOPE_GROUP])) {
        $conditions->addCacheContexts(['user']);
        $own_query->condition('gid', $allowed_own_ids[CGPII::SCOPE_GROUP], 'IN');
        $own_query_modified = TRUE;
      }

      // Add the owner query if any IDs were found.
      if ($own_query_modified && $own_ids = $own_query->execute()->fetchCol()) {
        $owner_condition_group = new ConditionGroup('AND');
        $owner_condition_group->addCondition($id_key, $own_ids);
        $owner_condition_group->addCondition($owner_key, $account->id());
        $condition_group->addCondition($owner_condition_group);
      }
    }
    else {
      foreach ([0, 1] as $status) {
        $any_query_modified = FALSE;
        $any_query = $this->database
          ->select('group_content_field_data', 'gc')
          ->fields('gc', ['entity_id']);

        // Add the allowed group types to the query (if any).
        if (!empty($allowed_any_by_status_ids[CGPII::SCOPE_GROUP_TYPE][$status])) {
          $sub_condition = $any_query->andConditionGroup();
          $sub_condition->condition('type', $allowed_any_by_status_ids[CGPII::SCOPE_GROUP_TYPE][$status], 'IN');

          // If the user had memberships, we need to make sure they are excluded
          // from group type based matches as the memberships' permissions take
          // precedence.
          if (!empty($all_ids[CGPII::SCOPE_GROUP])) {
            $sub_condition->condition('gid', $all_ids[CGPII::SCOPE_GROUP], 'NOT IN');
          }

          $any_query->condition($sub_condition);
          $any_query_modified = TRUE;
        }

        // Add the memberships with access to the query (if any).
        if (!empty($allowed_any_by_status_ids[CGPII::SCOPE_GROUP][$status])) {
          $any_query->condition('gid', $allowed_any_by_status_ids[CGPII::SCOPE_GROUP][$status], 'IN');
          $any_query_modified = TRUE;
        }

        $own_query_modified = FALSE;
        $own_query = $this->database
          ->select('group_content_field_data', 'gc')
          ->fields('gc', ['entity_id']);

        // Add the allowed owner group types to the query (if any).
        if (!empty($allowed_own_by_status_ids[CGPII::SCOPE_GROUP_TYPE][$status])) {
          $sub_condition = $own_query->andConditionGroup();
          $sub_condition->condition('type', $allowed_own_by_status_ids[CGPII::SCOPE_GROUP_TYPE][$status], 'IN');

          // If the user had memberships, we need to make sure they are excluded
          // from group type based matches as the memberships' permissions take
          // precedence.
          if (!empty($all_ids[CGPII::SCOPE_GROUP])) {
            $sub_condition->condition('gid', $all_ids[CGPII::SCOPE_GROUP], 'NOT IN');
          }

          $conditions->addCacheContexts(['user']);
          $own_query->condition($sub_condition);
          $own_query_modified = TRUE;
        }

        // Add the owner memberships with access to the query (if any).
        if (!empty($allowed_own_by_status_ids[CGPII::SCOPE_GROUP][$status])) {
          $conditions->addCacheContexts(['user']);
          $own_query->condition('gid', $allowed_own_by_status_ids[CGPII::SCOPE_GROUP][$status], 'IN');
          $own_query_modified = TRUE;
        }

        $status_condition_group = new ConditionGroup('AND');
        $sub_condition_group = new ConditionGroup('OR');
        if ($any_query_modified && $any_ids = $any_query->execute()->fetchCol()) {
          $sub_condition_group->addCondition($id_key, $any_ids);
        }
        if ($own_query_modified && $own_ids = $own_query->execute()->fetchCol()) {
          $owner_condition_group = new ConditionGroup('AND');
          $owner_condition_group->addCondition($id_key, $own_ids);
          $owner_condition_group->addCondition($owner_key, $account->id());
          $sub_condition_group->addCondition($owner_condition_group);
        }
        // Only check for this status if we added anything above.
        if ($sub_condition_group->count()) {
          $status_condition_group->addCondition($published_key, $status);
          $status_condition_group->addCondition($sub_condition_group);
          $condition_group->addCondition($status_condition_group);
        }
      }
    }

    $conditions->addCondition($condition_group);
  }

}
