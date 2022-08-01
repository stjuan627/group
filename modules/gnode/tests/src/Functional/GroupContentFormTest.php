<?php

namespace Drupal\Tests\gnode\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 *
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
   * A test group type.
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
    $this->nodeType = $this->createContentType([
      'type' => self::NODE_TYPE_BUNDLE,
    ]);
    $this->groupType = $this->entityTypeManager->getStorage('group_type')->load('default');
    $this->groupContentType = $this->entityTypeManager->getStorage('group_content_type')
      ->createFromPlugin($this->groupType, 'group_node:' . self::NODE_TYPE_BUNDLE)
      ->save();
    $this->group = $this->createGroup([
      'uid' => $this->groupCreator->id(),
    ]);
    $this->groupMember = $this->drupalCreateUser([
      'create ' . self::NODE_TYPE_BUNDLE . ' content',
    ]);
    $this->group->addMember($this->groupMember);
    $this->memberRole = $this->entityTypeManager->getStorage('group_role')->load($this->group->bundle() . '-member');

    // Rebuild the routes since gnode module has been enabled before we created
    // our content type, the route to create an entity of this content type
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

    // Check that member with "create group_node:bundle content" permission
    // can't access the form.
    $this->memberRole->revokePermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' content');
    $this->memberRole->save();
    $this->drupalGet($gnode_create_url);
    $this->assertSession()->statusCodeEquals(403);

    // Check that member with both permissions (create and attach group content)
    // can access the form.
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->save();
    $this->drupalGet($gnode_create_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');
  }

  /**
   * Test attach group content form access.
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

    // Check that member with "create group_node:bundle content" permission
    // can access the form.
    $this->memberRole->revokePermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' content');
    $this->memberRole->save();
    $this->drupalGet($gnode_add_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');

    // Check that member with both permissions (create and attach group content)
    // can access the form.
    $this->memberRole->grantPermission('create group_node:' . self::NODE_TYPE_BUNDLE . ' entity');
    $this->memberRole->save();
    $this->drupalGet($gnode_add_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Save');
  }

}
