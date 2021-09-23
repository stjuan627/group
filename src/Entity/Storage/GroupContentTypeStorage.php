<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for group content type entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group content type entities based on group type and plugin ID.
 */
class GroupContentTypeStorage extends ConfigEntityStorage implements GroupContentTypeStorageInterface {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * Statically caches loaded group content types by target entity type ID.
   *
   * @var \Drupal\group\Entity\GroupContentTypeInterface[][]
   */
  protected $byEntityTypeCache = [];

  /**
   * Constructs a GroupContentTypeStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   */
  public function __construct(EntityTypeInterface $entity_type, GroupRelationTypeManagerInterface $plugin_manager, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('group_relation_type.manager'),
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroupType(GroupTypeInterface $group_type) {
    return $this->loadByProperties(['group_type' => $group_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByRelationPluginId($plugin_id) {
    return $this->loadByProperties(['content_plugin' => $plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntityTypeId($entity_type_id) {
    $plugin_ids = [];

    if (isset($this->byEntityTypeCache[$entity_type_id])) {
      return $this->byEntityTypeCache[$entity_type_id];
    }

    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $group_relation_type) {
      /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type */
      if ($group_relation_type->getEntityTypeId() === $entity_type_id) {
        $plugin_ids[] = $plugin_id;
      }
    }

    // If no responsible group relations were found, we return nothing.
    if (empty($plugin_ids)) {
      $this->byEntityTypeCache[$entity_type_id] = [];
      return [];
    }

    // Otherwise load all group content types being handled by gathered plugins.
    $this->byEntityTypeCache[$entity_type_id] = $this->loadByRelationPluginId($plugin_ids);
    return $this->byEntityTypeCache[$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function createFromPlugin(GroupTypeInterface $group_type, $plugin_id, array $configuration = []) {
    // Add the group type ID to the configuration.
    $configuration['group_type_id'] = $group_type->id();

    // Instantiate the plugin we are installing.
    /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationInterface $plugin */
    $plugin = $this->pluginManager->createInstance($plugin_id, $configuration);

    // Create the group content type using plugin generated info.
    $values = [
      'id' => $this->getGroupContentTypeId($group_type->id(), $plugin_id),
      'label' => $plugin->getContentTypeLabel(),
      'description' => $plugin->getContentTypeDescription(),
      'group_type' => $group_type->id(),
      'content_plugin' => $plugin_id,
      'plugin_config' => $plugin->getConfiguration(),
    ];

    return $this->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentTypeId($group_type_id, $plugin_id) {
    $preferred_id = $group_type_id . '-' . str_replace(':', '-', $plugin_id);

    // Return a hashed ID if the readable ID would exceed the maximum length.
    if (strlen($preferred_id) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
      $hashed_id = 'group_content_type_' . md5($preferred_id);
      $preferred_id = substr($hashed_id, 0, EntityTypeInterface::BUNDLE_MAX_LENGTH);
    }

    return $preferred_id;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    parent::resetCache($ids);
    $this->byEntityTypeCache = [];
  }

}
