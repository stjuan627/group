<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default group permissions handler.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyPermissionProvider implements PermissionProviderInterface {

  /**
   * The default plugin handler.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface
   */
  protected $default;

  /**
   * Constructs a new EmptyPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $default
   *   The default permission provider.
   */
  public function __construct(PermissionProviderInterface $default) {
    $this->default = $default;
  }

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, array $definition) {
    // Intentionally left blank.
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->default->getAdminPermission();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    return $this->default->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    return $this->default->buildPermissions();
  }

}
