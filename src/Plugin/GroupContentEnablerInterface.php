<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerInterface.
 */

namespace Drupal\group\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for pluggable GroupContentEnabler back-ends.
 *
 * @see \Drupal\group\Annotation\GroupContentEnabler
 * @see \Drupal\group\GroupContentEnablerManager
 * @see \Drupal\group\Plugin\GroupContentEnablerBase
 * @see plugin_api
 */
interface GroupContentEnablerInterface extends ConfigurablePluginInterface {

  /**
   * Returns the plugin provider.
   *
   * @return string
   */
  public function getProvider();

  /**
   * Returns the unique ID representing the plugin instance.
   *
   * @return string
   *   The image effect ID.
   */
  public function getUuid();

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

}
