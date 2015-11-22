<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Storage\GroupRoleStorage.
 */

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Controller class for group roles.
 */
class GroupRoleStorage extends ConfigEntityStorage implements GroupRoleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function isPermissionInRoles($permission, array $rids) {
    foreach ($this->loadMultiple($rids) as $group_role) {
      /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
      if ($group_role->hasPermission($permission)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
