<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupTypeInterface.
 */

namespace Drupal\group\Entity;

use Drupal\group\Plugin\GroupContentEnablerInterface;
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
   * Returns the content enabler plugins for this group type.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   The group content plugin collection.
   */
  public function enabledContent();

  /**
   * Adds a content enabler plugin to this group type.
   *
   * @param array $configuration
   *   An array of content enabler plugin configuration.
   *
   * @return string
   *   The content enabler instance ID.
   */
  public function enableContent(array $configuration);

  /**
   * Removes a content enabler plugin from this group type.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $content
   *   The content enabler plugin instance.
   *
   * @return $this
   */
  public function disableContent(GroupContentEnablerInterface $content);

}
