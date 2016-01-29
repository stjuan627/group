<?php

/**
 * @file
 * Contains \Drupal\gnode\Plugin\GroupContentEnabler\GroupNode.
 */

namespace Drupal\gnode\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupContentEnabler(
 *   id = "group_node",
 *   label = @Translation("Group node"),
 *   description = @Translation("Adds nodes to groups both publicly and privately."),
 *   entity_type_id = "node",
 *   cardinality = 1,
 *   deriver = "Drupal\gnode\Plugin\GroupNodeDerivatives"
 * )
 */
class GroupNode extends GroupContentEnablerBase {

}
