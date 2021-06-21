<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default group permissions handler.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyPermissionProvider implements PermissionProviderInterface {

  use RelationHandlerTrait;

  /**
   * Constructs a new EmptyPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->parent->getAdminPermission();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    return $this->parent->buildPermissions();
  }

}
