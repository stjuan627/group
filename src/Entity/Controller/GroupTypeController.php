<?php

/**
 * @file
 * Contains \Drupal\group\Controller\GroupTypeController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Plugin\GroupContentEnablerInterface;
use Drupal\group\Plugin\GroupContentEnablerCollection;
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
   * The IDs of the content enabler plugins the group type uses.
   *
   * @var string[]
   */
  protected $installedPluginIds;

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
    foreach ($this->groupType->enabledContent() as $plugin_id => $plugin) {
      $this->installedPluginIds[] = $plugin_id;
    }

    // Render the table of available content enablers.
    $page['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $page['content'] = [
      '#type' => 'table',
      '#header' => [
        'info' => $this->t('Plugin information'),
        'provider' => $this->t('Provided by'),
        'entity_type_id' => $this->t('Applies to'),
        'status' => $this->t('Status'),
        'operations' => $this->t('Operations'),
      ],
    ];

    foreach ($this->getAllContentEnablers() as $plugin_id => $plugin) {
      $page['content'][$plugin_id] = $this->buildRow($plugin);
    }

    return $page;
  }

  /**
   * Returns a plugin collection of all available content enablers.
   *
   * We add all known plugins to one big collection so we can sort them using
   * the sorting logic available on the collection and so we're sure we're not
   * instantiating our vanilla plugins more than once.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   The content enabler plugin collection.
   */
  public function getAllContentEnablers() {
    $manager = \Drupal::service('plugin.manager.group_content_enabler');
    $collection = new GroupContentEnablerCollection($manager, []);

    // Add every known plugin to the collection with a vanilla configuration.
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $plugin_info) {
      $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
    }

    // Sort and return the plugin collection.
    return $collection->sort();
  }

  /**
   * Builds a row for a content enabler plugin.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   A render array to use as a table row.
   */
  public function buildRow(GroupContentEnablerInterface $plugin) {
    // Get the plugin status.
    // @todo Remove when enforced are also installed.
    if ($plugin->isEnforced()) {
      $status = $this->t('Enforced');
    }
    elseif (in_array($plugin->getPluginId(), $this->installedPluginIds)) {
      $status = $this->t('Installed');
    }
    else {
      $status = $this->t('Uninstalled');
    }

    $row = [
      'info' => [
        '#type' => 'inline_template',
        '#template' => '<div class="description"><span class="label">{{ label }}</span>{% if description %}<br/>{{ description }}{% endif %}</div>',
        '#context' => [
          'label' => $plugin->getLabel(),
        ],
      ],
      'provider' => [
        '#markup' => $this->moduleHandler->getName($plugin->getProvider())
      ],
      'entity_type_id' => [
        '#markup' => $this->entityTypeManager->getDefinition($plugin->getEntityTypeId())->getLabel()
      ],
      'status' => ['#markup' => $status],
      'operations' => $this->buildOperations($plugin),
    ];

    // Show the content enabler description if toggled on.
    if (!system_admin_compact_mode()) {
      $row['info']['#context']['description'] = $plugin->getDescription();
    }

    return $row;
  }

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   An associative array of operation links for the group type's content
   *   plugin, keyed by operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations($plugin) {
    return $plugin->getOperations() + $this->getDefaultOperations($plugin);
  }

  /**
   * Gets the group type's content plugin's default operation links.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations($plugin) {
    $operations = [];

    $group_content_type_id = $plugin->getContentTypeConfigId($this->groupType);
    $group_content_type = GroupContentType::load($group_content_type_id);

    $plugin_id = $plugin->getPluginId();
    $installed = in_array($plugin_id, $this->installedPluginIds);
    $route_params = [
      'group_content_type' => $group_content_type_id,
    ];

    if ($installed) {
      $operations['configure'] = [
        'title' => $this->t('Configure'),
        'url' => new Url('entity.group_content_type.configure_form', $route_params),
      ];

      /* @var $group_content_type \Drupal\group\Entity\GroupContentTypeInterface */
      $operations += field_ui_entity_operation($group_content_type);
    }

    if (!$plugin->isEnforced()) {
      if ($installed) {
        $operations['uninstall'] = [
          'title' => $this->t('Uninstall'),
          'weight' => 99,
          'url' => new Url('group_content_type.uninstall', $route_params),
        ];
      }
      else {
        $operations['install'] = [
          'title' => $this->t('Install'),
          'url' => new Url('group_content_type.install', ['group_type' => $this->groupType->id(), 'plugin_id' => $plugin_id]),
        ];
      }
    }

    return $operations;
  }

  /**
   * Builds operation links for the group type's content plugins.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerInterface $plugin
   *   The content enabler plugin to build operation links for.
   *
   * @return array
   *   A render array of operation links.
   */
  public function buildOperations($plugin) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($plugin),
    );

    return $build;
  }

  /**
   * Builds a configuration form for a group type's content enabler plugin.
   *
   * @param \Drupal\group\Entity\GroupContentTypeInterface $group_content_type
   *
   * @return array
   *   The form structure.
   */
  public function configureContent(GroupContentTypeInterface $group_content_type) {
    return ['info' => ['#markup' => 'Nothing to see here yet.']];
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
    // @todo validation here, do not allow just any ID.

    $group_type->enableContent($plugin_id);
    drupal_set_message($this->t('The content was enabled for the group type.'));
    return $this->redirect('group_type.content', ['group_type' => $group_type->id()]);
  }

  /**
   * Removes a content enabler plugin from the group type.
   *
   * @param \Drupal\group\Entity\GroupContentTypeInterface $group_content_type
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function disableContent(GroupContentTypeInterface $group_content_type) {
    // @todo Figure out where to disable this: GCT::uninstall(), GT:disableContent(), here, ...

    $group_type = $group_content_type->getGroupType();
    $group_type->disableContent($group_content_type->getContentPlugin()->getPluginId());
    drupal_set_message($this->t('The content was disabled for the group type.'));
    return $this->redirect('group_type.content', ['group_type' => $group_type->id()]);
  }

}
