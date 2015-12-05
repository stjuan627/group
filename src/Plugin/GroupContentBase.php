<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentBase.
 */

namespace Drupal\group\Plugin;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for GroupContent plugins.
 *
 * @see \Drupal\group\Annotation\GroupContent
 * @see \Drupal\group\GroupContentPluginManager
 * @see \Drupal\group\Plugin\GroupContentInterface
 * @see plugin_api
 */
abstract class GroupContentBase extends PluginBase implements GroupContentInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

}
