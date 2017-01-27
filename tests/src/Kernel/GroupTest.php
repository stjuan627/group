<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the general behavior of group entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\Group
 * @group group
 */
class GroupTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['group', 'group_test_config'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installConfig(['group', 'group_test_config']);
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    $this->container->get('current_user')->setAccount($this->createUser());
    $this->group = $this->createGroup();
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    $group->enforceIsNew();
    $group->save();
    return $group;
  }

  /**
   * Tests the addition of a member to a group.
   *
   * @covers ::addMember
   */
  public function testAddMember() {
    $account = $this->createUser();
    $this->assertFalse($this->group->getMember($account), 'The user is not automatically member of the group.');
    $this->group->addMember($account);
    $this->assertNotFalse($this->group->getMember($account), 'Successfully added a member.');
  }

  /**
   * Tests the removal of a member from a group.
   *
   * @covers ::removeMember
   * @depends testAddMember
   */
  public function testRemoveMember() {
    $account = $this->createUser();
    $this->group->addMember($account);
    $this->group->removeMember($account);
    $this->assertFalse($this->group->getMember($account), 'Successfully removed a member.');
  }

}
