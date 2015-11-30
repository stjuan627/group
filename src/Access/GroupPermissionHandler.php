<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupPermissionHandler.
 */

namespace Drupal\group\Access;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides the available permissions based on yml files.
 *
 * To define permissions you can use a $module.group.permissions.yml file. This
 * file defines machine names and human-readable names for each permission. The
 * machine names are the canonical way to refer to permissions for access
 * checking. Each permission may also have a restrict access and/or warning
 * message, keys defining what roles it applies to and a description.
 *
 * If your module needs to define dynamic permissions you can use the
 * permission_callbacks key to declare a callable that will return an array of
 * permissions, keyed by machine name. Each item in the array can contain the
 * same keys as an entry in $module.group.permissions.yml.
 *
 * Here is an example from the group module itself (comments have been added):
 * @code
 * # The key is the permission machine name, and is required.
 * leave group:
 *   # (required) Human readable name of the permission used in the UI.
 *   title: 'Leave group'
 *   # (optional) Define which roles can be assigned this permission.
 *   allowed for: ['member']
 *
 * # An array of callables used to generate dynamic permissions.
 * permission_callbacks:
 *   # Each item in the array should return an associative array with one or
 *   # more permissions following the same keys as the permission defined above.
 *   - Drupal\group\GroupPermissions::permissions
 * @endcode
 *
 * @see group.group.permissions.yml
 * @see \Drupal\group\GroupPermissions
 */
class GroupPermissionHandler implements GroupPermissionHandlerInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The YAML discovery class to find all .group.permissions.yml files.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery
   */
  protected $yamlDiscovery;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Constructs a new PermissionHandler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $string_translation, ControllerResolverInterface $controller_resolver) {
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('group.permissions', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $all_permissions = $this->buildPermissionsYaml();
    return $this->sortPermissions($all_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function moduleProvidesPermissions($module_name) {
    $permissions = $this->getPermissions();

    foreach ($permissions as $permission) {
      if ($permission['provider'] == $module_name) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Builds all permissions provided by .group.permissions.yml files.
   *
   * @return array[]
   *   An array of permissions as described in ::getPermissions().
   *
   * @see \Drupal\group\Access\PermissionHandlerInterface::getPermissions()
   */
  protected function buildPermissionsYaml() {
    $all_permissions = array();
    $all_callback_permissions = array();

    foreach ($this->getYamlDiscovery()->findAll() as $provider => $permissions) {
      // The top-level 'permissions_callback' is a list of methods in controller
      // syntax, see \Drupal\Core\Controller\ControllerResolver. These methods
      // should return an array of permissions in the same structure.
      if (isset($permissions['permission_callbacks'])) {
        foreach ($permissions['permission_callbacks'] as $permission_callback) {
          $callback = $this->controllerResolver->getControllerFromDefinition($permission_callback);
          if ($callback_permissions = call_user_func($callback)) {
            // Add any callback permissions to the array of permissions. In case
            // of any conflict, the YAML ones will take precedence.
            foreach ($callback_permissions as $name => $callback_permission) {
              if (!is_array($callback_permission)) {
                $callback_permission = array(
                  'title' => $callback_permission,
                );
              }

              // Set the provider if none was specified.
              $callback_permission += ['provider' => $provider];

              $all_callback_permissions[$name] = $callback_permission;
            }
          }
        }

        unset($permissions['permission_callbacks']);
      }

      foreach ($permissions as &$permission) {
        if (!is_array($permission)) {
          $permission = array(
            'title' => $permission,
          );
        }

        // Set the provider if none was specified.
        $permission += ['provider' => $provider];
      }

      $all_permissions += $permissions;
    }

    // Combine all defined permissions and set the rest of the defaults.
    $full_permissions = $all_permissions + $all_callback_permissions;
    foreach ($full_permissions as &$permission) {
      $permission['title'] = $this->t($permission['title']);
      $permission['description'] = isset($permission['description']) ? $this->t($permission['description']) : '';
      $permission += [
        'restrict access' => FALSE,
        'warning' => !empty($permission['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        'allowed for' => ['anonymous', 'outsider', 'member'],
      ];
    }

    return $full_permissions;
  }

  /**
   * Sorts the given permissions by provider name and title.
   *
   * @param array $all_permissions
   *   The permissions to be sorted.
   *
   * @return array[]
   *   An array of permissions as described in ::getPermissions().
   *
   * @see \Drupal\group\Access\PermissionHandlerInterface::getPermissions()
   */
  protected function sortPermissions(array $all_permissions = array()) {
    $modules = $this->getModuleNames();

    // Sort all permissions by their providing module's display name.
    uasort($all_permissions, function (array $permission_a, array $permission_b) use ($modules) {
      if ($modules[$permission_a['provider']] == $modules[$permission_b['provider']]) {
        return $permission_a['title'] > $permission_b['title'];
      }
      else {
        return $modules[$permission_a['provider']] > $modules[$permission_b['provider']];
      }
    });

    return $all_permissions;
  }

  /**
   * Returns all module names.
   *
   * @return string[]
   *   Returns the human readable names of all modules keyed by machine name.
   */
  protected function getModuleNames() {
    $modules = array();
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $modules[$module] = $this->moduleHandler->getName($module);
    }
    asort($modules);
    return $modules;
  }

}
