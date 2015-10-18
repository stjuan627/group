<?php

/**
 * @file
 * Contains \Drupal\group\GroupTypeInterface.
 */

namespace Drupal\group;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a group type entity.
 */
interface GroupTypeInterface extends ConfigEntityInterface {

  /**
   * Determines whether the group type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this group type.
   */
  public function getDescription();
}
