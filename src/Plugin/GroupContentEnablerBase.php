<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerBase.
 */

namespace Drupal\group\Plugin;

use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a base class for GroupContentEnabler plugins.
 *
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\GroupContentEnablerManager
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see plugin_api
 */
abstract class GroupContentEnablerBase extends PluginBase implements GroupContentEnablerInterface {

  /**
   * The ID of group type this plugin was instantiated for.
   *
   * @var string
   */
  protected $groupTypeId;

  /**
   * {@inheritdoc}
   *
   * @todo Consider doing configuration like BlockBase so we can remove this.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // We call ::setConfiguration at construction to hide all non-configurable
    // keys such as 'id'. This causes the $configuration property to only list
    // that which is in fact configurable. However, ::getConfiguration still
    // returns the full configuration array.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupCardinality() {
    return $this->pluginDefinition['group_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCardinality() {
    return $this->pluginDefinition['entity_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($name) {
    $paths = $this->pluginDefinition['paths'];
    return isset($paths[$name]) ? $paths[$name] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->groupTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnforced() {
    return $this->pluginDefinition['enforced'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLabel(GroupContentInterface $group_content) {
    return $group_content->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeConfigId() {
    return $this->getGroupTypeId() . '.' . str_replace(':', '.', $this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeLabel() {
    $group_type = GroupType::load($this->getGroupTypeId());
    return $group_type->label() . ': ' . $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeDescription() {
    return $this->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityForms() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $plugin_id = $this->getPluginId();
    $plugin_params = ['%plugin_name' => $this->getLabel()];

    $permissions["view $plugin_id content"] = [
      'title' => '%plugin_name: View content',
      'title_args' => $plugin_params,
    ];

    $permissions["create $plugin_id content"] = [
      'title' => '%plugin_name: Create new content',
      'title_args' => $plugin_params,
    ];

    $permissions["edit own $plugin_id content"] = [
      'title' => '%plugin_name: Edit own content',
      'title_args' => $plugin_params,
    ];

    $permissions["edit any $plugin_id content"] = [
      'title' => '%plugin_name: Edit any content',
      'title_args' => $plugin_params,
    ];

    $permissions["delete own $plugin_id content"] = [
      'title' => '%plugin_name: Delete own content',
      'title_args' => $plugin_params,
    ];

    $permissions["delete any $plugin_id content"] = [
      'title' => '%plugin_name: Delete any content',
      'title_args' => $plugin_params,
    ];

    return $permissions;
  }

  /**
   * Gets the collection route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute() {
    if ($path = $this->getPath('collection')) {
      $plugin_id = $this->getPluginId();
      $route = new Route($path);

      $route
        ->setDefaults([
          '_entity_list' => 'group_content',
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_group_permission', "view $plugin_id content")
        ->setRequirement('_group_installed_content', $plugin_id)
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * Gets the canonical route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCanonicalRoute() {
    if ($path = $this->getPath('canonical')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_entity_view' => 'group_content.full',
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_entity_access', 'group_content.view')
        ->setRequirement('_group_installed_content', $this->getPluginId())
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content' => ['type' => 'entity:group_content'],
        ]);

      return $route;
    }
  }

  /**
   * Gets the add form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddFormRoute() {
    if ($path = $this->getPath('add-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Entity\Controller\GroupContentController::add',
          '_title_callback' => '\Drupal\group\Entity\Controller\GroupContentController::addPageTitle',
          'plugin_id' => $this->getPluginId(),
        ])
        ->setRequirement('_group_content_add_access', $this->getPluginId())
        ->setRequirement('_group_installed_content', $this->getPluginId())
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * Gets the edit form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEditFormRoute() {
    if ($path = $this->getPath('edit-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_entity_form' => 'group_content.edit',
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::editTitle',
        ])
        ->setRequirement('_entity_access', 'group_content.update')
        ->setRequirement('_group_installed_content', $this->getPluginId())
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content' => ['type' => 'entity:group_content'],
        ]);

      return $route;
    }
  }

  /**
   * Gets the delete form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteFormRoute() {
    if ($path = $this->getPath('delete-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_entity_form' => 'group_content.delete',
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::deleteTitle',
        ])
        ->setRequirement('_entity_access', 'group_content.delete')
        ->setRequirement('_group_installed_content', $this->getPluginId())
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content' => ['type' => 'entity:group_content'],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    $routes = [];
    $route_prefix = 'entity.group_content.' . str_replace(':', '__', $this->getPluginId());

    if ($collection_route = $this->getCollectionRoute()) {
      $routes["$route_prefix.collection"] = $collection_route;
    }

    if ($add_route = $this->getAddFormRoute()) {
      $routes["$route_prefix.add_form"] = $add_route;
    }

    if ($canonical_route = $this->getCanonicalRoute()) {
      $routes["$route_prefix.canonical"] = $canonical_route;
    }

    if ($edit_route = $this->getEditFormRoute()) {
      $routes["$route_prefix.edit_form"] = $edit_route;
    }

    if ($delete_route = $this->getDeleteFormRoute()) {
      $routes["$route_prefix.delete_form"] = $delete_route;
    }

    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(GroupInterface $group, AccountInterface $account) {
    $plugin_id = $this->getPluginId();
    return AccessResult::allowedIf($group->hasPermission("create $plugin_id content", $account));
  }

  /**
   * Performs access check for the view operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function viewAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $plugin_id = $this->getPluginId();
    return AccessResult::allowedIf($group_content->getGroup()->hasPermission("view $plugin_id content", $account));
  }

  /**
   * Performs access check for the update operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function updateAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $plugin_id = $this->getPluginId();

    // @todo Check for own content when we support setting an author.

    return AccessResult::allowedIf($group_content->getGroup()->hasPermission("edit any $plugin_id content", $account));
  }

  /**
   * Performs access check for the delete operation.
   *
   * This method is supposed to be overwritten by extending classes that
   * do their own custom access checking.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content for which to check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function deleteAccess(GroupContentInterface $group_content, AccountInterface $account) {
    $plugin_id = $this->getPluginId();

    // @todo Check for own content when we support setting an author.

    return AccessResult::allowedIf($group_content->getGroup()->hasPermission("delete any $plugin_id content", $account));
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(GroupContentInterface $group_content, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        $result = $this->viewAccess($group_content, $account);
        break;
      case 'update':
        $result = $this->updateAccess($group_content, $account);
        break;
      case 'delete':
        $result = $this->deleteAccess($group_content, $account);
        break;
      default:
        $result = AccessResult::neutral();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceSettings() {
    return ['target_type' => $this->getEntityTypeId()];
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'id' => $this->getPluginId(),
      'group_type' => $this->getGroupTypeId(),
      'data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += array(
      'data' => array(),
      'group_type' => NULL,
    );
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    $this->groupTypeId = $configuration['group_type'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}
