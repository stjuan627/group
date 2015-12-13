<?php

/**
 * @file
 * Contains \Drupal\group\Controller\GroupTypeController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form for a specific group type.
 */
class GroupTypeController extends ControllerBase {

  /**
   * The group type to use in this controller.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The module manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupSettingsForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }
  
  /**
   * Builds an admin interface to manage the group type's group content plugins.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to build an interface for.
   */
  public function content(GroupTypeInterface $group_type) {
    $this->groupType = $group_type;

    $plugins = $this->pluginManager->getDefinitions();
    $enabled = [];
    foreach ($this->groupType->enabledContent() as $plugin) {
      $enabled[] = $plugin->getPluginId();
    }

    // Build the list of enabled group content effects for this group type.
    $page['content'] = [
      '#type' => 'table',
      '#header' => [
        'label' => $this->t('Label'),
        'description' => $this->t('Description'),
        'provider' => $this->t('Provided by'),
        'entity_type_id' => $this->t('Applies to'),
        'status' => $this->t('Status'),
        'operations' => $this->t('Operations'),
      ],
    ];

    foreach ($plugins as $plugin_id => $plugin_info) {
      $is_enabled = in_array($plugin_id, $enabled);
      $page['content'][$plugin_id] = [
        'label' => ['#markup' => $plugin_info['label']],
        'description' => ['#markup' => $plugin_info['description']],
        'provider' => ['#markup' => $this->moduleHandler->getName($plugin_info['provider'])],
        'entity_type_id' => ['#markup' => $this->entityTypeManager->getDefinition($plugin_info['entity_type_id'])->getLabel()],
        'status' => ['#markup' => $is_enabled ? $this->t('Enabled') : $this->t('Disabled')],
        'operations' => $this->buildOperations($plugin_id),
      ];
    }

    return $page;
  }

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param string $plugin_id
   *   The ID of the group content plugin to build operation links for.
   *
   * @return array
   *   An associative array of operation links for the group type's content
   *  plugin, keyed by operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations($plugin_id) {
    $operations = $this->getDefaultOperations($plugin_id);
    // @todo Allow plugin to add operations.
    return $operations;
  }

  /**
   * Gets the group type's content plugin's default operation links.
   *
   * @param string $plugin_id
   *   The ID of the group content plugin to build operation links for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations($plugin_id) {
    $enabled = $operations = [];
    foreach ($this->groupType->enabledContent() as $plugin) {
      $enabled[] = $plugin->getPluginId();
    }

    $route_params = ['group_type' => $this->groupType->id(), 'plugin_id' => $plugin_id];
    if (in_array($plugin_id, $enabled)) {
      $operations['disable'] = [
        'title' => $this->t('Disable'),
        'weight' => 99,
        'url' => new Url('group_type.content_disable', $route_params),
      ];
    }
    else {
      $operations['enable'] = [
        'title' => $this->t('Enable'),
        'url' => new Url('group_type.content_enable', $route_params),
      ];
    }

    return $operations;
  }

  /**
   * Builds operation links for the group type's content plugins.
   *
   * @param string $plugin_id
   *   The ID of the group content plugin to build operation links for.
   *
   * @return array
   *   A render array of operation links.
   */
  public function buildOperations($plugin_id) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($plugin_id),
    );

    return $build;
  }

  /**
   * Adds an unconfigured content enabler plugin to the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   * @param string $plugin_id
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function enableContent(GroupTypeInterface $group_type, $plugin_id) {
    // @todo validation here.

    $enabler_id = $group_type->enableContent(['id' => $plugin_id]);
    $group_type->save();

    if (!empty($enabler_id)) {
      drupal_set_message($this->t('The content was successfully enabled for the group type.'));
    }

    return $this->redirect('group_type.content', array('group_type' => $group_type->id()));
  }

  /**
   * Removes a content enabler plugin from the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   * @param string $plugin_id
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function disableContent(GroupTypeInterface $group_type, $plugin_id) {
    // @todo validation here.
    // @todo disabling here.
    return $this->redirect('group_type.content', array('group_type' => $group_type->id()));
  }

}
