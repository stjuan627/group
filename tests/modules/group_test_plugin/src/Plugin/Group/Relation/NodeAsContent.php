<?php

namespace Drupal\group_test_plugin\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation for nodes.
 *
 * @GroupRelation(
 *   id = "node_as_content",
 *   label = @Translation("Node as content"),
 *   description = @Translation("Adds nodes to groups."),
 *   entity_type_id = "node",
 *   entity_access = TRUE,
 *   deriver = "Drupal\group_test_plugin\Plugin\Group\Relation\NodeAsContentDeriver",
 *   admin_permission = "administer node_as_content:page"
 * )
 */
class NodeAsContent extends GroupRelationBase {
}
