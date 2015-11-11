<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Access\GroupRoleAccessControlHandler.
 */

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the group role entity type.
 *
 * @see \Drupal\group\Entity\GroupRole
 */
class GroupRoleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var $entity \Drupal\group\Entity\GroupRole */
    if ($operation == 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
      }
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
