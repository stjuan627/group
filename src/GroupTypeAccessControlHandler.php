<?php

/**
 * @file
 * Contains \Drupal\group\GroupTypeAccessControlHandler.
 */

namespace Drupal\group;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the group type entity type.
 *
 * @see \Drupal\group\Entity\GroupType
 */
class GroupTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var $entity \Drupal\group\Entity\GroupType */
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
