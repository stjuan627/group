<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a common interface for group relation handlers.
 */
interface RelationHandlerInterface {

  /**
   * Initializes the handler.
   *
   * @param string $plugin_id
   *   The group relation plugin ID. Note: This is the actual plugin ID,
   *   including any potential derivative ID. To get the base plugin ID, you
   *   should use $definition['id'].
   * @param array $definition
   *   The group relation plugin definition.
   *
   * @todo Plugin definition should become a class.
   */
  public function init($plugin_id, array $definition);

}
