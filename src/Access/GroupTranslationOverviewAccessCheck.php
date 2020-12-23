<?php

namespace Drupal\group\Access;

use Drupal\content_translation\Access\ContentTranslationOverviewAccess;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;

/**
 * Access check for entity translation overview.
 */
class GroupTranslationOverviewAccessCheck extends ContentTranslationOverviewAccess {

  /**
   * {@inheritdoc}
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    /* @var \Drupal\group\Entity\GroupInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity_type_id === 'group' && $entity instanceof Group) {
      // We need to make sure the group is translatable and user has permission.
      $condition = !$entity->getUntranslated()->language()->isLocked() &&
        \Drupal::languageManager()->isMultilingual() &&
        $entity->isTranslatable();
      if ($condition) {
        // We always override the access for groups based on group permissions.
        if ($entity->hasPermission('translate group', $account)) {
          return AccessResult::allowed()->addCacheContexts(['user'])->addCacheableDependency($entity)->addCacheableDependency($entity->getGroupType());
        }
      }
      return AccessResult::forbidden()->addCacheContexts(['user'])->addCacheableDependency($entity)->addCacheableDependency($entity->getGroupType());
    }

    // Fallback for other entities.
    return parent::access($route_match, $account, $entity_type_id);
  }

}
