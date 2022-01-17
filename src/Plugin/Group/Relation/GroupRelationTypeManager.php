<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Manages group relation type type plugin definitions.
 *
 * Each entity type definition array is set in the entity type's annotation and
 * altered by hook_group_relation_type_alter().
 *
 * @see \Drupal\group\Annotation\GroupRelationType
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface
 * @see hook_group_relation_type_alter()
 */
class GroupRelationTypeManager extends DefaultPluginManager implements GroupRelationTypeManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Contains instantiated handlers keyed by handler type and plugin ID.
   *
   * @var array
   */
  protected $handlers = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $groupTypeStorage;

  /**
   * A group content type storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface
   */
  protected $groupContentTypeStorage;

  /**
   * A collection of vanilla instances of all group relations.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationCollection
   */
  protected $allPlugins;

  /**
   * An list each group type's installed plugins as plugin collections.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationCollection[]
   */
  protected $groupTypeInstalled = [];

  /**
   * An static cache of group content type IDs per plugin ID.
   *
   * @var array[]
   */
  protected $pluginGroupContentTypeMap;

  /**
   * The cache key for the group content type IDs per plugin ID map.
   *
   * @var string
   */
  protected $pluginGroupContentTypeMapCacheKey;

  /**
   * An static cache of plugin IDs per group type ID.
   *
   * @var array[]
   */
  protected $groupTypePluginMap;

  /**
   * The cache key for the plugin IDs per group type ID map.
   *
   * @var string
   */
  protected $groupTypePluginMapCacheKey;

  /**
   * Constructs a GroupRelationTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/Group/Relation', $namespaces, $module_handler, 'Drupal\group\Plugin\Group\Relation\GroupRelationInterface', 'Drupal\group\Annotation\GroupRelationType');
    $this->alterInfo('group_relation_type');
    $this->setCacheBackend($cache_backend, 'group_relations');
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginGroupContentTypeMapCacheKey = $this->cacheKey . '_GCT_map';
    $this->groupTypePluginMapCacheKey = $this->cacheKey . '_GT_map';
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($plugin_id, $handler_type) {
    if (!isset($this->handlers[$handler_type][$plugin_id])) {
      $this->handlers[$handler_type][$plugin_id] = $this->createHandlerInstance($plugin_id, $handler_type);
    }
    return $this->handlers[$handler_type][$plugin_id];
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance($plugin_id, $handler_type) {
    /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type */
    $group_relation_type = $this->getDefinition($plugin_id);
    $service_name = "group.relation_handler.$handler_type.{$group_relation_type->id()}";

    if (!$this->container->has($service_name)) {
      throw new InvalidPluginDefinitionException($plugin_id, sprintf('The "%s" plugin did not specify a %s handler service (%s).', $plugin_id, $handler_type, $service_name));
    }
    $handler = $this->container->get($service_name);

    if (!is_subclass_of($handler, 'Drupal\group\Plugin\Group\RelationHandler\RelationHandlerInterface')) {
      throw new InvalidPluginDefinitionException($plugin_id, 'Trying to instantiate a handler that does not implement \Drupal\group\Plugin\Group\RelationHandler\RelationHandlerInterface.');
    }
    $handler->init($plugin_id, $group_relation_type);

    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlHandler($plugin_id) {
    return $this->getHandler($plugin_id, 'access_control');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceHandler($plugin_id) {
    return $this->getHandler($plugin_id, 'entity_reference');
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationProvider($plugin_id) {
    return $this->getHandler($plugin_id, 'operation_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionProvider($plugin_id) {
    return $this->getHandler($plugin_id, 'permission_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function getPostInstallHandler($plugin_id) {
    return $this->getHandler($plugin_id, 'post_install');
  }

  /**
   * Returns the group type storage handler.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The group type storage handler.
   */
  protected function getGroupTypeStorage() {
    if (!isset($this->groupTypeStorage)) {
      $this->groupTypeStorage = $this->entityTypeManager->getStorage('group_type');
    }
    return $this->groupTypeStorage;
  }

  /**
   * Returns the group content type storage handler.
   *
   * @return \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface
   *   The group content type storage handler.
   */
  protected function getGroupContentTypeStorage() {
    if (!isset($this->groupContentTypeStorage)) {
      $this->groupContentTypeStorage = $this->entityTypeManager->getStorage('group_content_type');
    }
    return $this->groupContentTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalled(GroupTypeInterface $group_type) {
    if (!isset($this->groupTypeInstalled[$group_type->id()])) {
      $configurations = [];
      $group_content_types = $this->getGroupContentTypeStorage()->loadByGroupType($group_type);

      // Get the plugin config from every group content type for the group type.
      foreach ($group_content_types as $group_content_type) {
        $plugin_id = $group_content_type->getPluginId();

        // Grab the plugin config from every group content type and amend it
        // with the group type ID so the plugin knows what group type to use. We
        // also specify the 'id' key because DefaultLazyPluginCollection throws
        // an exception if it is not present.
        $configuration = $group_content_type->get('plugin_config');
        $configuration['group_type_id'] = $group_type->id();
        $configuration['id'] = $plugin_id;

        $configurations[$plugin_id] = $configuration;
      }

      $plugins = new GroupRelationCollection($this, $configurations);
      $plugins->sort();

      $this->groupTypeInstalled[$group_type->id()] = $plugins;
    }

    return $this->groupTypeInstalled[$group_type->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledIds(GroupTypeInterface $group_type) {
    $map = $this->getGroupTypePluginMap();
    return isset($map[$group_type->id()]) ? $map[$group_type->id()] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginIdsByEntityTypeAccess($entity_type_id) {
    $plugin_ids = [];
    foreach ($this->getDefinitions() as $plugin_id => $group_relation_type) {
      /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type */
      if ($group_relation_type->definesEntityAccess() && $group_relation_type->getEntityTypeId() == $entity_type_id) {
        $plugin_ids[] = $plugin_id;
      }
    }
    return $plugin_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function installEnforced(GroupTypeInterface $group_type = NULL) {
    $enforced = [];

    // Gather the ID of all plugins that are marked as enforced.
    foreach ($this->getDefinitions() as $plugin_id => $group_relation_type) {
      /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type */
      if ($group_relation_type->isEnforced()) {
        $enforced[] = $plugin_id;
      }
    }

    // If no group type was specified, we check all of them.
    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
    $group_types = empty($group_type) ? $this->getGroupTypeStorage()->loadMultiple() : [$group_type];

    // Search through all of the enforced plugins and install new ones.
    foreach ($group_types as $group_type) {
      $installed = $this->getInstalledIds($group_type);

      foreach ($enforced as $plugin_id) {
        if (!in_array($plugin_id, $installed)) {
          $this->getGroupContentTypeStorage()->createFromPlugin($group_type, $plugin_id)->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentTypeIds($plugin_id) {
    $map = $this->getPluginGroupContentTypeMap();
    return isset($map[$plugin_id]) ? $map[$plugin_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginGroupContentTypeMap() {
    $map = $this->getCachedPluginGroupContentTypeMap();

    if (!isset($map)) {
      $map = [];

      /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $group_content_types */
      $group_content_types = $this->getGroupContentTypeStorage()->loadMultiple();
      foreach ($group_content_types as $group_content_type) {
        $map[$group_content_type->getPluginId()][] = $group_content_type->id();
      }

      $this->setCachedPluginGroupContentTypeMap($map);
    }

    return $map;
  }

  /**
   * Returns the cached group content type ID map.
   *
   * @return array|null
   *   On success this will return the group content ID map (array). On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array.
   */
  protected function getCachedPluginGroupContentTypeMap() {
    if (!isset($this->pluginGroupContentTypeMap) && $cache = $this->cacheGet($this->pluginGroupContentTypeMapCacheKey)) {
      $this->pluginGroupContentTypeMap = $cache->data;
    }
    return $this->pluginGroupContentTypeMap;
  }

  /**
   * Sets a cache of the group content type ID map.
   *
   * @param array $map
   *   The group content type ID map to store in cache.
   */
  protected function setCachedPluginGroupContentTypeMap(array $map) {
    $this->cacheSet($this->pluginGroupContentTypeMapCacheKey, $map, Cache::PERMANENT);
    $this->pluginGroupContentTypeMap = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypePluginMap() {
    $map = $this->getCachedGroupTypePluginMap();

    if (!isset($map)) {
      $map = [];

      /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $group_content_types */
      $group_content_types = $this->getGroupContentTypeStorage()->loadMultiple();
      foreach ($group_content_types as $group_content_type) {
        $map[$group_content_type->getGroupTypeId()][] = $group_content_type->getPluginId();
      }

      $this->setCachedGroupTypePluginMap($map);
    }

    return $map;
  }

  /**
   * Returns the cached group type plugin map.
   *
   * @return array|null
   *   On success this will return the group type plugin map (array). On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array.
   */
  protected function getCachedGroupTypePluginMap() {
    if (!isset($this->groupTypePluginMap) && $cache = $this->cacheGet($this->groupTypePluginMapCacheKey)) {
      $this->groupTypePluginMap = $cache->data;
    }
    return $this->groupTypePluginMap;
  }

  /**
   * Sets a cache of the group type plugin map.
   *
   * @param array $map
   *   The group type plugin map to store in cache.
   */
  protected function setCachedGroupTypePluginMap(array $map) {
    $this->cacheSet($this->groupTypePluginMapCacheKey, $map, Cache::PERMANENT);
    $this->groupTypePluginMap = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedGroupTypeCollections(GroupTypeInterface $group_type = NULL) {
    if (!isset($group_type)) {
      $this->groupTypeInstalled = [];
    }
    else {
      $this->groupTypeInstalled[$group_type->id()] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedPluginMaps() {
    if ($this->cacheBackend) {
      $this->cacheBackend->delete($this->pluginGroupContentTypeMapCacheKey);
      $this->cacheBackend->delete($this->groupTypePluginMapCacheKey);
    }
    $this->pluginGroupContentTypeMap = NULL;
    $this->groupTypePluginMap = NULL;

    // Also clear the array of per group type plugin collections as it shares
    // its cache clearing requirements with the group type plugin map.
    $this->groupTypeInstalled = [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();

    // The collection of all plugins should only change if the plugin
    // definitions change, so we can safely reset that here.
    $this->allPlugins = NULL;
  }

}
