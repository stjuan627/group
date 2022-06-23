<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Tests\group\Traits\NodeTypeCreationTrait;

/**
 * Tests for the ConfigWrapper entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\ConfigWrapper
 */
class ConfigWrapperTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test', 'node'];

  /**
   * Tests the wrapped config entity getter.
   *
   * @covers ::getConfigEntity
   */
  public function testGetConfigEntity() {
    $node_type = $this->createNodeType();
    $wrapper = $this->createConfigWrapper(['bundle' => 'node_type', 'entity_id' => $node_type->id()]);
    $wrapped = $wrapper->getConfigEntity();

    $this->assertEquals($node_type->id(), $wrapped->id());
    $this->assertEquals($node_type->getEntityTypeId(), $wrapped->getEntityTypeId());
  }

  /**
   * Tests the wrapped config entity ID getter.
   *
   * @covers ::testGetConfigEntityId
   */
  public function testGetConfigEntityId() {
    $node_type = $this->createNodeType();
    $wrapper = $this->createConfigWrapper(['bundle' => 'node_type', 'entity_id' => $node_type->id()]);
    $this->assertEquals($node_type->id(), $wrapper->getConfigEntityId());
  }

  /**
   * Creates a config wrapper.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\ConfigWrapper
   *   The created config wrapper entity.
   */
  protected function createConfigWrapper(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group_config_wrapper');
    $wrapper = $storage->create($values);
    $storage->save($wrapper);
    return $wrapper;
  }

}
