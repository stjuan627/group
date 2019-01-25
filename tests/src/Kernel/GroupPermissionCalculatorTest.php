<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\group\Access\CalculatedGroupPermissions;

/**
 * Tests the calculation of group permissions.
 *
 * @coversDefaultClass \Drupal\group\Access\GroupPermissionCalculator
 * @group group
 */
class GroupPermissionCalculatorTest extends GroupKernelTestBase {

  /**
   * The group permissions hash generator service.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizer
   */
  protected $roleSynchronizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->permissionCalculator = $this->container->get('group_permission.calculator');
    $this->roleSynchronizer = $this->container->get('group_role.synchronizer');
  }

  /**
   * Tests the calculation of the anonymous permissions.
   *
   * @covers ::calculateAnonymousPermissions
   * @uses \Drupal\group\Access\GroupPermissionCalculator::buildAnonymousPermissions
   */
  public function testCalculateAnonymousPermissions() {
    // @todo Use a proper set-up instead of the one from GroupKernelTestBase?
    $permissions = [
      'default' => [],
      'other' => [],
    ];
    $cache_tags = [
      'config:group.role.default-anonymous',
      'config:group.role.other-anonymous',
      'config:group_type_list',
    ];
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculateAnonymousPermissions();
    $this->assertEquals($permissions, $calculated_permissions->getAnonymousPermissions(), 'Anonymous permissions are returned per group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Anonymous permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Anonymous permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Anonymous permissions have the right cache tags.');

    $group_role = $this->entityTypeManager->getStorage('group_role')->load('default-anonymous');
    $group_role->grantPermission('view group')->save();
    $permissions['default'][] = 'view group';

    $calculated_permissions = $this->permissionCalculator->calculateAnonymousPermissions();
    $this->assertEquals($permissions, $calculated_permissions->getAnonymousPermissions(), 'Updated anonymous permissions are returned per group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Updated anonymous permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Updated anonymous permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Updated anonymous permissions have the right cache tags.');

    $this->createGroupType(['id' => 'test']);
    $permissions['test'] = [];
    $cache_tags[] = 'config:group.role.test-anonymous';
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculateAnonymousPermissions();
    $this->assertEquals($permissions, $calculated_permissions->getAnonymousPermissions(), 'Anonymous permissions are updated after introducing a new group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Anonymous permissions have the right cache contexts after introducing a new group type.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Anonymous permissions have the right max cache age after introducing a new group type.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Anonymous permissions have the right cache tags after introducing a new group type.');
  }

  /**
   * Tests the calculation of the outsider permissions.
   *
   * @covers ::calculateOutsiderPermissions
   * @uses \Drupal\group\Access\GroupPermissionCalculator::buildOutsiderPermissions
   */
  public function testCalculateOutsiderPermissions() {
    // @todo Use a proper set-up instead of the one from GroupKernelTestBase?
    $account = $this->createUser(['roles' => ['test']]);
    $group_role_id = $this->roleSynchronizer->getGroupRoleId('default', 'test');

    $permissions = [
      'default' => ['join group', 'view group'],
      'other' => [],
    ];
    $cache_tags = [
      'config:group.role.default-outsider',
      'config:group.role.other-outsider',
      'config:group.role.' . $group_role_id,
      'config:group.role.' . $this->roleSynchronizer->getGroupRoleId('other', 'test'),
      'config:group_type_list',
    ];
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculateOutsiderPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getOutsiderPermissions(), 'Outsider permissions are returned per group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Outsider permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Outsider permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Outsider permissions have the right cache tags.');

    $group_role = $this->entityTypeManager->getStorage('group_role')->load('other-outsider');
    $group_role->grantPermission('view group')->save();
    $permissions['other'][] = 'view group';

    $calculated_permissions = $this->permissionCalculator->calculateOutsiderPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getOutsiderPermissions(), 'Updated outsider permissions are returned per group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Updated outsider permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Updated outsider permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Updated outsider permissions have the right cache tags.');

    $group_role = $this->entityTypeManager->getStorage('group_role')->load($group_role_id);
    $group_role->grantPermission('edit group')->save();
    $permissions['default'][] = 'edit group';

    $calculated_permissions = $this->permissionCalculator->calculateOutsiderPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getOutsiderPermissions(), 'Updated synchronized outsider permissions are returned per group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Updated synchronized outsider permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Updated synchronized outsider permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Updated synchronized outsider permissions have the right cache tags.');

    $this->createGroupType(['id' => 'test']);
    $permissions['test'] = [];
    $cache_tags[] = 'config:group.role.test-outsider';
    $cache_tags[] = 'config:group.role.' . $this->roleSynchronizer->getGroupRoleId('test', 'test');
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculateOutsiderPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getOutsiderPermissions(), 'Outsider permissions are updated after introducing a new group type.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Outsider permissions have the right cache contexts after introducing a new group type.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Outsider permissions have the right max cache age after introducing a new group type.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Outsider permissions have the right cache tags after introducing a new group type.');
  }

  /**
   * Tests the calculation of the member permissions.
   *
   * @covers ::calculateMemberPermissions
   * @uses \Drupal\group\Access\GroupPermissionCalculator::buildMemberPermissions
   */
  public function testCalculateMemberPermissions() {
    // @todo Use a proper set-up instead of the one from GroupKernelTestBase?
    $account = $this->createUser();
    $group = $this->createGroup(['type' => 'default']);

    $permissions = [];
    $cache_tags = ['user:' . $account->id()];

    $calculated_permissions = $this->permissionCalculator->calculateMemberPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getMemberPermissions(), 'Member permissions are returned per group ID.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Member permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Member permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Member permissions have the right cache tags.');

    $group->addMember($account);
    $member = $group->getMember($account);
    $permissions[$group->id()][] = 'view group';
    $permissions[$group->id()][] = 'leave group';
    $cache_tags[] = 'config:group.role.default-member';
    $cache_tags = Cache::mergeTags($cache_tags, $member->getCacheTags());

    $calculated_permissions = $this->permissionCalculator->calculateMemberPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getMemberPermissions(), 'Member permissions are returned per group ID after joining a group.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Member permissions have the right cache contexts after joining a group.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Member permissions have the right max cache age after joining a group.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Member permissions have the right cache tags after joining a group.');

    // @todo This displays a desperate need for addRole() and removeRole().
    $membership = $member->getGroupContent();
    $membership->group_roles[] = 'default-custom';
    $membership->save();
    $permissions[$group->id()][] = 'join group';
    $cache_tags[] = 'config:group.role.default-custom';
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculateMemberPermissions($account);
    $this->assertEquals($permissions, $calculated_permissions->getMemberPermissions(), 'Updated member permissions are returned per group ID.', 0.0, 1, TRUE);
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Updated member permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Updated member permissions have the right max cache age.');
    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags(), 'Updated member permissions have the right cache tags.');
  }

  /**
   * Tests the calculation of the authenticated permissions.
   *
   * @covers ::calculateAuthenticatedPermissions
   * @depends testCalculateOutsiderPermissions
   * @depends testCalculateMemberPermissions
   */
  public function testCalculateAuthenticatedPermissions() {
    $account = $this->createUser();
    $group = $this->createGroup(['type' => 'default']);
    $group->addMember($account);

    $calculated_permissions = new CalculatedGroupPermissions();
    $calculated_permissions
      ->merge($this->permissionCalculator->calculateOutsiderPermissions($account))
      ->merge($this->permissionCalculator->calculateMemberPermissions($account));

    $this->assertEquals($calculated_permissions, $this->permissionCalculator->calculateAuthenticatedPermissions($account), 'Authenticated permissions are returned as a merge of outsider and member permissions.');
  }

  /**
   * Tests the calculation of an account's permissions.
   *
   * @covers ::calculatePermissions
   * @depends testCalculateAnonymousPermissions
   * @depends testCalculateAuthenticatedPermissions
   */
  public function testCalculatePermissions() {
    $account = new AnonymousUserSession();
    $calculated_permissions = $this->permissionCalculator->calculateAnonymousPermissions();
    $this->assertEquals($calculated_permissions, $this->permissionCalculator->calculatePermissions($account), 'The calculated anonymous permissions are returned for an anonymous user.');

    $account = $this->createUser();
    $group = $this->createGroup(['type' => 'default']);
    $group->addMember($account);
    $calculated_permissions = new CalculatedGroupPermissions();
    $calculated_permissions
      ->merge($this->permissionCalculator->calculateOutsiderPermissions($account))
      ->merge($this->permissionCalculator->calculateMemberPermissions($account));
    $this->assertEquals($calculated_permissions, $this->permissionCalculator->calculatePermissions($account), 'Calculated permissions for a member are returned as a merge of outsider and member permissions.');
  }

}
