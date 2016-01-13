<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\EntityReferenceSelection\GroupMembershipRoleSelection.
 */

namespace Drupal\group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the comment entity type.
 *
 * @EntityReferenceSelection(
 *   id = "group_membership_role",
 *   label = @Translation("Group membership role selection"),
 *   entity_types = {"group_role"},
 *   group = "group_type",
 *   weight = 1
 * )
 */
class GroupMembershipRoleSelection extends DefaultSelection {

}
