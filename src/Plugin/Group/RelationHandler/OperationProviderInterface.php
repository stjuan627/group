<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a common interface for group relation operation providers.
 */
interface OperationProviderInterface extends RelationHandlerInterface {

  /**
   * Gets the list of operations for the group relation.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to provide the group relation operations for.
   *
   * @return array
   *   An associative array of operation links for the group type's content
   *   plugin, keyed by operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  public function getOperations(GroupTypeInterface $group_type);

}
