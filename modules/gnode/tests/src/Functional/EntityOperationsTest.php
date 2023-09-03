<?php

namespace Drupal\Tests\gnode\Functional;

use Drupal\Tests\group\Functional\EntityOperationsTest as GroupEntityOperationsTest;

/**
 * Tests that entity operations (do not) show up on the group overview.
 *
 * @see gnode_entity_operation()
 *
 * @group gnode
 */
class EntityOperationsTest extends GroupEntityOperationsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['gnode'];

  /**
   * {@inheritdoc}
   */
  public function provideEntityOperationScenarios() {
    $scenarios['withoutAccess'] = [
      [],
      ['group/1/nodes' => 'Nodes'],
    ];

    $scenarios['withAccess'] = [
      [],
      ['group/1/nodes' => 'Nodes'],
      ['access group_node overview'],
    ];

    $scenarios['withAccessAndViews'] = [
      ['group/1/nodes' => 'Nodes'],
      [],
      ['access group_node overview'],
      ['views'],
    ];

    return $scenarios;
  }

  /**
   * Test access on group nodes.
   */
  public function testGroupNodeAccess() {
    $group = $this->createGroup();

    // Create group content type, install plugin.
    $node_type_id = 'article';
    $plugin_id = 'group_node:' . $node_type_id;
    $group_type = $group->getGroupType();
    $this->drupalCreateContentType(['type' => $node_type_id]);
    $this->container->get('plugin.manager.group_content_enabler')->clearCachedDefinitions();
    $this->entityTypeManager->getStorage('group_content_type')->createFromPlugin(
      $group_type,
      $plugin_id,
      []
    )->save();

    // Grant permissions and create an outsider user.
    $permission_provider = $this->container->get('plugin.manager.group_content_enabler')->getPermissionProvider($plugin_id);

    $role = $group_type->getMemberRole();
    $role->grantPermissions([
      $permission_provider->getPermission('view', 'entity', 'any'),
      $permission_provider->getPermission('view', 'relation', 'any'),
      $permission_provider->getPermission('view unpublished', 'entity', 'any'),
    ]);
    $role->save();

    $role = $group_type->getAnonymousRole();
    $role->grantPermissions([
      $permission_provider->getPermission('view', 'entity', 'any'),
      $permission_provider->getPermission('view', 'relation', 'any'),
      'view group',
    ]);
    $role->save();
    $outsider = $this->drupalCreateUser(['access content']);
    $group->addMember($outsider, [
      'group_roles' => [$group_type->getOutsiderRoleId()],
    ]);

    // Define test cases as a numerical array, each case being an array
    // containing node status and relation status keys.
    $cases = [
      ['status' => 0, 'relation_status' => 0],
      ['status' => 1, 'relation_status' => 0],
      ['status' => 0, 'relation_status' => 1],
      ['status' => 1, 'relation_status' => 1],
    ];

    // Create test nodes and add them to the test group.
    $time = $this->container->get('datetime.time')->getRequestTime();
    foreach ($cases as $i => $values) {
      $time -= $i;
      $node = $this->drupalCreateNode([
        'type' => $node_type_id,
        'title' => 'Title ' . $i,
        'sticky' => FALSE,
        'created' => $time,
        'changed' => $time,
        'status' => $values['status'],
      ]);

      $group->addContent($node, $plugin_id, ['status' => $values['relation_status']]);
    }

    // Check with the current user with permissions - all content should be
    // accessible.
    $assert_session = $this->assertSession();

    foreach ($group->getContent('group_node:article') as $relation) {
      $this->drupalGet($relation->toUrl()->toString());
      $assert_session->statusCodeEquals(200);
    }

    // Check content access for anonymous user, content should be accessible
    // only if both the relation and the node is published.
    $this->drupalLogout();
    foreach ($group->getContent('group_node:article') as $relation) {
      $expected_code = 403;
      if ($relation->get('status')->value && $relation->getEntity()->isPublished()) {
        $expected_code = 200;
      }
      $this->drupalGet($relation->toUrl()->toString());
      $assert_session->statusCodeEquals($expected_code);
    }

  }

}
