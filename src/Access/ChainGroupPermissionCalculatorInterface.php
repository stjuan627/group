<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Runs the added calculators one by one until the full permissions are built.
 *
 * Each calculator in the chain can be another chain, which is why this
 * interface extends the permission calculator one.
 *
 * @todo Perhaps move to own module and make scopes config entities.
 *   But why? Label/desc/id and then what?
 * @todo Add alterPermissions($permissions, $account, $scope)?
 */
interface ChainGroupPermissionCalculatorInterface extends GroupPermissionCalculatorInterface {

  /**
   * Adds a calculator.
   *
   * @param \Drupal\group\Access\GroupPermissionCalculatorInterface $calculator
   *   The calculator.
   *
   * @return mixed
   */
  public function addCalculator(GroupPermissionCalculatorInterface $calculator);

  /**
   * Gets all added calculators.
   *
   * @return \Drupal\group\Access\GroupPermissionCalculatorInterface[]
   *   The calculators.
   */
  public function getCalculators();

  /**
   * Calculates the full group permissions for an account.
   *
   * This includes all scopes: outsider, insider, individual.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to retrieve the permissions.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsInterface
   *   An object representing the full group permissions.
   */
  public function calculateFullPermissions(AccountInterface $account);

}
