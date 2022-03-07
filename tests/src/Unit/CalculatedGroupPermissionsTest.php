<?php

namespace Drupal\Tests\group\Unit;

use Drupal\group\Access\CalculatedGroupPermissions;
use Drupal\group\Access\CalculatedGroupPermissionsInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CalculatedGroupPermissions value object.
 *
 * @coversDefaultClass \Drupal\group\Access\CalculatedGroupPermissions
 * @group group
 */
class CalculatedGroupPermissionsTest extends UnitTestCase {

  /**
   * Tests that the object values were set in the constructor.
   *
   * @covers ::__construct
   * @covers ::getItem
   * @covers ::getItems
   * @covers ::getItemsByScope
   */
  public function testConstructor() {
    $item_a = new CalculatedGroupPermissionsItem('scope_a', 'foo', ['baz']);
    $item_b = new CalculatedGroupPermissionsItem('scope_b', 1, ['bob', 'charlie']);

    $calculated_permissions = $this->prophesize(CalculatedGroupPermissionsInterface::class);
    $calculated_permissions->getItems()->willReturn([$item_a, $item_b]);
    $calculated_permissions->getCacheTags()->willReturn(['24']);
    $calculated_permissions->getCacheContexts()->willReturn(['Oct']);
    $calculated_permissions->getCacheMaxAge()->willReturn(1986);
    $calculated_permissions = new CalculatedGroupPermissions($calculated_permissions->reveal());

    $this->assertSame($item_a, $calculated_permissions->getItem('scope_a', 'foo'), 'Managed to retrieve the calculated permissions item by scope and identifier.');
    $this->assertFalse($calculated_permissions->getItem('scope_a', '404-id-not-found'), 'Requesting a non-existent identifier fails correctly.');
    $this->assertSame([$item_a, $item_b], $calculated_permissions->getItems(), 'Successfully retrieved all items regardless of scope.');
    $this->assertSame([$item_a], $calculated_permissions->getItemsByScope('scope_a'), 'Successfully retrieved all items by scope.');

    $this->assertSame(['24'], $calculated_permissions->getCacheTags(), 'Successfully inherited all cache tags.');
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'All cache contexts were cleared so they do not bubble up.');
    $this->assertSame(1986, $calculated_permissions->getCacheMaxAge(), 'Successfully inherited cache max-age.');
  }

}
