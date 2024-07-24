<?php

namespace Drupal\group_test_plugin\Plugin\GroupContentEnabler;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Derives node content types for group content.
 *
 * This class is responsible for deriving node content types that can be
 * used as group content. It extends the DeriverBase class to provide
 * dynamic content type handling.
 */
class NodeAsContentDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * Provides derivative definitions based on the base plugin definition.
   *
   * This method generates dynamic configurations for plugin instances by
   * extending the base plugin definition.
   *
   * @param array $base_plugin_definition
   *   The base plugin definition.
   *
   * @return array
   *   An array of derivative definitions.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $this->derivatives['page'] = [
      'entity_bundle' => 'page',
      'label' => $this->t('Pages as content'),
      'description' => $this->t('Adds pages to groups.'),
    ] + $base_plugin_definition;

    $this->derivatives['article'] = [
      'entity_bundle' => 'article',
      'label' => $this->t('Article as content'),
      'description' => $this->t('Adds articles to groups.'),
    ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
