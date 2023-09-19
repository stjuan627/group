<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\PermissionScopeInterface;

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
    $group_type = $this->createGroupTypeAndRole();

    $group_role = $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);
    $group_type->set('creator_roles', [$group_role->id()]);
    $group_type->save();

    $submit_button = 'Create ' . $group_type->label() . ' and complete your membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    $submit_button = 'Save group and membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Back');

    // Submit the membership form
    $this->submitForm([], $submit_button);
    $this->assertSession()->statusCodeEquals(200);

    // Get the group
    $all_groups = $this->entityTypeManager->getStorage('group')->loadMultiple();
    $this->assertCount(1, $all_groups);
    $group = reset($all_groups);

    // Check there is just one membership
    $membership_ids = $this->loadGroupMembership($group, $this->groupCreator);
    $this->assertCount(1, $membership_ids, 'Wizard set just one membership');

    // Check that the roles assigned to the created member are the same as what we configured in the group defaults
    $membership = $group->getMember($this->groupCreator);
    $ids = [];
    foreach ($membership->getGroupRelationship()->group_roles as $group_role_ref) {
      $ids[] = $group_role_ref->target_id;
    }
    $this->assertEquals($group_type->getCreatorRoleIds(), $ids, 'Wizard set the correct creator roles');
  }

  /**
   * Tests that a group creator gets a membership without using the wizard.
   */
  public function testCreatorMembershipNoWizard() {
    $group_type = $this->createGroupTypeAndRole(FALSE);

    $submit_button = 'Create ' . $group_type->label() . ' and become a member';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonNotExists('Cancel');
  }

  /**
   * Tests that a group form is not turned into a wizard.
   */
  public function testNoWizard() {
    $group_type = $this->createGroupTypeAndRole(FALSE, FALSE);

    $this->assertSession()->buttonExists('Create ' . $group_type->label());
    $this->assertSession()->buttonNotExists('Cancel');
  }

  /**
   * Create group type and role with creation rights.
   *
   * @param bool $creator_wizard
   *   The group creator must immediately complete their membership.
   * @param bool $creator_membership
   *   The group creator automatically receives a membership.
   *
   * @return \Drupal\group\Entity\GroupType
   *   Group type.
   */
  protected function createGroupTypeAndRole($creator_wizard = TRUE, $creator_membership = TRUE) {
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

    return $group_type;
  }

  /**
   * Membership array of a user in a group.
   *
   * @param GroupInterface $group
   *   The group used to get the memberships.
   * @param AccountInterface $account
   *   The user account used to get the memberships.
   *
   * @return array|int
   *   The memberships ids array.
   */
  protected function loadGroupMembership(GroupInterface $group, AccountInterface $account) {
    $storage = $this->entityTypeManager->getStorage('group_relationship');

    return $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership')
      ->execute();
  }

}
