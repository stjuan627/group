<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Represents a calculated set of group permissions with cacheable metadata.
 *
 * @see \Drupal\group\Access\ChainGroupPermissionCalculator
 */
class RefinableCalculatedGroupPermissions implements RefinableCalculatedGroupPermissionsInterface {

  use CalculatedGroupPermissionsTrait;
  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function addItem(CalculatedGroupPermissionsItemInterface $item, $overwrite = FALSE) {
    if (!$overwrite && $existing = $this->getItem($item->getScope(), $item->getIdentifier())) {
      $item = $this->mergeItems($existing, $item);
    }
    $this->items[$item->getScope()][$item->getIdentifier()] = $item;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($scope, $identifier) {
    unset($this->items[$scope][$identifier]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItems() {
    $this->items = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItemsByScope($scope) {
    unset($this->items[$scope]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(CalculatedGroupPermissionsInterface $calculated_permissions) {
    foreach ($calculated_permissions->getItems() as $item) {
      $this->addItem($item);
    }
    $this->addCacheableDependency($calculated_permissions);
    return $this;
  }

  /**
   * Merges two items of identical scope and identifier.
   *
   * @param \Drupal\group\Access\CalculatedGroupPermissionsItemInterface $a
   *   The first item to merge.
   * @param \Drupal\group\Access\CalculatedGroupPermissionsItemInterface $b
   *   The second item to merge.
   *
   * @return \Drupal\group\Access\CalculatedGroupPermissionsItemInterface
   *   A new item representing the merger of both items.
   *
   * @throws \LogicException
   *   Exception thrown when someone somehow manages to call this method with
   *   mismatching items.
   */
  protected function mergeItems(CalculatedGroupPermissionsItemInterface $a, CalculatedGroupPermissionsItemInterface $b) {
    if ($a->getScope() != $b->getScope()) {
      throw new \LogicException('Trying to merge two items of different scopes.');
    }

    if ($a->getIdentifier() != $b->getIdentifier()) {
      throw new \LogicException('Trying to merge two items with different identifiers.');
    }

    // If either of the items is admin, the new one is too.
    $is_admin = $a->isAdmin() || $b->isAdmin();

    // Admin items don't need to have any permissions.
    $permissions = [];
    if (!$is_admin) {
      $permissions = array_unique(array_merge($a->getPermissions(), $b->getPermissions()));
    }

    return new CalculatedGroupPermissionsItem($a->getScope(), $a->getIdentifier(), $permissions, $is_admin);
  }

}
