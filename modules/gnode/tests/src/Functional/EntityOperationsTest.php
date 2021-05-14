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
  public static $modules = ['gnode'];

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

    $cases = [
      ['status' => 0, 'relation_status' => 0],
      ['status' => 1, 'relation_status' => 0],
      // For now we skip the case where node is unpublished and relation
      // published as group currently displays an empty page instead of
      // 403 in such a case.
      ['status' => 1, 'relation_status' => 1],
    ];

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
        // We have to provide body as group content with access denied
        // displays an empty page with just a title by default.
        'body' => [
          [
            'value' => "Node $i body",
            'format' => filter_default_format(),
          ],
        ],
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
