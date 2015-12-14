<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnabler\GroupMembership.
 */

namespace Drupal\group\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for users.
 *
 * @GroupContentEnabler(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 *   description = @Translation("Adds users to groups as members."),
 *   entity_type_id = "user",
 *   enforced = TRUE
 * )
 */
class GroupMembership extends GroupContentEnablerBase {

}
