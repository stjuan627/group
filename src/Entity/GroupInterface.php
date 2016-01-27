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
 * @ingroup group
 */
interface GroupInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the group type entity the group uses.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   */
  public function getGroupType();

  /**
   * Retrieves all GroupContent entities for the group.
   *
   * @param string $content_enabler
   *   (optional) A content enabler plugin ID to filter on.
   * @param array $filters
   *   (optional) An associative array of extra filters where the keys are
   *   property or field names and the values are the value to filter on.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   A list of GroupContent entities matching the criteria.
   */
  public function getContent($content_enabler = NULL, $filters = []);

  /**
   * Retrieves all group content for the group.
   *
   * Unlike GroupInterface::getContent(), this function actually returns the
   * entities that were added to the group through GroupContent entities.
   *
   * @param string $content_enabler
   *   (optional) A content enabler plugin ID to filter on.
   * @param array $filters
   *   (optional) An associative array of extra filters where the keys are
   *   property or field names and the values are the value to filter on.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   A list of entities matching the criteria.
   *
   * @see \Drupal\group\Entity\GroupInterface::getContent()
   */
  public function getContentEntities($content_enabler = NULL, $filters = []);

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

}
