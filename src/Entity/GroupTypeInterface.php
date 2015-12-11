<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupTypeInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a group type entity.
 */
interface GroupTypeInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

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
   * Gets the role IDs.
   *
   * @return string[]
   *   The ids of the group roles this group type uses.
   */
  public function getRoleIds();

  /**
   * Returns the group content plugin IDs for this style.
   *
   * @return string[]
   *   An array of group content plugin IDs.
   */
  public function getContentIds();

  /**
   * Returns the group content plugins for this style.
   *
   * @return \Drupal\group\Plugin\GroupContentPluginCollection
   *   The group content plugin collection.
   */
  public function getContent();

}
