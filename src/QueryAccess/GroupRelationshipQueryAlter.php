<?php

namespace Drupal\group\QueryAccess;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\group\PermissionScopeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for altering group queries.
 *
 * @internal
 */
class GroupRelationshipQueryAlter extends QueryAlterBase {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('group_relation_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doAlter($operation) {
    // If any new relationship is added, it might change access.
    $this->cacheableMetadata->addCacheTags(['group_relationship_list']);

    // Retrieve the full list of group permissions for the user.
    $this->cacheableMetadata->addCacheContexts(['user.group_permissions']);
    $calculated_permissions = $this->permissionCalculator->calculateFullPermissions($this->currentUser);

    $allowed_any_ids = $allowed_own_ids = [];
    foreach (array_keys($this->pluginManager->getDefinitions()) as $plugin_id) {
      $handler = $this->pluginManager->getPermissionProvider($plugin_id);
      $admin_permission = $handler->getAdminPermission();
      $any_permission = $handler->getPermission($operation, 'relationship', 'any');
      $own_permission = $handler->getPermission($operation, 'relationship', 'own');

      foreach ($calculated_permissions->getItems() as $item) {
        if ($admin_permission !== FALSE && $item->hasPermission($admin_permission)) {
          $allowed_any_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
        elseif ($any_permission !== FALSE && $item->hasPermission($any_permission)) {
          $allowed_any_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
        elseif($own_permission !== FALSE && $item->hasPermission($own_permission)) {
          $allowed_own_ids[$item->getScope()][$plugin_id][] = $item->getIdentifier();
        }
      }
    }

    // If no group type or group gave access, we deny access altogether.
    if (empty($allowed_any_ids) && empty($allowed_own_ids)) {
      $this->query->alwaysFalse();
      return;
    }

    if (!empty($allowed_any_ids)) {
      $this->addScopedConditions($allowed_any_ids, $this->query);
    }

    if (!empty($allowed_own_ids)) {
      $this->cacheableMetadata->addCacheContexts(['user']);
      $data_table = $this->ensureDataTable();

      $this->query->condition($owner_conditions = $this->query->andConditionGroup());
      $owner_conditions->condition("$data_table.uid", $this->currentUser->id());
      $this->addScopedConditions($allowed_own_ids, $owner_conditions);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $scope_conditions, $scope) {
    $membership_alias = $this->ensureMembershipJoin();
    $data_table = $this->ensureDataTable();

    foreach ($allowed_ids as $plugin_id => $identifiers) {
      $sub_condition = $this->query->andConditionGroup();
      $sub_condition->condition("$data_table.group_type", array_unique($identifiers), 'IN');
      $sub_condition->condition("$data_table.plugin_id", $plugin_id);
      if ($scope === PermissionScopeInterface::OUTSIDER_ID) {
        $sub_condition->isNull("$membership_alias.entity_id");
      }
      else {
        $sub_condition->isNotNull("$membership_alias.entity_id");
      }
      $scope_conditions->condition($sub_condition);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $scope_conditions) {
    $data_table = $this->ensureDataTable();

    foreach ($allowed_ids as $plugin_id => $identifiers) {
      $sub_condition = $this->query->andConditionGroup();
      $sub_condition->condition("$data_table.gid", array_unique($identifiers) , 'IN');
      $sub_condition->condition("$data_table.plugin_id", $plugin_id);
      $scope_conditions->condition($sub_condition);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return $this->ensureBaseTable();
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinLeftField() {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinRightField() {
    return 'id';
  }

}
