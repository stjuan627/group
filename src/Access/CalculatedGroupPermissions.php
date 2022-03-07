<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\CacheableDependencyTrait;

/**
 * Represents a calculated set of group permissions with cacheable metadata.
 *
 * @see \Drupal\group\Access\ChainGroupPermissionCalculator
 */
class CalculatedGroupPermissions implements CalculatedGroupPermissionsInterface {

  use CacheableDependencyTrait;
  use CalculatedGroupPermissionsTrait;

  /**
   * Constructs a new CalculatedGroupPermissions.
   *
   * @param \Drupal\group\Access\CalculatedGroupPermissionsInterface $source
   *   The calculated group permission to create a value object from.
   */
  public function __construct(CalculatedGroupPermissionsInterface $source) {
    foreach ($source->getItems() as $item) {
      $this->items[$item->getScope()][$item->getIdentifier()] = $item;
    }
    $this->setCacheability($source);

    // The (persistent) cache contexts attached to the permissions are only
    // used internally to store the permissions in the VariationCache. We strip
    // these cache contexts when the calculated permissions get converted into a
    // value object here so that they will never bubble up by accident.
    $this->cacheContexts = [];
  }

}
