<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;

/**
 * Tests the behavior of group config wrapper storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\ConfigWrapperStorage
 * @group group
 */
class ConfigWrapperStorageTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The group config wrapper storage handler.
   *
   * @var \Drupal\group\Entity\Storage\ConfigWrapperStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->storage = $this->entityTypeManager->getStorage('group_config_wrapper');

    // Install the node type handling plugin on a group type.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->createGroupType(), 'node_type_as_content'));
  }

  /**
   * Tests the creation of a ConfigWrapper entity.
   *
   * @covers ::wrapEntity
   */
  public function testWrapEntity() {
    $node_type = $this->createNodeType();
    $wrapper = $this->storage->wrapEntity($node_type);
    $this->assertSame($node_type->id(), $wrapper->getConfigEntityId());
  }

  /**
   * Tests that nothing is wrapped if flag is set to only load.
   *
   * @covers ::wrapEntity
   * @depends testWrapEntity
   */
  public function testWrapEntityNoCreate() {
    $node_type = $this->createNodeType();
    $this->assertFalse($this->storage->wrapEntity($node_type, FALSE));
    $this->storage->wrapEntity($node_type);
    $this->assertNotFalse($this->storage->wrapEntity($node_type, FALSE));
  }

  /**
   * Tests the loading of a ConfigWrapper entity.
   *
   * @covers ::wrapEntity
   * @depends testWrapEntity
   */
  public function testWrapWrappedEntity() {
    $node_type = $this->createNodeType();
    $wrapper_a = $this->storage->wrapEntity($node_type);
    $wrapper_b = $this->storage->wrapEntity($node_type);
    $this->assertSame($wrapper_a->id(), $wrapper_b->id());
  }

}
