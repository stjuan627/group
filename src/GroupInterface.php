<?php
/**
 * @file
 * Contains \Drupal\group\GroupInterface.
 */

namespace Drupal\group;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Group entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup group
 */
interface GroupInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Prepares the langcode for a group.
   *
   * This will return the content language instead of ::activeLangcode if the
   * Language module is enabled and the group has a translation in the content
   * language. Its main use is to pass this language on to ::access().
   *
   * @return string
   *   The langcode for this group.
   */
  public function prepareLangcode();

}
