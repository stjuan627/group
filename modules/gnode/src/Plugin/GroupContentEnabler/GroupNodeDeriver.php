<?php

namespace Drupal\gnode\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Derives group nodes based on configuration.
 *
 * This class extends `DeriverBase` and is responsible for deriving group
 * nodes from configuration data, enabling dynamic management and
 * generation of group-related content.
 */
class GroupNodeDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   *
   * Provides derivative definitions based on the base plugin definition.
   * Allows for dynamic configuration of plugin instances.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach (NodeType::loadMultiple() as $name => $node_type) {
      $label = $node_type->label();

      $this->derivatives[$name] = [
        'entity_bundle' => $name,
        'label' => $this->t('Group node (@type)', ['@type' => $label]),
        'description' => $this->t('Adds %type content to groups both publicly and privately.', ['%type' => $label]),
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
