<?php

namespace Drupal\Tests\group\Functional;

/**
 * Tests the group creator wizard.
 *
 * @group group
 */
class GroupCreatorWizardTest extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpAccount();
  }

  /**
   * Tests that a group creator gets a membership using the wizard.
   */
  public function testCreatorMembershipWizard() {
    $group_type = $this->createGroupType();
    $group_type_id = $group_type->id();

    $role = $this->drupalCreateRole(["create $group_type_id group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    $this->drupalGet("/group/add/$group_type_id");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create ' . $group_type->label() . ' and complete your membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    $submit_button = 'Save group and membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Back');

    // Submit the form
    $this->submitForm([], $submit_button);
    $this->assertSession()->statusCodeEquals(200);

    // Get the group
    $all_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    $this->assertCount(1, $all_groups);
    $group = reset($all_groups);

    // Check for the membership.
    $group_relationship_storage = $this->entityTypeManager->getStorage('group_relationship');
    $creator_relationships = $group_relationship_storage->loadByEntity($this->groupCreator, 'group_membership');

    // Check the count equals one.
    $this->assertCount(1, $creator_relationships, 'There is just one membership');

    // Check the belonging group of that membership
    $creator_relationship = reset($creator_relationships);
    $this->assertEquals($creator_relationship->getGroupId(), $group->id(), 'Membership belongs to the group');
  }

  /**
   * Tests that a group creator gets a membership without using the wizard.
   */
  public function testCreatorMembershipNoWizard() {
    $group_type = $this->createGroupType(['creator_wizard' => FALSE]);
    $group_type_id = $group_type->id();

    $role = $this->drupalCreateRole(["create $group_type_id group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    $this->drupalGet("/group/add/$group_type_id");
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create ' . $group_type->label() . ' and become a member';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonNotExists('Cancel');
  }

  /**
   * Tests that a group form is not turned into a wizard.
   */
  public function testNoWizard() {
    $group_type = $this->createGroupType([
      'creator_membership' => FALSE,
      'creator_wizard' => FALSE,
    ]);
    $group_type_id = $group_type->id();

    $role = $this->drupalCreateRole(["create $group_type_id group"]);
    $this->groupCreator->addRole($role);
    $this->groupCreator->save();

    $this->drupalGet("/group/add/$group_type_id");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Create ' . $group_type->label());
    $this->assertSession()->buttonNotExists('Cancel');
  }

}
