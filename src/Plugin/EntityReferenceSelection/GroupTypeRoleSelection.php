<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\EntityReferenceSelection\GroupTypeRoleSelection.
 */

namespace Drupal\group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Only shows the group roles which are available for a group type.
 *
 * The only handler setting is 'group_type_id', a required string that points
 * to the ID of the group type for which this handler will be run.
 *
 * @EntityReferenceSelection(
 *   id = "group_type:group_role",
 *   label = @Translation("Group type role selection"),
 *   entity_types = {"group_role"},
 *   group = "group_type",
 *   weight = 0
 * )
 */
class GroupTypeRoleSelection extends DefaultSelection {

}
