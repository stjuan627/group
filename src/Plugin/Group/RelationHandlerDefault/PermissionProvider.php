<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides group permissions for group relation plugins.
 */
class PermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new PermissionProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->definition['admin_permission'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    assert(in_array($target, ['relation', 'entity'], TRUE), '$target must be either "relation" or "entity"');
    assert(in_array($scope, ['any', 'own'], TRUE), '$target must be either "relation" or "entity"');

    if ($target === 'relation') {
      switch ($operation) {
        case 'view':
          return $this->getRelationViewPermission($scope);
        case 'update':
          return $this->getRelationUpdatePermission($scope);
        case 'delete':
          return $this->getRelationDeletePermission($scope);
        case 'create':
          return $this->getRelationCreatePermission();
      }
    }
    elseif ($target === 'entity') {
      switch ($operation) {
        case 'view':
          return $this->getEntityViewPermission($scope);
        case 'view unpublished':
          return $this->getEntityViewUnpublishedPermission($scope);
        case 'update':
          return $this->getEntityUpdatePermission($scope);
        case 'delete':
          return $this->getEntityDeletePermission($scope);
        case 'create':
          return $this->getEntityCreatePermission();
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = [];

    // Provide permissions for the relation (i.e.: The group content entity).
    $prefix = 'Relation:';
    if ($name = $this->getAdminPermission()) {
      $permissions[$name] = $this->buildPermission("$prefix Administer relations");
      $permissions[$name]['restrict access'] = TRUE;
    }

    if ($name = $this->getPermission('view', 'relation')) {
      $permissions[$name] = $this->buildPermission("$prefix View any entity relations");
    }
    if ($name = $this->getPermission('view', 'relation', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own entity relations");
    }
    if ($name = $this->getPermission('update', 'relation')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit any entity relations");
    }
    if ($name = $this->getPermission('update', 'relation', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit own entity relations");
    }
    if ($name = $this->getPermission('delete', 'relation')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete any entity relations");
    }
    if ($name = $this->getPermission('delete', 'relation', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete own entity relations");
    }

    if ($name = $this->getPermission('create', 'relation')) {
      $permissions[$name] = $this->buildPermission(
        "$prefix Add entity relations",
        'Allows you to add an existing %entity_type entity to the group.'
      );
    }

    // Provide permissions for the actual entity being added to the group.
    $prefix = 'Entity:';
    if ($name = $this->getPermission('view', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix View any %entity_type entities");
    }
    if ($name = $this->getPermission('view', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own %entity_type entities");
    }
    if ($name = $this->getPermission('view unpublished', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix View any unpublished %entity_type entities");
    }
    if ($name = $this->getPermission('view unpublished', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix View own unpublished %entity_type entities");
    }
    if ($name = $this->getPermission('update', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit any %entity_type entities");
    }
    if ($name = $this->getPermission('update', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Edit own %entity_type entities");
    }
    if ($name = $this->getPermission('delete', 'entity')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete any %entity_type entities");
    }
    if ($name = $this->getPermission('delete', 'entity', 'own')) {
      $permissions[$name] = $this->buildPermission("$prefix Delete own %entity_type entities");
    }

    if ($name = $this->getPermission('create', 'entity')()) {
      $permissions[$name] = $this->buildPermission(
        "$prefix Add %entity_type entities",
        'Allows you to create a new %entity_type entity and add it to the group.'
      );
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationViewPermission($scope = 'any') {
    // @todo Implement view own permission.
    if ($scope === 'any') {
      // Backwards compatible permission name for 'any' scope.
      return "view $this->pluginId content";
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationUpdatePermission($scope = 'any') {
    return "update $scope $this->pluginId content";
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationDeletePermission($scope = 'any') {
    return "delete $scope $this->pluginId content";
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationCreatePermission() {
    return "create $this->pluginId content";
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityViewPermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      // @todo Implement view own permission.
      if ($scope === 'any') {
        // Backwards compatible permission name for 'any' scope.
        return "view $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityViewUnpublishedPermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsPublishedInterface) {
        // @todo Implement view own unpublished permission and add it here by
        // checking for $this->implementsOwnerInterface.
        if ($scope === 'any') {
          return "view $scope unpublished $this->pluginId entity";
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityUpdatePermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "update $scope $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDeletePermission($scope = 'any') {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface || $scope === 'any') {
        return "delete $scope $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCreatePermission() {
    if ($this->definesEntityPermissions) {
      return "create $this->pluginId entity";
    }
    return FALSE;
  }

}
