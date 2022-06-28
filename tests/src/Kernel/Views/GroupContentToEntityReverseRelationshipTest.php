<?php

namespace Drupal\Tests\group\Kernel\Views;

/**
 * Tests the group_content_to_entity_reverse relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupContentToEntityReverse
 *
 * @group group
 */
class GroupContentToEntityReverseRelationshipTest extends GroupContentToEntityRelationshipTest {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_content_to_entity_reverse_relationship'];

}
