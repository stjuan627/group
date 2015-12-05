<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentInterface.
 */

namespace Drupal\group\Plugin;

/**
 * Defines an interface for pluggable GroupContent back-ends.
 *
 * @see \Drupal\group\Annotation\GroupContent
 * @see \Drupal\group\GroupContentPluginManager
 * @see \Drupal\group\Plugin\GroupContentBase
 * @see plugin_api
 */
interface GroupContentInterface {

  /**
   * Returns the administrative label for this filter plugin.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the administrative description for this filter plugin.
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
