<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupContentTypeInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a group content type entity.
 */
interface GroupContentTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this group content type.
   */
  public function getDescription();

  /**
   * Gets the group type the content type was created for.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The group type for which the content type was created.
   */
  public function getGroupType();

  /**
   * Gets the content enabler plugin the content type uses.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The content enabler plugin the content type uses.
   */
  public function getContentPlugin();

}
