<?php

/**
 * @file
 * Contains \Drupal\group\GroupContentPluginManager.
 */

namespace Drupal\group;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages GroupContent plugin implementations.
 *
 * @see hook_group_content_info_alter()
 * @see \Drupal\group\Annotation\GroupContent
 * @see \Drupal\group\Plugin\GroupContentInterface
 * @see \Drupal\group\Plugin\GroupContentBase
 * @see plugin_api
 */
class GroupContentPluginManager extends DefaultPluginManager {

  /**
   * Constructs a GroupContentPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GroupContent', $namespaces, $module_handler, 'Drupal\group\Plugin\GroupContentInterface', 'Drupal\group\Annotation\GroupContent');
    $this->alterInfo('group_content_info');
    $this->setCacheBackend($cache_backend, 'group_content_plugins');
  }

}
