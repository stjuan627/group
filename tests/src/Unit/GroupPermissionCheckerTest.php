<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface;
use Drupal\group\Access\ChainGroupPermissionCalculatorInterface;
use Drupal\group\Access\GroupPermissionChecker;
use Drupal\group\Access\RefinableCalculatedGroupPermissions;
use Drupal\group\Entity\GroupInterface;
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
    $this->permissionChecker = new GroupPermissionChecker($this->permissionCalculator->reveal());
  }

  /**
   * Tests checking whether a user has a permission in a group.
   *
   * @param bool $is_anon
   *   Whether the user is anonymous.
   * @param array $group_type_permissions
   *   The permissions the user has in the group type scope.
   * @param bool $group_type_admin
   *   Whether the user is an admin in the group type scope.
   * @param array $group_permissions
   *   The permissions the user has in the group scope.
   * @param bool $group_admin
   *   Whether the user is an admin in the group scope.
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
  public function testHasPermissionInGroup($is_anon, $group_type_permissions, $group_type_admin, $group_permissions, $group_admin, $permission, $has_permission, $message) {
    $account = $this->prophesize(AccountInterface::class);
    $account->isAnonymous()->willReturn($is_anon);

    $group = $this->prophesize(GroupInterface::class);
    $group->id()->willReturn(1);
    $group->bundle()->willReturn('foo');

    $scope_gt = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP_TYPE;
    $scope_g = CalculatedGroupPermissionsItemInterface::SCOPE_GROUP;
    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    foreach ($group_type_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedGroupPermissionsItem($scope_gt, $identifier, $permissions, $group_type_admin));
    }
    foreach ($group_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedGroupPermissionsItem($scope_g, $identifier, $permissions, $group_admin));
    }

    $this->permissionCalculator
      ->calculatePermissions($account->reveal())
      ->willReturn($calculated_permissions);

    $result = $this->permissionChecker->hasPermissionInGroup($permission, $account->reveal(), $group->reveal());
    $this->assertSame($has_permission, $result, $message);
  }

  /**
   * Data provider for testHasPermissionInGroup().
   *
   * All scenarios assume group ID 1 and type 'foo'.
   */
  public function provideHasPermissionInGroupScenarios() {
    $scenarios['anonymousWithAdmin'] = [
      TRUE,
      ['foo' => []],
      TRUE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An anonymous user with the group admin permission can view the group.'
    ];

    $scenarios['outsiderWithAdmin'] = [
      FALSE,
      ['foo' => []],
      TRUE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An outsider with the group admin permission can view the group.'
    ];

    $scenarios['memberWithAdmin'] = [
      FALSE,
      [],
      FALSE,
      [1 => []],
      TRUE,
      'view group',
      TRUE,
      'A member with the group admin permission can view the group.'
    ];

    $scenarios['anonymousWithPermission'] = [
      TRUE,
      ['foo' => ['view group']],
      FALSE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An anonymous user with the right permission can view the group.'
    ];

    $scenarios['outsiderWithPermission'] = [
      FALSE,
      ['foo' => ['view group']],
      FALSE,
      [],
      FALSE,
      'view group',
      TRUE,
      'An outsider with the right permission can view the group.'
    ];

    $scenarios['memberWithPermission'] = [
      FALSE,
      [],
      FALSE,
      [1 => ['view group']],
      FALSE,
      'view group',
      TRUE,
      'A member with the right permission can view the group.'
    ];

    $scenarios['anonymousWithoutPermission'] = [
      TRUE,
      ['foo' => []],
      FALSE,
      [],
      FALSE,
      'view group',
      FALSE,
      'An anonymous user without the right permission can not view the group.'
    ];

    $scenarios['outsiderWithoutPermission'] = [
      FALSE,
      ['foo' => []],
      FALSE,
      [],
      FALSE,
      'view group',
      FALSE,
      'An outsider without the right permission can not view the group.'
    ];

    $scenarios['memberWithoutPermission'] = [
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
