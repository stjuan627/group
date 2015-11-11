<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupRoleInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a group role entity.
 */
interface GroupRoleInterface extends ConfigEntityInterface {

  /**
   * Determines whether the group role is locked.
   *
   * @return string|false
   *   The module name that locks the role or FALSE.
   */
  public function isLocked();

}
