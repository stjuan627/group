<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupAccessResult.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Extends the AccessResult class with group permission checks.
 */
abstract class GroupAccessResult extends AccessResult {

  /**
   * Creates an allowed access result if the permission is present, neutral otherwise.
   *
   * Checks the permission and adds a 'group.permissions' cache context.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to check a permission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check a permission.
   * @param string $permission
   *   The permission to check for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permission, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  public static function allowedIfHasGroupPermission(GroupInterface $group, AccountInterface $account, $permission) {
    return static::allowedIf($group->hasPermission($permission, $account));
    //return static::allowedIf($group->hasPermission($permission, $account))->addCacheContexts(['group.permissions']);
  }

  /**
   * Creates an allowed access result if the permissions are present, neutral otherwise.
   *
   * Checks the permission and adds a 'group.permissions' cache contexts.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to check permissions.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check permissions.
   * @param array $permissions
   *   The permissions to check.
   * @param string $conjunction
   *   (optional) 'AND' if all permissions are required, 'OR' in case just one.
   *   Defaults to 'AND'.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permissions, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  public static function allowedIfHasGroupPermissions(GroupInterface $group, AccountInterface $account, array $permissions, $conjunction = 'AND') {
    $access = FALSE;

    if ($conjunction == 'AND' && !empty($permissions)) {
      $access = TRUE;
      foreach ($permissions as $permission) {
        if (!$permission_access = $group->hasPermission($permission, $account)) {
          $access = FALSE;
          break;
        }
      }
    }
    else {
      foreach ($permissions as $permission) {
        if ($permission_access = $group->hasPermission($permission, $account)) {
          $access = TRUE;
          break;
        }
      }
    }

    return static::allowedIf($access);
    //return static::allowedIf($access)->addCacheContexts(empty($permissions) ? [] : ['group.permissions']);
  }

}
