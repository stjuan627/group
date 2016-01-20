<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerInterface.
 */

namespace Drupal\group\Plugin;

use Drupal\group\Entity\GroupContentInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for pluggable GroupContentEnabler back-ends.
 *
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\GroupContentEnablerManager
 * @see \Drupal\group\Plugin\GroupContentEnablerBase
 * @see plugin_api
 */
interface GroupContentEnablerInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Returns the plugin provider.
   *
   * @return string
   */
  public function getProvider();

  /**
   * Returns the administrative label for the plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the administrative description for the plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Returns the entity type ID the plugin supports.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns a path defined by the plugin.
   *
   * @var string $name
   *   The name (key) of the path as defined in the plugin annotation.
   *
   * @return string
   */
  public function getPath($name);

  /**
   * Returns the ID of the group type the plugin was instantiated for.
   *
   * @return string|null
   *   The group type ID, if set in the plugin configuration.
   */
  public function getGroupTypeId();

  /**
   * Returns whether this plugin is always on.
   *
   * @return bool
   *   The 'enforced' status.
   */
  public function isEnforced();

  /**
   * Retrieves the label for a piece of group content.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *
   * @return string
   *   The label as expected by \Drupal\Core\Entity\EntityInterface::label().
   */
  public function getContentLabel(GroupContentInterface $group_content);

  /**
   * Returns a safe, unique configuration ID for a group content type.
   *
   * By default we use GROUP_TYPE_ID.PLUGIN_ID.DERIVATIVE_ID, but feel free to
   * use any other means of identifying group content types. Make sure you also
   * provide a configuration schema should you diverge from the default
   * group.content_type.*.* or group_content_type.*.*.* schema.
   *
   * Please do not return any invalid characters in the ID as it will crash the
   * website. Refer to ConfigBase::validateName() for valid characters.

   * @return string
   *   The safe ID to use as the configuration name.
   *
   * @see \Drupal\Core\Config\ConfigBase::validateName()
   */
  public function getContentTypeConfigId();

  /**
   * Returns the administrative label for a group content type.
   *
   * @return string
   */
  public function getContentTypeLabel();

  /**
   * Returns the administrative description for a group content type.
   *
   * @return string
   */
  public function getContentTypeDescription();

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @return array
   *   An associative array of operation links to show on the group type content
   *   administration UI, keyed by operation name, containing the following
   *   key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations();

  /**
   * Provides a list of group permissions the plugin exposes.
   *
   * If you have some group permissions that would only make sense when your
   * plugin is installed, you may define those here. They will not be shown on
   * the permission configuration form unless the plugin is installed.
   *
   * @return array
   *   An array of group permissions, see GroupPermissionHandlerInterface for
   *   the structure of a group permission.
   *
   * @see GroupPermissionHandlerInterface::getPermissions()
   */
  public function getPermissions();

  /**
   * Provides routes for GroupContent entities.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of routes keyed by name.
   */
  public function getRoutes();

  /**
   * Run tasks after the group content type for this plugin has been created.
   *
   * A good example of what you might want to do here, is the installation of
   * extra locked fields on the group content type. You can find an example in
   * \Drupal\group\Plugin\GroupContentEnabler\GroupMembership::postInstall().
   */
  public function postInstall();

}
