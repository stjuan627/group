<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupTypeInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a group type entity.
 */
interface GroupTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this group type.
   */
  public function getDescription();

  /**
   * Gets the group roles.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles this group type uses.
   */
  public function getRoles();

  /**
   * Gets the role ids.
   *
   * @return string[]
   *   The ids of the group roles this group type uses.
   */
  public function getRoleIds();

}
