<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentPluginCollection.
 */

namespace Drupal\group\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of image effects.
 */
class GroupContentPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\group\Plugin\GroupContentInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
