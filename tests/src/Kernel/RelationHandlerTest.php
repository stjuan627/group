<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests that relation handlers work as expected.
 *
 * @group group
 */
class RelationHandlerTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'group_test_plugin', 'group_test_plugin_alter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user', 'group_test_plugin']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('entity_test_with_owner');
    $this->installEntitySchema('node');
    $this->createNodeType(['type' => 'page']);
    $this->createNodeType(['type' => 'article']);
  }

  /**
   * Tests that decorators can target all plugins or one in specific.
   */
  public function testDecoratorChain() {
    /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationManagerInterface $relation_manager */
    $relation_manager = $this->container->get('plugin.manager.group_relation');

    $message = "All plugins have foobar appended, proving decorating defaults works and respects priority";
    $expected = 'administer user_as_content' . 'foobar';
    $this->assertSame($expected, $relation_manager->getPermissionProvider('user_as_content')->getAdminPermission(), $message);

    $message = "Node plugin also has baz appended, proving decoration_priority works separately for the default and specific service";
    $expected = 'administer node_as_content:page' . 'foobar' . 'baz';
    $this->assertSame($expected, $relation_manager->getPermissionProvider('node_as_content:page')->getAdminPermission(), $message);

    $message = "Test entity plugin also has bazfoo appended, proving decoration_priority is respected within specific alters";
    $expected = 'administer entity_test_as_content' . 'foobar' . 'bazfoo';
    $this->assertSame($expected, $relation_manager->getPermissionProvider('entity_test_as_content')->getAdminPermission(), $message);
  }

  /**
   * Creates a node type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The created node type entity.
   */
  protected function createNodeType(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('node_type');
    $node_type = $storage->create($values + [
        'type' => $this->randomMachineName(),
        'label' => $this->randomString(),
      ]);
    $storage->save($node_type);
    return $node_type;
  }

}
