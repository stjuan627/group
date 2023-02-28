<?php

namespace Drupal\Tests\group\Functional;

use Drupal\group\GroupMembership;

/**
 * Tests the behavior of the group type form.
 *
 * @group group
 */
class GroupTypeFormTest extends GroupBrowserTestBase {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The group type ID to use in testing.
   *
   * @var string
   */
  protected $groupTypeId = 'my_first_group_type';

  /**
   * Contains some common values for the group type form.
   *
   * @var array
   */
  protected $commonValues = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->commonValues = [
      'Name' => 'My first group type',
      'id' => $this->groupTypeId,
    ];
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'administer group',
    ] + parent::getGlobalPermissions();
  }

  /**
   * Sets up the group type add form and runs common assertions.
   *
   * @return string
   *   The submit button label.
   */
  protected function setUpAddFormAndGetSubmitButton() {
    $this->drupalGet('/admin/group/types/add');
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Save group type';
    $this->assertSession()->buttonExists($submit_button);
    return $submit_button;
  }

  /**
   * Tests changing the group title field label.
   */
  public function testCustomGroupTitleFieldLabel() {
    $title_field_label = 'Title for foo';
    $edit = ['Title field label' => $title_field_label] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $fields = $this->entityFieldManager->getFieldDefinitions('group', $this->groupTypeId);
    $this->assertEquals($title_field_label, $fields['label']->getLabel());
  }

  /**
   * Tests not granting the group creator a membership.
   */
  public function testNoCreatorMembership() {
    $edit = ['The group creator automatically becomes a member' => 0] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $group = $this->createGroup(['type' => $this->groupTypeId]);
    $this->assertEquals(\Drupal::currentUser()->id(), $group->getOwnerId());
    $this->assertFalse($group->getMember(\Drupal::currentUser()));
  }

  /**
   * Tests granting the group creator a membership.
   */
  public function testCreatorMembership() {
    $edit = ['The group creator automatically becomes a member' => 1] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $group = $this->createGroup(['type' => $this->groupTypeId]);
    $this->assertEquals(\Drupal::currentUser()->id(), $group->getOwnerId());
    $this->assertInstanceOf(GroupMembership::class, $group->getMember(\Drupal::currentUser()));
  }

  /**
   * Tests not creating the default roles.
   */
  public function testNoCreateDefaultRoles() {
    $edit = ['Automatically configure useful default roles' => 0] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNull($storage->load($this->groupTypeId . '-anonymous'));
    $this->assertNull($storage->load($this->groupTypeId . '-outsider'));
    $this->assertNull($storage->load($this->groupTypeId . '-member'));
  }

  /**
   * Tests creating the default roles.
   */
  public function testCreateDefaultRoles() {
    $edit = ['Automatically configure useful default roles' => 1] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNotNull($storage->load($this->groupTypeId . '-anonymous'));
    $this->assertNotNull($storage->load($this->groupTypeId . '-outsider'));
    $this->assertNotNull($storage->load($this->groupTypeId . '-member'));
  }

  /**
   * Tests not creating the admin role.
   */
  public function testNoCreateAdminRole() {
    $edit = ['Automatically configure an administrative role' => 0] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNull($storage->load($this->groupTypeId . '-admin'));
  }

  /**
   * Tests creating the admin role.
   */
  public function testCreateAdminRole() {
    $edit = ['Automatically configure an administrative role' => 1] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNotNull($storage->load($this->groupTypeId . '-admin'));
  }

  /**
   * Tests not assigning the admin role.
   *
   * @depends testCreatorMembership
   * @depends testCreateAdminRole
   */
  public function testNoAssignAdminRole() {
    $edit = [
      'The group creator automatically becomes a member' => 1,
      'Automatically configure an administrative role' => 1,
      'Automatically assign this administrative role to group creators' => 0,
    ] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $membership = $this->createGroup(['type' => $this->groupTypeId])->getMember(\Drupal::currentUser());

    $ids = [];
    foreach ($membership->getGroupRelationship()->group_roles as $group_role_ref) {
      $ids[] = $group_role_ref->target_id;
    }
    $this->assertNotContains($this->groupTypeId . '-admin', $ids);
  }

  /**
   * Tests assigning the admin role.
   *
   * @depends testCreatorMembership
   * @depends testCreateAdminRole
   */
  public function testAssignAdminRole() {
    $edit = [
      'The group creator automatically becomes a member' => 1,
      'Automatically configure an administrative role' => 1,
      'Automatically assign this administrative role to group creators' => 1,
    ] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $membership = $this->createGroup(['type' => $this->groupTypeId])->getMember(\Drupal::currentUser());

    $ids = [];
    foreach ($membership->getGroupRelationship()->group_roles as $group_role_ref) {
      $ids[] = $group_role_ref->target_id;
    }
    $this->assertContains($this->groupTypeId . '-admin', $ids);
  }

  /**
   * Tests that the presence of a global admin role makes new options show up.
   */
  public function testGlobalAdminRoleDetection() {
    $this->setUpAddFormAndGetSubmitButton();
    $this->assertSession()->pageTextNotContains('We have detected that your site has an all-access global role called');

    $this->createAdminRole();

    $this->setUpAddFormAndGetSubmitButton();
    $this->assertSession()->pageTextContains('We have detected that your site has an all-access global role called');
  }

  /**
   * Tests not creating the admin outsider role.
   *
   * @depends testGlobalAdminRoleDetection
   */
  public function testNoOutsiderAdminRoleCreation() {
    $this->createAdminRole();
    $edit = ['add_admin_outsider' => 0] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNull($storage->load($this->groupTypeId . '-admin_out'));
  }

  /**
   * Tests creating the admin outsider role.
   *
   * @depends testGlobalAdminRoleDetection
   */
  public function testOutsiderAdminRoleCreation() {
    $this->createAdminRole();
    $edit = ['add_admin_outsider' => 1] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNotNull($storage->load($this->groupTypeId . '-admin_out'));
  }

  /**
   * Tests not creating the admin insider role.
   *
   * @depends testGlobalAdminRoleDetection
   */
  public function testNoInsiderAdminRoleCreation() {
    $this->createAdminRole();
    $edit = ['add_admin_insider' => 0] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNull($storage->load($this->groupTypeId . '-admin_in'));
  }

  /**
   * Tests creating the admin insider role.
   *
   * @depends testGlobalAdminRoleDetection
   */
  public function testInsiderAdminRoleCreation() {
    $this->createAdminRole();
    $edit = ['add_admin_insider' => 1] + $this->commonValues;
    $this->submitForm($edit, $this->setUpAddFormAndGetSubmitButton());

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->assertNotNull($storage->load($this->groupTypeId . '-admin_in'));
  }

}
