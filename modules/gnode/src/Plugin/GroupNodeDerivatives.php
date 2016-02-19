<?php

/**
 * @file
 * Contains \Drupal\gnode\Plugin\GroupNodeDerivatives.
 */

namespace Drupal\gnode\Plugin;

use Drupal\node\Entity\NodeType;
use Drupal\Component\Plugin\Derivative\DeriverInterface;

class GroupNodeDerivatives implements DeriverInterface {

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);
    if (isset($derivatives[$derivative_id])) {
      return $derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];

    // @todo This will define the common routes multiple times. Try to avoid.
    foreach (NodeType::loadMultiple() as $name => $node_type) {
      $label = $node_type->label();

      $derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => t('Group node') . " ($label)",
        'description' => t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $derivatives;
  }

}
