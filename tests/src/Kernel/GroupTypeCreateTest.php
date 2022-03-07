<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Tests the creation of group type entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeCreateTest extends GroupKernelTestBase {

  /**
   * Tests special behavior during group type creation.
   *
   * @covers ::postSave
   */
  public function testCreate() {
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $group_content_type_storage */
    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $this->assertCount(0, $group_content_type_storage->loadByEntityTypeId('user'));

    // Check that the group type was created and saved properly.
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->create([
        'id' => 'dummy',
        'label' => 'Dummy',
        'description' => $this->randomMachineName(),
      ]);

    $this->assertInstanceOf(GroupTypeInterface::class, $group_type);
    $this->assertEquals(SAVED_NEW, $group_type->save(), 'Group type was saved successfully.');

    // Check that enforced plugins were installed.
    $this->assertCount(1, $group_content_type_storage->loadByEntityTypeId('user'));
    $group_content_type = $group_content_type_storage->load(
      $group_content_type_storage->getGroupContentTypeId($group_type->id(), 'group_membership')
    );
    $this->assertNotNull($group_content_type, 'Enforced plugins were installed on the group type.');
  }

}
