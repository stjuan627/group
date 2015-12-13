<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupAddAccessCheck.
 */

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Determines access to for group add pages.
 *
 * @ingroup group_access
 */
class GroupAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks access to the group add page for the group type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type. If not specified, access is allowed if there
   *   exists at least one group type for which the user may create a group.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(AccountInterface $account, GroupTypeInterface $group_type = NULL) {
    $access_control_handler = $this->entityManager->getAccessControlHandler('group');
    // If checking whether a group of a particular type may be created.
    if ($account->hasPermission('administer group')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    if ($group_type) {
      return $access_control_handler->createAccess($group_type->id(), $account, [], TRUE);
    }
    // If checking whether a group of any type may be created.
    foreach ($this->entityManager->getStorage('group_type')->loadMultiple() as $group_type) {
      if (($access = $access_control_handler->createAccess($group_type->id(), $account, [], TRUE)) && $access->isAllowed()) {
        return $access;
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
