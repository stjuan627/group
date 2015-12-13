<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnablerCollection.
 */

namespace Drupal\group\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of group content plugins.
 */
class GroupContentEnablerCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
