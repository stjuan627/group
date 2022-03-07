<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\group\Access\ChainGroupPermissionCalculatorInterface;
use Drupal\group\Access\GroupPermissionChecker;
use Drupal\group\Access\RefinableCalculatedGroupPermissions;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the group permission checker service.
 *
 * @coversDefaultClass \Drupal\group\Access\GroupPermissionChecker
 * @group group
 */
class GroupPermissionCheckerTest extends UnitTestCase {

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\ChainGroupPermissionCalculatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $permissionCalculator;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $membershipLoader;

  /**
   * The group permission checker.
   *
   * @var \Drupal\group\Access\GroupPermissionCheckerInterface
   */
  protected $permissionChecker;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->permissionCalculator = $this->prophesize(ChainGroupPermissionCalculatorInterface::class);
    $this->membershipLoader = $this->prophesize(GroupMembershipLoaderInterface::class);
    $this->permissionChecker = new GroupPermissionChecker($this->permissionCalculator->reveal(), $this->membershipLoader->reveal());
  }

  /**
   * Tests checking whether a user has a permission in a group.
   *
   * @param bool $is_member
   *   Whether the user is a member.
   * @param array $outsider_permissions
   *   The permissions the user has in the outsider scope.
   * @param bool $outsider_admin
   *   Whether the user is an admin in the outsider scope.
   * @param array $insider_permissions
   *   The permissions the user has in the insider scope.
   * @param bool $insider_admin
   *   Whether the user is an admin in the insider scope.
   * @param array $individual_permissions
   *   The permissions the user has in the individual scope.
   * @param bool $individual_admin
   *   Whether the user is an admin in the individual scope.
   * @param string $permission
   *   The permission to check for.
   * @param bool $has_permission
   *   Whether the user should have the permission.
   * @param string $message
   *   The message to use in the assertion.
   *
   * @covers ::hasPermissionInGroup
   * @dataProvider provideHasPermissionInGroupScenarios
   */
  public function testHasPermissionInGroup($is_member, $outsider_permissions, $outsider_admin, $insider_permissions, $insider_admin, $individual_permissions, $individual_admin, $permission, $has_permission, $message) {
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $group = $this->prophesize(GroupInterface::class);
    $group->id()->willReturn(1);
    $group->bundle()->willReturn('foo');
    $group = $group->reveal();

    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    foreach ($outsider_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedGroupPermissionsItem('outsider', $identifier, $permissions, $outsider_admin));
    }
    foreach ($insider_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedGroupPermissionsItem('insider', $identifier, $permissions, $insider_admin));
    }
    foreach ($individual_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedGroupPermissionsItem('individual', $identifier, $permissions, $individual_admin));
    }

    $this->permissionCalculator
      ->calculateFullPermissions($account)
      ->willReturn($calculated_permissions);

    $this->membershipLoader
      ->load($group, $account)
      ->willReturn($is_member);

    $result = $this->permissionChecker->hasPermissionInGroup($permission, $account, $group);
    $this->assertSame($has_permission, $result, $message);
  }

  /**
   * Data provider for testHasPermissionInGroup().
   *
   * All scenarios assume group ID 1 and type 'foo'.
   */
  public function provideHasPermissionInGroupScenarios() {
    $scenarios['outsiderWithAdmin'] = [
      FALSE,
      ['foo' => []],
      TRUE,
      [],
      FALSE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An outsider with the group admin permission can view the group.'
    ];

    $scenarios['insiderWithAdmin'] = [
      TRUE,
      [],
      FALSE,
      ['foo' => []],
      TRUE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An insider with the group admin permission can view the group.'
    ];

    $scenarios['memberWithAdmin'] = [
      TRUE,
      [],
      FALSE,
      [],
      FALSE,
      [1 => []],
      TRUE,
      'view group',
      TRUE,
      'A member with the group admin permission can view the group.'
    ];

    $scenarios['outsiderWithPermission'] = [
      FALSE,
      ['foo' => ['view group']],
      FALSE,
      [],
      FALSE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An outsider with the right permission can view the group.'
    ];

    $scenarios['insiderWithPermission'] = [
      TRUE,
      [],
      FALSE,
      ['foo' => ['view group']],
      FALSE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An insider with the right permission can view the group.'
    ];

    $scenarios['memberWithPermission'] = [
      TRUE,
      [],
      FALSE,
      [],
      FALSE,
      [1 => ['view group']],
      FALSE,
      'view group',
      TRUE,
      'A member with the right permission can view the group.'
    ];

    $scenarios['outsiderWithoutPermission'] = [
      FALSE,
      ['foo' => []],
      FALSE,
      [],
      FALSE,
      [],
      FALSE,
      'view group',
      FALSE,
      'An outsider without the right permission can not view the group.'
    ];

    $scenarios['insiderWithoutPermission'] = [
      TRUE,
      [],
      FALSE,
      ['foo' => []],
      FALSE,
      [],
      FALSE,
      'view group',
      FALSE,
      'An insider without the right permission can not view the group.'
    ];

    $scenarios['memberWithoutPermission'] = [
      TRUE,
      [],
      FALSE,
      [],
      FALSE,
      [1 => []],
      FALSE,
      'view group',
      FALSE,
      'A member without the right permission can not view the group.'
    ];

    return $scenarios;
  }

}
