<?php

namespace Drupal\group\Access;

use Drupal\content_translation\Access\ContentTranslationManageAccessCheck;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Symfony\Component\Routing\Route;

/**
 * Access check for entity translation manage page.
 */
class GroupTranslationManageAccessCheck extends ContentTranslationManageAccessCheck {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL, $entity_type_id = NULL) {
    /* @var \Drupal\group\Entity\GroupInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);
    if ($entity_type_id === 'group' && $entity instanceof Group) {

      // We always override the access for groups based on group permissions.
      if ($entity->hasPermission('translate group', $account)) {
        return AccessResult::allowed()->addCacheContexts(['user'])->addCacheableDependency($entity)->addCacheableDependency($entity->getGroupType());
      }

      return AccessResult::forbidden()->addCacheContexts(['user'])->addCacheableDependency($entity)->addCacheableDependency($entity->getGroupType());
    }

    return parent::access($route, $route_match, $account, $source, $target, $language, $entity_type_id);
  }

}
