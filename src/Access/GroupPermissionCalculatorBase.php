<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Base class for group permission calculators.
 */
abstract class GroupPermissionCalculatorBase implements GroupPermissionCalculatorInterface {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    return (new RefinableCalculatedGroupPermissions())->addCacheContexts($this->getPersistentCacheContexts($scope));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    return [];
  }

}
