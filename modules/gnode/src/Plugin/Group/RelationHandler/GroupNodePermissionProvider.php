<?php

namespace Drupal\gnode\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\RelationHandlerTrait;

/**
 * Provides group permissions for the group_node relation plugin.
 */
class GroupNodePermissionProvider implements PermissionProviderInterface {

  use RelationHandlerTrait;

  /**
   * The default permission provider.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface
   */
  protected $default;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
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
  public function getAdminPermission() {
    return $this->default->getAdminPermission();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    // Backwards compatible permission name for 'any' scope.
    if ($operation === 'view unpublished' && $target === 'entity' && $scope === 'any') {
      return "$operation $this->pluginId $target";
    }
    return $this->default->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->default->buildPermissions();

    // Rename the view any unpublished entity permission.
    if ($name = $this->default->getPermission('view', 'entity', 'any')) {
      $permissions[$this->getPermission('view', 'entity', 'any')] = $permissions[$name];
      unset($permissions[$name]);
    }

    return $permissions;
  }

}
