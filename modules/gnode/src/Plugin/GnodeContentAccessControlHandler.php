<?php

namespace Drupal\gnode\Plugin;

use Drupal\group\Plugin\GroupContentAccessControlHandler;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides access control for Node GroupContent entities.
 */
class GnodeContentAccessControlHandler extends GroupContentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function relationAccess(GroupContentInterface $group_content, $operation, AccountInterface $account, $return_as_object = FALSE) {
    // Grant access to unpublished relation (and entity) only if the user has
    // permission to perform the operation on the entity.
    // Effect: a user can view group content node only if the entity and
    // relation is published or if that user has permission to view unpublished
    // group content entities.
    $permission = $this->permissionProvider->getPermission("$operation unpublished", 'entity', 'any');
    if (!$group_content->get('status')->value && $permission) {
      $result = $this->combinedGroupContentPermissionsCheck($group_content, $account, [$permission], $operation);
    }
    else {
      $result = parent::relationAccess($group_content, $operation, $account, TRUE);
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
