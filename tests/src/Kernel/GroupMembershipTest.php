<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionCheckerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of the GroupMembership shared bundle class.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupMembership
 * @group group
 */
class GroupMembershipTest extends GroupKernelTestBase {

  /**
   * The account to use in testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * The group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected GroupTypeInterface $groupType;

  /**
   * The insider group role to use in testing.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected GroupRoleInterface $groupRoleInsider;

  /**
   * The individual group role to use in testing.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected GroupRoleInterface $groupRoleIndividual;

  /**
   * The group to use in testing.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected GroupInterface $group;

  /**
   * The group membership to run tests on.
   *
   * @var \Drupal\group\Entity\GroupMembershipInterface
   */
  protected GroupMembershipInterface $groupMembership;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->createUser();
    $this->groupType = $this->createGroupType();
    $this->groupRoleInsider = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ]);
    $this->groupRoleIndividual = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'permissions' => ['view group'],
    ]);

    // Reload the roles so that we can do proper comparison of loaded roles.
    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->groupRoleInsider = $storage->load($this->groupRoleInsider->id());
    $this->groupRoleIndividual = $storage->load($this->groupRoleIndividual->id());

    $this->group = $this->createGroup(['type' => $this->groupType->id()]);
    $this->group->addMember($this->account, ['group_roles' => [$this->groupRoleIndividual->id()]]);

    // Manually load the membership here using the storage so that we don't
    // end up testing ::loadSingle() via a detour.
    $memberships = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties([
        'gid' => $this->group->id(),
        'entity_id' => $this->account->id(),
        'plugin_id' => 'group_membership',
      ]);

    $this->groupMembership = reset($memberships);
  }

  /**
   * Tests the retrieval of a membership's group roles.
   *
   * @covers ::getRoles
   */
  public function testGetRoles() {
    $expected[$this->groupRoleIndividual->id()] = $this->groupRoleIndividual;
    $this->assertEquals($expected, $this->groupMembership->getRoles(FALSE));

    $expected[$this->groupRoleInsider->id()] = $this->groupRoleInsider;
    $this->assertEquals($expected, $this->groupMembership->getRoles());
  }

  /**
   * Tests the addition of a group role to a membership.
   *
   * @covers ::addRole
   * @depends testGetRoles
   */
  public function testAddRole() {
    $group_role = $this->createGroupRole([
      'group_type' => $this->groupType->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);

    $expected = [
      $this->groupRoleIndividual->id(),
      $this->groupRoleInsider->id(),
      $group_role->id(),
    ];
    $this->groupMembership->addRole($group_role->id());
    $this->assertEqualsCanonicalizing($expected, array_keys($this->groupMembership->getRoles()));
  }

  /**
   * Tests the removal of a group role from a membership.
   *
   * @covers ::removeRole
   * @depends testGetRoles
   */
  public function testRemoveRole() {
    $this->groupMembership->removeRole($this->groupRoleIndividual->id());
    $this->assertEquals([$this->groupRoleInsider->id()], array_keys($this->groupMembership->getRoles()));
  }

  /**
   * Tests the permission check on a membership.
   *
   * @covers ::hasPermission
   */
  public function testHasPermission() {
    // This should always be a wrapper around the permission checker, so check.
    $permission_checker = \Drupal::service('group_permission.checker');
    assert($permission_checker instanceof GroupPermissionCheckerInterface);

    $expected = $permission_checker->hasPermissionInGroup('view group', $this->account, $this->group);
    $this->assertSame($expected, $this->groupMembership->hasPermission('view group'));

    $expected = $permission_checker->hasPermissionInGroup('edit group', $this->account, $this->group);
    $this->assertSame($expected, $this->groupMembership->hasPermission('edit group'));
  }

}
