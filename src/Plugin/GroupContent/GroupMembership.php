<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContent\GroupMembership.
 */

namespace Drupal\group\Plugin\GroupContent;

use Drupal\group\Plugin\GroupContentBase;

/**
 * Provides a filter to align elements.
 *
 * @GroupContent(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 *   description = @Translation("Adds users to groups as members."),
 *   entity_type_id = "user"
 * )
 */
class GroupMembership extends GroupContentBase {

}
