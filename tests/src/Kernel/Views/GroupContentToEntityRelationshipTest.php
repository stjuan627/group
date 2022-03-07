<?php

namespace Drupal\Tests\group\Kernel\Views;

/**
 * Tests the group_content_to_entity relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupContentToEntity
 *
 * @group group
 */
class GroupContentToEntityRelationshipTest extends GroupViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_content_to_entity_relationship'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);
    $this->installEntitySchema('node');

    // Enable the user_as_content plugin on the test group type.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($this->groupType, 'user_as_content')->save();
  }

  /**
   * Tests that a regular user is not returned by the view.
   */
  public function testRegularUserIsNotListed() {
    $this->createUser();
    $this->assertEquals(0, count($this->getViewResults()), 'The view does not show regular users.');
  }

  /**
   * Tests that a group's owner (default member) is returned by the view.
   */
  public function testGroupOwnerIsListed() {
    $this->createGroup();
    $this->assertEquals(1, count($this->getViewResults()), 'The view displays the user for the default member.');
  }

  /**
   * Tests that an extra group member is returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testAddedMemberIsListed() {
    $group = $this->createGroup();
    $group->addMember($this->createUser());
    $this->assertEquals(2, count($this->getViewResults()), 'The view displays the users for both the default and the added member.');
  }

  /**
   * Tests that any other group content is not returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testOtherContentIsNotListed() {
    $group = $this->createGroup();
    $group->addContent($this->createUser(), 'user_as_content');
    $this->assertEquals(1, count($this->getViewResults()), 'The view only displays the user for default member and not the one that was added as content.');
  }

}
