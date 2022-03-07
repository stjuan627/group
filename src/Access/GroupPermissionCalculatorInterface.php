<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines the group permission calculator interface.
 *
 * Please make sure that when calculating permissions, you attach the right
 * cacheable metadata. This includes cache contexts if your implementation
 * causes the calculated permissions to vary by something. Any cache contexts
 * defined in the getPersistentCacheContexts() methods must also be added to the
 * corresponding calculated permissions.
 *
 * It's of the utmost importance that you properly declare any cache context
 * that should always be present in the ::getPersistentCacheContexts method. For
 * instance: If your outsider permissions are the same for everyone but user
 * 1337, then your outsider permissions must ALL vary by the user cache context.
 *
 * Do NOT use the user.group_permissions in any of the calculations as that
 * cache context is essentially a wrapper around the calculated permissions and
 * you'd therefore end up in an infinite loop.
 */
interface GroupPermissionCalculatorInterface {

  /**
   * Calculates the group permissions for an account within a given scope.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to calculate the outsider permissions.
   * @param string $scope
   *   The scope to calculate the permissions for.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the group permissions within the given scope.
   */
  public function calculatePermissions(AccountInterface $account, $scope);

  /**
   * Gets the persistent cache contexts for a given scope.
   *
   * WARNING: These should never change based on anything other than the passed
   * in scope. If you make these cache contexts conditional, the cache might not
   * work properly and you are exposing your site to privilege escalation.
   *
   * @param string $scope
   *   The scope to get the persistent cache contexts for.
   *
   * @return string[]
   *   The persistent cache contexts.
   */
  public function getPersistentCacheContexts($scope);

}
