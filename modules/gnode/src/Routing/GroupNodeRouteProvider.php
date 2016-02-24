<?php

/**
 * @file
 * Contains \Drupal\gnode\Routing\GroupNodeRouteProvider.
 */

namespace Drupal\gnode\Routing;

use Drupal\node\Entity\NodeType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_node group content.
 */
class GroupNodeRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    $plugin_ids = [];
    foreach (NodeType::loadMultiple() as $name => $node_type) {
      $plugin_ids[] = "group_node:$name";
    }

    $route = new Route('group/{group}/node');
    $route
      ->setDefaults([
        '_entity_list' => 'group_content',
        '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        'plugin_id' => $plugin_ids,
      ])
      ->setRequirement('_group_permission', 'access group_node overview')
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE)
      ->setOption('parameters', [
        'group' => ['type' => 'entity:group'],
      ]);

    return ['entity.group_content.group_node.collection' => $route];
  }

}
