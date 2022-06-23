<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

/**
 * Defines the Group content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_content_type",
 *   label = @Translation("Group content type"),
 *   label_singular = @Translation("group content type"),
 *   label_plural = @Translation("group content types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group content type",
 *     plural = "@count group content types"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupContentTypeStorage",
 *     "access" = "Drupal\group\Entity\Access\GroupContentTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentTypeDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "content_type",
 *   bundle_of = "group_content",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "group_type",
 *     "content_plugin",
 *     "plugin_config",
 *   },
 *   links = {
 *     "edit-form" = "/admin/group/content/manage/{group_content_type}",
 *   }
 * )
 */
class GroupContentType extends ConfigEntityBundleBase implements GroupContentTypeInterface {

  /**
   * The machine name of the group content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The group type ID for the group content type.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The group relation type ID for the group content type.
   *
   * @var string
   * @todo 2.0.x Replace with other name.
   */
  protected $content_plugin;

  /**
   * The group relation configuration for group content type.
   *
   * @var array
   */
  protected $plugin_config = [];

  /**
   * The group relation instance.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   */
  protected $pluginInstance;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->getGroupTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->group_type;
  }

  /**
   * Returns the group relation type manager.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   *   The group relation type manager.
   */
  protected function getGroupRelationTypeManager() {
    return \Drupal::service('group_relation_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    if (!isset($this->pluginInstance)) {
      $configuration = $this->plugin_config;
      $configuration['group_type_id'] = $this->getGroupTypeId();
      $this->pluginInstance = $this->getGroupRelationTypeManager()->createInstance($this->getPluginId(), $configuration);
    }
    return $this->pluginInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->content_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function updateContentPlugin(array $configuration) {
    $this->plugin_config = $configuration;
    $this->save();

    // Make sure people get a fresh local plugin instance.
    $this->pluginInstance = NULL;

    // Make sure people get a freshly configured plugin collection.
    $this->getGroupRelationTypeManager()->clearCachedGroupTypeCollections($this->getGroupType());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByPluginId($plugin_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    return $storage->loadByPluginId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntityTypeId($entity_type_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    return $storage->loadByEntityTypeId($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      $plugin_manager = $this->getGroupRelationTypeManager();

      // When a new GroupContentType is saved, we clear the views data cache to
      // make sure that all of the views data which relies on group content
      // types is up to date.
      if (\Drupal::moduleHandler()->moduleExists('views')) {
        \Drupal::service('views.views_data')->clear();
      }

      // Run the post install tasks on the plugin.
      $post_install_handler = $plugin_manager->getPostInstallHandler($this->getPluginId());
      $task_arguments = [$this, \Drupal::isConfigSyncing()];
      foreach ($post_install_handler->getInstallTasks() as $task) {
        call_user_func_array($task, $task_arguments);
      }

      // We need to reset the plugin ID map cache as it will be out of date now.
      $plugin_manager->clearCachedPluginMaps();

      // If the plugin handles config entities, it might affect the available
      // bundles for ConfigWrapper, so we need to clear the bundle info cache.
      if ($plugin_manager->getDefinition($this->getPluginId())->handlesConfigEntityType()) {
        \Drupal::service('entity_type.bundle.info')->clearCachedBundles();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // When a GroupContentType is deleted, we clear the views data cache to make
    // sure that all of the views data which relies on group content types is up
    // to date.
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      \Drupal::service('views.views_data')->clear();
    }

    $plugin_manager = \Drupal::service('group_relation_type.manager');
    assert($plugin_manager instanceof GroupRelationTypeManagerInterface);

    // We need to reset the plugin ID map cache as it will be out of date now.
    $plugin_manager->clearCachedPluginMaps();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // By adding the group type as a dependency, we ensure the group content
    // type is deleted along with the group type.
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());

    // Add the dependencies of the responsible group relation.
    $this->addDependencies($this->getPlugin()->calculateDependencies());
  }

}
