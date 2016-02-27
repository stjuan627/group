<?php

/**
 * @file
 * Contains \Drupal\gnode\Plugin\GroupContentEnabler\GroupNode.
 */

namespace Drupal\gnode\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\node\Entity\NodeType;
use Symfony\Component\Routing\Route;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupContentEnabler(
 *   id = "group_node",
 *   label = @Translation("Group node"),
 *   description = @Translation("Adds nodes to groups both publicly and privately."),
 *   entity_type_id = "node",
 *   entity_cardinality = 1,
 *   path_key = "node",
 *   deriver = "Drupal\gnode\Plugin\GroupContentEnabler\GroupNodeDeriver"
 * )
 */
class GroupNode extends GroupContentEnablerBase {

  /**
   * Retrieves the node type this plugin supports.
   *
   * @return \Drupal\node\NodeTypeInterface
   *   The node type this plugin supports.
   */
  protected function getNodeType() {
    return NodeType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityForms() {
    return ['gnode-form' => 'Drupal\gnode\Form\GroupNodeFormStep2'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = parent::getPermissions();

    $type = $this->getEntityBundle();
    $type_arg = ['%node_type' => $this->getNodeType()->label()];
    $defaults = [
      'title_args' => $type_arg,
      'description' => 'Only applies to %node_type nodes that belong to this group.',
      'description_args' => $type_arg,
    ];

    $permissions["view $type node"] = [
      'title' => '%node_type: View content',
    ] + $defaults;

    $permissions["create $type node"] = [
      'title' => '%node_type: Create new content',
      'description' => 'Allows you to create %node_type nodes that immediately belong to this group.',
      'description_args' => $type_arg,
    ] + $defaults;

    $permissions["edit own $type node"] = [
      'title' => '%node_type: Edit own content',
    ] + $defaults;

    $permissions["edit any $type node"] = [
      'title' => '%node_type: Edit any content',
    ] + $defaults;

    $permissions["delete own $type node"] = [
      'title' => '%node_type: Delete own content',
    ] + $defaults;

    $permissions["delete any $type node"] = [
      'title' => '%node_type: Delete any content',
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths() {
    $paths = parent::getPaths();

    $type = $this->getEntityBundle();
    $paths['add-form'] = "/group/{group}/node/add/$type";
    $paths['node-add-form'] = "/group/{group}/node/create/$type";

    return $paths;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\gnode\Routing\GroupNodeRouteProvider
   */
  public function getRouteName($name) {
    switch ($name) {
      // The collection route can be found in GroupNodeRouteProvider.
      case 'collection':
        return 'entity.group_content.group_node.collection';

      // The add form routes need to have the node type hardcoded in their path
      // so we can have a different route for each node type. That way, the
      // routes can check for the responsible plugin without needing to have the
      // plugin ID in the path.
      case 'add-form':
      case 'node-add-form':
        $prefix = str_replace('-', '_', $name) . '_';
        $type = $this->getEntityBundle();
        return "entity.group_content.group_node.$prefix$type";
    }

    return parent::getRouteName($name);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\gnode\Routing\GroupNodeRouteProvider
   */
  protected function getCollectionRoute() {
  }

  /**
   * Gets the join form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getNodeAddFormRoute() {
    if ($path = $this->getPath('node-add-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_controller' => '\Drupal\gnode\Controller\GroupNodeController::add',
          '_title_callback' => '\Drupal\gnode\Controller\GroupNodeController::addTitle',
          'node_type' => $this->getEntityBundle(),
        ])
        ->setRequirement('_group_permission', 'create ' . $this->getEntityBundle() . ' node')
        ->setRequirement('_group_installed_content', $this->getPluginId())
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    $routes = parent::getRoutes();

    if ($route = $this->getNodeAddFormRoute()) {
      $routes[$this->getRouteName('node-add-form')] = $route;
    }

    return $routes;
  }

}
