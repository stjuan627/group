<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\group\Access\RefinableCalculatedGroupPermissions;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the RefinableCalculatedGroupPermissions class.
 *
 * @coversDefaultClass \Drupal\group\Access\RefinableCalculatedGroupPermissions
 * @group group
 */
class RefinableCalculatedGroupPermissionsTest extends UnitTestCase {

  /**
   * Tests the addition of a calculated permissions item.
   *
   * @covers ::addItem
   * @covers ::getItem
   */
  public function testAddItem() {
    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $scope = 'some_scope';

    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['bar']);
    $calculated_permissions->addItem($item);
    $this->assertSame($item, $calculated_permissions->getItem($scope, 'foo'), 'Managed to retrieve the calculated permissions item.');

    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['baz']);
    $calculated_permissions->addItem($item);
    $this->assertEquals(['bar', 'baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Adding a calculated permissions item that was already in the list merges them.');

    $calculated_permissions->addItem($item, TRUE);
    $this->assertEquals(['baz'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Successfully overwrote an item that was already in the list.');

    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['cat'], TRUE);
    $calculated_permissions->addItem($item);
    $this->assertEquals([], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Merging in a calculated permissions item with admin rights empties the permissions.');
    $this->assertTrue($calculated_permissions->getItem($scope, 'foo')->isAdmin(), 'Merging in a calculated permissions item with admin rights flags the result as having admin rights.');
  }

  /**
   * Tests the removal of a calculated permissions item.
   *
   * @covers ::removeItem
   * @depends testAddItem
   */
  public function testRemoveItem() {
    $scope = 'some_scope';
    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['bar']);

    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $calculated_permissions
      ->addItem($item)
      ->removeItem($scope, 'foo');

    $this->assertFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not retrieve a removed item.');
  }

  /**
   * Tests the removal of all calculated permissions items.
   *
   * @covers ::removeItems
   * @depends testAddItem
   */
  public function testRemoveItems() {
    $scope = 'some_scope';
    $item = new CalculatedGroupPermissionsItem($scope, 'foo', ['bar']);

    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $calculated_permissions
      ->addItem($item)
      ->removeItems();

    $this->assertFalse($calculated_permissions->getItem($scope, 'foo'), 'Could not retrieve a removed item.');
  }

  /**
   * Tests the removal of calculated permissions items by scope.
   *
   * @covers ::removeItemsByScope
   * @depends testAddItem
   */
  public function testRemoveItemsByScope() {
    $scope_a = 'cat';
    $scope_b = 'dog';

    $item_a = new CalculatedGroupPermissionsItem($scope_a, 'foo', ['bar']);
    $item_b = new CalculatedGroupPermissionsItem($scope_b, 1, ['baz']);

    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $calculated_permissions
      ->addItem($item_a)
      ->addItem($item_b)
      ->removeItemsByScope($scope_a);

    $this->assertFalse($calculated_permissions->getItem($scope_a, 'foo'), 'Could not retrieve a removed item.');
    $this->assertNotFalse($calculated_permissions->getItem($scope_b, 1), 'Untouched scope item was found.');
  }

  /**
   * Tests merging in another CalculatedGroupPermissions object.
   *
   * @covers ::merge
   * @depends testAddItem
   */
  public function testMerge() {
    $scope = 'some_scope';

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());

    $item_a = new CalculatedGroupPermissionsItem($scope, 'foo', ['baz']);
    $item_b = new CalculatedGroupPermissionsItem($scope, 'foo', ['bob', 'charlie']);
    $item_c = new CalculatedGroupPermissionsItem($scope, 'bar', []);
    $item_d = new CalculatedGroupPermissionsItem($scope, 'baz', []);

    $calculated_permissions = new RefinableCalculatedGroupPermissions();
    $calculated_permissions
      ->addItem($item_a)
      ->addItem($item_c)
      ->addCacheContexts(['foo'])
      ->addCacheTags(['foo']);

    $other = new RefinableCalculatedGroupPermissions();
    $other
      ->addItem($item_b)
      ->addItem($item_d)
      ->addCacheContexts(['bar'])
      ->addCacheTags(['bar']);

    $calculated_permissions->merge($other);
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'bar'), 'Original item that did not conflict was kept.');
    $this->assertNotFalse($calculated_permissions->getItem($scope, 'baz'), 'Incoming item that did not conflict was added.');
    $this->assertSame(['baz', 'bob', 'charlie'], $calculated_permissions->getItem($scope, 'foo')->getPermissions(), 'Permissions were merged properly.');
    $this->assertEqualsCanonicalizing(['bar', 'foo'], $calculated_permissions->getCacheContexts(), 'Cache contexts were merged properly');
    $this->assertEqualsCanonicalizing(['bar', 'foo'], $calculated_permissions->getCacheTags(), 'Cache tags were merged properly');
  }

}
