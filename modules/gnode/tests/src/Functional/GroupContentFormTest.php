<?php

namespace Drupal\Tests\gnode\Functional;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Test that a group member can create / attach content to the group.
 *
 * @group gnode
 */
class GroupContentFormTest extends GroupBrowserTestBase {

  const NODE_TYPE_BUNDLE = 'default_nodetype';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'group',
    'gnode',
  ];

  /**
   * A test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * A test group content type.
   *
   * @var \Drupal\group\Entity\GroupContentTypeInterface
   */
  protected $groupContentType;

  /**
   * A test group type.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * A test user member of our test group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupMember;

  /**
   * The group member role.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected $memberRole;

  /**
   * A test node type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a node type.
    $this->nodeType = $this->createContentType([
      'type' => self::NODE_TYPE_BUNDLE,
    ]);

    // Create a group type.
    $this->groupType = $this->createGroupType([
      'creator_membership' => TRUE,
    ]);

    // Enable the node type we created as an available group content type
    // for our group type.
    $this->groupContentType = $this->entityTypeManager->getStorage('group_content_type')
      ->createFromPlugin($this->groupType, 'group_node:' . self::NODE_TYPE_BUNDLE)
      ->save();

    // Create a group.
    $this->group = $this->createGroup([
      'uid' => $this->groupCreator->id(),
      'type' => $this->groupType->id(),
    ]);

    // Create a group role for the group members.
    $this->memberRole = $this->createGroupRole([
      'group_type' => $this->group->bundle(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
    ]);

    // Create a user and add him to the group as a member.
    $this->groupMember = $this->drupalCreateUser([
      'create ' . self::NODE_TYPE_BUNDLE . ' content',
    ]);
    $this->group->addMember($this->groupMember);

    // Rebuild the routes since gnode module has been enabled before our content
    // type has been created, the route to create an entity of this content type
    // in the group doesn't exist yet.
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Test create group content form access.
   */
  public function testCreateGroupContentFormAccess() {
    $this->drupalLogin($this->groupMember);
    $gnode_create_url = '/group/' . $this->group->id() . '/node/create';

    // Check that member with no permissions can't access the form.
    $this->drupalGet($gnode_create_url);
    $this->assertSession()->statusCodeEquals(403);

    // Check that member with "create group_node:bundle entity" permission
    // can access the form.
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->save();
    $this->drupalGet($gnode_create_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');

    // Check that member with "create group_node:bundle relationship" permission
    // can't access the form.
    $this->memberRole->revokePermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' relationship');
    $this->memberRole->save();
    $this->drupalGet($gnode_create_url);
    $this->assertSession()->statusCodeEquals(403);

    // Check that member with both permissions (create entity and create
    // relationship) can access the form.
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->save();
    $this->drupalGet($gnode_create_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');
  }

  /**
   * Test create group relationship form access.
   */
  public function testAttachGroupContentFormAccess() {
    $this->drupalLogin($this->groupMember);
    $gnode_add_url = '/group/' . $this->group->id() . '/node/add';

    // Check that member with no permissions can't access the form.
    $this->drupalGet($gnode_add_url);
    $this->assertSession()->statusCodeEquals(403);

    // Check that member with "create group_node:bundle entity" permission
    // can't access the form.
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->save();
    $this->drupalGet($gnode_add_url);
    $this->assertSession()->statusCodeEquals(403);

    // Check that member with "create group_node:bundle relationship" permission
    // can access the form.
    $this->memberRole->revokePermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' relationship');
    $this->memberRole->save();
    $this->drupalGet($gnode_add_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');

    // Check that member with both permissions (create entity and create
    // relationship) can access the form.
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->save();
    $this->drupalGet($gnode_add_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');
  }

}
