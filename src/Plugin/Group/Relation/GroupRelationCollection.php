<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of group relation plugins.
 */
class GroupRelationCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   *
   * Sorts plugins by provider.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);

    if ($a->getProvider() != $b->getProvider()) {
      return strnatcasecmp($a->getProvider(), $b->getProvider());
    }

    return parent::sortHelper($aID, $bID);
  }

}
