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
   * Prevents this class from being instantiated.
   */
  private function __construct() {}
  
  /**
   * Returns a list of additional forms to enable for group content entities.
   *
   * @return array
   *   An associative array with form names as keys and class names as values.
   */
  public static function getAdditionalEntityForms() {
    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');
    
    // Retrieve all installed content enabler plugins.
    $installed = $plugin_manager->getInstalledIds();

    // Retrieve all possible forms from all installed plugins.
    $forms = [];
    foreach ($plugin_manager->getAll() as $plugin_id => $plugin) {
      // Skip plugins that have not been installed anywhere.
      if (!in_array($plugin_id, $installed)) {
        continue;
      }

      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $forms = array_merge($forms, $plugin->getEntityForms());
    }

    return $forms;
  }

  /**
   * Installs all plugins which are marked as enforced.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to install enforced plugins for. Leave blank to
   *   run the installation process for all group types.
   */
  public static function installEnforcedPlugins(GroupTypeInterface $group_type = NULL) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');
    
    $enforced = [];
    foreach ($plugin_manager->getAll() as $plugin_id => $plugin) {
      /** @var GroupContentEnablerInterface $plugin */
      if ($plugin->isEnforced()) {
        $enforced[] = $plugin_id;
      }
    }

    $group_types = empty($group_type) ? GroupType::loadMultiple() : [$group_type];
    foreach ($group_types as $group_type) {
      // Search through all the enforced plugins and install new ones.
      foreach ($enforced as $plugin_id) {
        if (!$group_type->hasContentPlugin($plugin_id)) {
          $group_type->installContentPlugin($plugin_id);
        }
      }
    }
  }

}
