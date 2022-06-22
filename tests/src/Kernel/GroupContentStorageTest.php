<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;

/**
 * Tests the behavior of group content storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\GroupContentStorage
 * @group group
 */
class GroupContentStorageTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin'];

  /**
   * The group content storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected $storage;

  /**
   * The group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->entityTypeManager->getStorage('group_content');
    $this->groupType = $this->createGroupType();

    // Enable the test plugins on a test group type.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    $storage->createFromPlugin($this->groupType, 'user_as_content')->save();
    $storage->createFromPlugin($this->groupType, 'group_as_content')->save();
  }

  /**
   * Creates an unsaved group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createUnsavedGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => $this->groupType->id(),
      'label' => $this->randomMachineName(),
    ]);
    return $group;
  }

  /**
   * Creates an unsaved user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUnsavedUser($values = []) {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    return $account;
  }

  /**
   * Tests the creation of a GroupContent entity using an unsaved group.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $account = $this->createUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot add an entity to an unsaved group.');
    $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an unsaved entity.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForUnsavedEntity() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $account = $this->createUnsavedUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot add an unsaved entity to a group.');
    $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an incorrect plugin ID.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForInvalidPluginId() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $account = $this->createUser();

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Invalid plugin provided for adding the entity to the group.');
    $this->storage->createForEntityInGroup($account, $group, 'group_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an incorrect bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateForInvalidBundle() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $subgroup = $this->createGroup(['type' => $this->createGroupType()->id()]);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage("The provided plugin provided does not support the entity's bundle.");
    $this->storage->createForEntityInGroup($subgroup, $group, 'group_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using a bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithBundle() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $subgroup = $this->createGroup(['type' => $this->createGroupType(['id' => 'default'])->id()]);
    $group_content = $this->storage->createForEntityInGroup($subgroup, $group, 'group_as_content');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupContentInterface', $group_content, 'Created a GroupContent entity using a bundle-specific plugin.');
  }

  /**
   * Tests the creation of a GroupContent entity using no bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithoutBundle() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $account = $this->createUser();
    $group_content = $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupContentInterface', $group_content, 'Created a GroupContent entity using a bundle-independent plugin.');
  }

  /**
   * Tests the loading of GroupContent entities for an unsaved group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $this->assertSame([], $this->storage->loadByGroup($group));
  }

  /**
   * Tests the loading of GroupContent entities for a group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByGroup() {
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $this->assertCount(1, $this->storage->loadByGroup($group), 'Managed to load the group creator membership by group.');
  }

  /**
   * Tests the loading of GroupContent entities for an unsaved entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByUnsavedEntity() {
    $group = $this->createUnsavedGroup();
    $this->assertSame([], $this->storage->loadByEntity($group));
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByEntity() {
    $group_a = $this->createGroup(['type' => $this->groupType->id()]);
    $group_b = $this->createGroup(['type' => $this->createGroupType(['id' => 'default'])->id()]);
    $account = $this->getCurrentUser();

    // Both entities should have ID 2 to test
    $this->assertSame($group_b->id(), $account->id());

    // Add the group as content so we can verify only the user is returned.
    $group_a->addContent($group_b, 'group_as_content');
    $this->assertCount(2, $this->storage->loadByEntity($account), 'Managed to load the group creator memberships by user.');
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @covers ::loadByPluginId
   */
  public function testLoadByPluginId() {
    $this->createGroup(['type' => $this->groupType->id()]);
    $this->assertCount(1, $this->storage->loadByPluginId('group_membership'), 'Managed to load the group creator membership by plugin ID.');
  }

}
