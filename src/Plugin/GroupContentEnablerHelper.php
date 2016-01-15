<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerHelper.
 */

namespace Drupal\group\Plugin;

use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Facilitates the installation of GroupContentEnabler plugins.
 */
class GroupContentEnablerHelper {

  /**
   * A collection of instances of all content enabler plugins.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerCollection
   */
  protected static $pluginCollection;

  /**
   * Prevents this class from being instantiated.
   */
  private function __construct() {}

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
  public static function getAllContentEnablers() {
    if (!isset(self::$pluginCollection)) {
      $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');
      $collection = new GroupContentEnablerCollection($plugin_manager, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($plugin_manager->getDefinitions() as $plugin_id => $plugin_info) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      self::$pluginCollection = $collection->sort();
    }

    return self::$pluginCollection;
  }

  /**
   * Installs all plugins which are marked as enforced.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to install enforced plugins for. Leave blank to
   *   run the installation process for all group types.
   */
  public static function installEnforcedPlugins(GroupTypeInterface $group_type = NULL) {
    $enforced = [];
    foreach (self::getAllContentEnablers() as $plugin_id => $plugin) {
      /** @var GroupContentEnablerInterface $plugin */
      if ($plugin->isEnforced()) {
        $enforced[] = $plugin_id;
      }
    }

    $group_types = empty($group_type) ? GroupType::loadMultiple() : [$group_type];
    foreach ($group_types as $group_type) {
      // Retrieve all the installed plugins from the group type.
      $installed_plugins = $group_type->enabledContent()->getIterator();

      // Search through all the enforced plugins and install new ones.
      foreach ($enforced as $plugin_id) {
        if (!$installed_plugins->offsetExists($plugin_id)) {
          $group_type->enableContent($plugin_id);
        }
      }
    }
  }

  /**
   * Resets the static properties on this class.
   */
  public static function reset() {
    self::$pluginCollection = NULL;
  }

}
