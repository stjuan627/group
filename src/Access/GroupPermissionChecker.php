<?php

namespace Drupal\group\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Calculates group permissions for an account.
 */
class GroupPermissionChecker implements GroupPermissionCheckerInterface {

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\ChainGroupPermissionCalculatorInterface
   */
  protected $groupPermissionCalculator;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * Constructs a GroupPermissionChecker object.
   *
   * @param \Drupal\group\Access\ChainGroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   */
  public function __construct(ChainGroupPermissionCalculatorInterface $permission_calculator, GroupMembershipLoaderInterface $group_membership_loader) {
    $this->groupPermissionCalculator = $permission_calculator;
    $this->groupMembershipLoader = $group_membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermissionInGroup($permission, AccountInterface $account, GroupInterface $group) {
    $calculated_permissions = $this->groupPermissionCalculator->calculateFullPermissions($account);

    if ($this->groupMembershipLoader->load($group, $account)) {
      $item = $calculated_permissions->getItem('individual', $group->id());
      if ($item && $item->hasPermission($permission)) {
        return TRUE;
      }
      $item = $calculated_permissions->getItem('insider', $group->bundle());
    }
    else {
      $item = $calculated_permissions->getItem('outsider', $group->bundle());
    }

    return $item && $item->hasPermission($permission);
  }

}
