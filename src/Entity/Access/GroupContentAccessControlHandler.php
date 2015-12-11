<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Access\GroupContentAccessControlHandler
 */

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Group entity.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // @todo Implement this on the plugin.

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf(TRUE);

      case 'edit':
        return AccessResult::allowedIf(TRUE);

      case 'delete':
        return AccessResult::allowedIf(TRUE);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // @todo Implement this on the plugin.
    return AccessResult::allowedIf(TRUE);
  }

}
