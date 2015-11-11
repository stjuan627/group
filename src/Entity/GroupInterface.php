<?php
/**
 * @file
 * Contains \Drupal\group\Entity\GroupInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a Group entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup group
 */
interface GroupInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Checks whether a user has the requested permission.
   *
   * @param string $permission
   *   The permission to check for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   *
   * @return bool
   *   Whether the user has the requested permission.
   */
  public function hasPermission($permission, AccountInterface $account);

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
