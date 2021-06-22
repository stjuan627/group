<?php

namespace Drupal\group_test_plugin\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides all possible permissions.
 */
class FullEntityPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new FullEntityPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent permission provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PermissionProviderInterface $parent, EntityTypeManagerInterface $entity_type_manager) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
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
    // The view permissions support all scopes here.
    if ($target === 'entity') {
      switch ($operation) {
        case 'view':
          return $this->getEntityViewPermission($scope);

        case 'view unpublished':
          return $this->getEntityViewUnpublishedPermission($scope);
      }
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->parent->buildPermissions();

    // Rename view any permissions.
    if ($name = $this->parent->getPermission('view', 'entity')) {
      $permissions[$this->getPermission('view', 'entity')] = $permissions[$name];
      unset($permissions[$name]);
    }
    if ($name = $this->parent->getPermission('view unpublished', 'entity')) {
      $permissions[$this->getPermission('view unpublished', 'entity')] = $permissions[$name];
      unset($permissions[$name]);
    }

    // Support view own permissions.
    $prefix = 'Entity:';
    if ($name = $this->getPermission('view', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own %entity_type entities");
    }
    if ($name = $this->getPermission('view unpublished', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own unpublished %entity_type entities");
    }

    return $permissions;
  }

  /**
   * Gets the name of the view permission for the entity.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewPermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "view $scope $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * Gets the name of the view unpublished permission for the entity.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewUnpublishedPermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "view $scope unpublished $this->pluginId entity";
      }
    }
    return FALSE;
  }

}
