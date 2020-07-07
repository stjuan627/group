<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests that Group properly checks access for "complex" grouped entities.
 *
 * By complex entities we mean entities that can be published or unpublished and
 * have a way of determining who owns the entity. This leads to far more complex
 * query alters as we need to take ownership and publication state into account.
 *
 * Until Entity API commits https://www.drupal.org/project/entity/issues/3134160
 * we can only support 'view' operations. As soon as the above issue lands, we
 * should also test queries with operations other than 'view'.
 *
 * @todo Keep an eye on the above issue.
 *
 * @coversDefaultClass \Drupal\group\EventSubscriber\QueryAccessSubscriber
 * @group group
 */
class QueryAccessSubscriberComplexTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The node storage to use in testing.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

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

    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');

    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    $this->createNodeType(['type' => 'page']);

    $this->groupType = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);

    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->save($storage->createFromPlugin($this->groupType, 'node_as_content:page'));
  }

  /**
   * Tests that regular access checks still work.
   */
  public function testRegularAccess() {
    $node_1 = $this->createNode(['type' => 'page', 'uid' => $this->createUser()->id()]);
    $node_2 = $this->createNode(['type' => 'page']);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Regular node query access still works.');
  }

  /**
   * Tests that grouped nodes are properly hidden.
   */
  public function testGroupAccessWithoutPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Only the ungrouped node shows up.');
  }

  /**
   * Tests that grouped nodes are visible to members.
   */
  public function testGroupAccessWithMemberPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $this->groupType->getMemberRole()->grantPermission('administer node_as_content:page')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Members can see grouped nodes');
  }

  /**
   * Tests that grouped nodes are visible to non-members.
   */
  public function testGroupAccessWithNonMemberPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $this->groupType->getOutsiderRole()->grantPermission('administer node_as_content:page')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $this->createGroup(['type' => $this->groupType->id()]);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Outsiders can see grouped nodes');
  }

  /**
   * Tests the viewing of any published entities.
   */
  public function testViewAnyPublishedAccess() {
    $account = $this->createUser();
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $node_3 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);

    $this->groupType->getMemberRole()->grantPermission('view any node_as_content:page entity')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $group->addContent($node_3, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());
    $group->addMember($account);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id(), $node_3->id()], array_keys($ids), 'Members can see any published nodes.');

    $this->setCurrentUser($account);
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id(), $node_3->id()], array_keys($ids), 'Members can see any published nodes.');

    $this->setCurrentUser($this->createUser());
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Only the ungrouped published node shows up.');
  }

  /**
   * Tests the viewing of own published entities.
   */
  public function testViewOwnPublishedAccess() {
    $account = $this->createUser();
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $node_3 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);

    $this->groupType->getMemberRole()->grantPermission('view own node_as_content:page entity')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $group->addContent($node_3, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());
    $group->addMember($account);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Members can see their own published nodes.');

    $this->setCurrentUser($account);
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id(), $node_3->id()], array_keys($ids), 'Members can see their own published nodes.');

    $this->setCurrentUser($this->createUser());
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Only the ungrouped published node shows up.');
  }

  /**
   * Tests the viewing of any unpublished entities.
   */
  public function testViewAnyUnpublishedAccess() {
    $account = $this->createUser();
    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0, 'uid' => $account->id()]);

    $this->groupType->getMemberRole()->grantPermission('view any unpublished node_as_content:page entity')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $group->addContent($node_3, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());
    $group->addMember($account);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id(), $node_3->id()], array_keys($ids), 'Members can see any unpublished nodes.');

    $this->setCurrentUser($account);
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id(), $node_3->id()], array_keys($ids), 'Members can see any unpublished nodes.');

    // This is actually a core issue, but for now unpublished nodes show up in
    // entity queries when there are no node grants defining modules.
    $this->setCurrentUser($this->createUser());
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Only the ungrouped unpublished node shows up.');
  }

  /**
   * Tests the viewing of own unpublished entities.
   */
  public function testViewOwnUnpublishedAccess() {
    $account = $this->createUser();
    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0, 'uid' => $account->id()]);

    $this->groupType->getMemberRole()->grantPermission('view own unpublished node_as_content:page entity')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $group->addContent($node_3, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());
    $group->addMember($account);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Members can see their own unpublished nodes.');

    $this->setCurrentUser($account);
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id(), $node_3->id()], array_keys($ids), 'Members can see their own unpublished nodes.');

    // This is actually a core issue, but for now unpublished nodes show up in
    // entity queries when there are no node grants defining modules.
    $this->setCurrentUser($this->createUser());
    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Only the ungrouped unpublished node shows up.');
  }

  /**
   * Tests the viewing of own unpublished entities.
   */
  public function testAdminAccess() {
    $account = $this->createUser();
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page']);
    $node_4 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_5 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_6 = $this->createNode(['type' => 'page', 'status' => 0, 'uid' => $account->id()]);

    $this->groupType->getMemberRole()->grantPermission('administer node_as_content:page')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_3, 'node_as_content:page');
    $group->addContent($node_4, 'node_as_content:page');
    $group->addContent($node_5, 'node_as_content:page');
    $group->addContent($node_6, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());

    $ids = $this->nodeStorage->getQuery()->execute();
    $expected = [
      $node_1->id(),
      $node_2->id(),
      $node_3->id(),
      $node_4->id(),
      $node_5->id(),
      $node_6->id(),
    ];
    $this->assertEquals($expected, array_keys($ids), 'Admin member can see anything regardless of owner or published status.');
  }

  /**
   * Tests that adding new group content clears caches.
   */
  public function testNewGroupContent() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $this->groupType->getMemberRole()->grantPermission('view any node_as_content:page entity')->save();
    $group = $this->createGroup(['type' => $this->groupType->id()]);

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Outsiders can see ungrouped nodes');

    // This should clear the cache.
    $group->addContent($node_1, 'node_as_content:page');

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Outsiders can see ungrouped nodes');
  }

  /**
   * Tests that adding new permissions clears caches.
   *
   * This is actually tested in the permission calculator, but added here also
   * for additional hardening. It does not really clear the cached conditions,
   * but rather return a different set as your user.group_permissions cache
   * context value changes.
   *
   * We will not test any further scenarios that trigger a change in your group
   * permissions as those are -as mentioned above- tested elsewhere. It just
   * seemed like a good idea to at least test one scenario here.
   */
  public function testNewPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $group = $this->createGroup(['type' => $this->groupType->id()]);
    $group->addContent($node_1, 'node_as_content:page');
    $group->addMember($this->getCurrentUser());

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_2->id()], array_keys($ids), 'Members can only see ungrouped nodes');

    // This should trigger a different set of conditions.
    $this->groupType->getMemberRole()->grantPermission('view any node_as_content:page entity')->save();

    $ids = $this->nodeStorage->getQuery()->execute();
    $this->assertEquals([$node_1->id(), $node_2->id()], array_keys($ids), 'Outsiders can see grouped nodes');
  }

  /**
   * Creates a node.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node entity.
   */
  protected function createNode(array $values = []) {
    $node = $this->nodeStorage->create($values + [
      'title' => $this->randomString(),
    ]);
    $node->enforceIsNew();
    $this->nodeStorage->save($node);
    return $node;
  }

  /**
   * Creates a node type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The created node type entity.
   */
  protected function createNodeType(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('node_type');
    $node_type = $storage->create($values + [
      'type' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($node_type);
    return $node_type;
  }

}
