<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;

/**
 * Tests for the GroupContent entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupContent
 */
class GroupContentTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test', 'group_test_plugin', 'node'];

  /**
   * Tests that entity url templates are functional.
   *
   * @covers ::urlRouteParameters
   */
  public function testUrlRouteParameters() {
    $group_type = $this->createGroupType();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $account = $this->createUser();
    $group->addMember($account);
    $group_content = $group->getMember($account)->getGroupContent();

    // Canonical.
    $expected = "/group/{$group->id()}/content/{$group_content->id()}";
    $this->assertEquals($expected, $group_content->toUrl()->toString());

    // Add form.
    $expected = "/group/{$group->id()}/content/add/group_membership";
    $this->assertEquals($expected, $group_content->toUrl('add-form')->toString());

    // Add page.
    $expected = "/group/{$group->id()}/content/add";
    $this->assertEquals($expected, $group_content->toUrl('add-page')->toString());

    // Collection.
    $expected = "/group/{$group->id()}/content";
    $this->assertEquals($expected, $group_content->toUrl('collection')->toString());

    // Create form.
    $expected = "/group/{$group->id()}/content/create/group_membership";
    $this->assertEquals($expected, $group_content->toUrl('create-form')->toString());

    // Create page.
    $expected = "/group/{$group->id()}/content/create";
    $this->assertEquals($expected, $group_content->toUrl('create-page')->toString());

    // Delete form.
    $expected = "/group/{$group->id()}/content/{$group_content->id()}/delete";
    $this->assertEquals($expected, $group_content->toUrl('delete-form')->toString());

    // Edit form.
    $expected = "/group/{$group->id()}/content/{$group_content->id()}/edit";
    $this->assertEquals($expected, $group_content->toUrl('edit-form')->toString());
  }

  /**
   * Tests that after adding an entity to a group, it gets saved again.
   *
   * @covers ::postSave
   *
   * @see group_test_user_update()
   */
  public function testSubjectResaved() {
    $changed = 123456789;
    $account = $this->createUser(['changed' => $changed]);

    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);
    $group->addContent($account, 'group_membership');

    // All users whose changed time was set to 123456789 get their changed time
    // set to 530496000 in group_test_user_update() when the account is updated.
    $account_unchanged = $this->entityTypeManager->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(530496000, $account_unchanged->getChangedTime(), 'Account was saved as part of being added to a group.');
  }

  /**
   * Tests the retrieval of the grouped entity.
   */
  public function testGetEntity() {
    // Create a group type and enable adding users and node types as content.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    $storage->createFromPlugin($group_type, 'user_as_content')->save();
    $storage->createFromPlugin($group_type, 'node_type_as_content')->save();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $account = $this->createUser();
    $group_content = $group->addRelationship($account, 'user_as_content');
    $this->assertEquals($account->id(), $group_content->getEntity()->id());
    $this->assertEquals('user', $group_content->getEntity()->getEntityTypeId());

    $node_type = $this->createNodeType();
    $group_content = $group->addRelationship($node_type, 'node_type_as_content');
    $this->assertEquals('node_type', $group_content->getEntity()->getEntityTypeId());
    $this->assertEquals($node_type->id(), $group_content->getEntity()->id());
  }

  /**
   * Tests that custom list cache tags are properly invalidated.
   *
   * @covers ::getListCacheTagsToInvalidate
   */
  public function testListCacheTagInvalidation() {
    $cache = \Drupal::cache();

    // Create a group type and enable adding users as content.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    $storage->createFromPlugin($group_type, 'user_as_content')->save();

    // Create a group and user to check the cache tags for.
    $test_group = $this->createGroup(['type' => $group_type->id()]);
    $test_group_id = $test_group->id();
    $test_account = $this->createUser();
    $test_account_id = $test_account->id();

    // Create an extra group and account to test with.
    $extra_group = $this->createGroup(['type' => $group_type->id()]);
    $extra_account = $this->createUser();

    $scenarios = [
      // Create a list for specific group, any entity, any plugin.
      'group_content' => ["group_content_list:group:$test_group_id"],
      // Create a list for any group, specific entity, any plugin.
      'content_groups' => ["group_content_list:entity:$test_account_id"],
      // Create a list for any group, any entity, specific plugin.
      'all_memberships' => ["group_content_list:plugin:group_membership"],
      // Create a list for specific group, any entity, specific plugin.
      'group_memberships' => ["group_content_list:plugin:group_membership:group:$test_group_id"],
      // Create a list for any group, specific entity, specific plugin.
      'user_memberships' => ["group_content_list:plugin:group_membership:entity:$test_account_id"],
    ];
    foreach ($scenarios as $cid => $cache_tags) {
      $cache->set($cid, 'foo', CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }

    // Add another user to another group and verify cache entries.
    $extra_group->addContent($extra_account, 'user_as_content');
    $this->assertNotFalse($cache->get('group_content'), 'List for specific group, any entity, any plugin found.');
    $this->assertNotFalse($cache->get('content_groups'), 'List for any group, specific entity, any plugin found.');
    $this->assertNotFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add another user as content to the group and verify cache entries.
    $test_group->addContent($extra_account, 'user_as_content');
    $this->assertFalse($cache->get('group_content'), 'List for specific group, any entity, any plugin cleared.');
    $this->assertNotFalse($cache->get('content_groups'), 'List for any group, specific entity, any plugin found.');
    $this->assertNotFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add the user as content to another group and verify cache entries.
    $extra_group->addContent($test_account, 'user_as_content');
    $this->assertFalse($cache->get('content_groups'), 'List for any group, specific entity, any plugin cleared.');
    $this->assertNotFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add any user as a member to any group and verify cache entries.
    $extra_group->addMember($extra_account);
    $this->assertFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin cleared.');
    $this->assertNotFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin found.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add any user as a member to the group and verify cache entries.
    $test_group->addMember($extra_account);
    $this->assertFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin cleared.');
    $this->assertNotFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin found.');

    // Add the user as a member to any group and verify cache entries.
    $extra_group->addMember($test_account);
    $this->assertFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin cleared.');

    // Set the cache again and verify if we add the user to the group.
    foreach ($scenarios as $cid => $cache_tags) {
      $cache->set($cid, 'foo', CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
    $test_group->addMember($test_account);
    $this->assertFalse($cache->get('group_content'), 'List for specific group, any entity, any plugin cleared.');
    $this->assertFalse($cache->get('content_groups'), 'List for any group, specific entity, any plugin cleared.');
    $this->assertFalse($cache->get('all_memberships'), 'List for any group, any entity, specific plugin cleared.');
    $this->assertFalse($cache->get('group_memberships'), 'List for specific group, any entity, specific plugin cleared.');
    $this->assertFalse($cache->get('user_memberships'), 'List for any group, specific entity, specific plugin cleared.');
  }

  /**
   * Tests that custom list cache tags are properly set for config entities.
   *
   * @covers ::getListCacheTagsToInvalidate
   * @depends testListCacheTagInvalidation
   */
  public function testGetListCacheTagsToInvalidateForConfig() {
    // Create a group type and enable adding node types as content.
    $group_type = $this->createGroupType();

    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    $storage->createFromPlugin($group_type, 'node_type_as_content')->save();

    $group = $this->createGroup(['type' => $group_type->id()]);
    $node_type = $this->createNodeType();

    $group_content = $group->addRelationship($node_type, 'node_type_as_content');
    $expected = [
      'group_content_list',
      'group_content_list:' . $group_content->bundle(),
      'group_content_list:group:' . $group->id(),
      'group_content_list:entity:' . $node_type->id(),
      'group_content_list:plugin:node_type_as_content',
      'group_content_list:plugin:node_type_as_content:group:' . $group->id(),
      'group_content_list:plugin:node_type_as_content:entity:' . $node_type->id(),
    ];
    // We can call this method because we made it public.
    assert($group_content instanceof GroupContent);
    $this->assertSame($expected, $group_content->getListCacheTagsToInvalidate());
  }

}
