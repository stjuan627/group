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
   * The default permission provider.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface
   */
  protected $default;

  /**
   * Constructs a new FullEntityPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $default
   *   The default permission provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PermissionProviderInterface $default, EntityTypeManagerInterface $entity_type_manager) {
    $this->default = $default;
    $this->entityTypeManager = $entity_type_manager;
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
    // The view permissions support all scopes here.
    if ($target === 'entity' && $scope === 'own') {
      switch ($operation) {
        case 'view':
          return $this->getEntityViewOwnPermission();

        case 'view unpublished':
          return $this->getEntityViewOwnUnpublishedPermission();
      }
    }
    return $this->default->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->default->buildPermissions();

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
   * Gets the name of the view own permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewOwnPermission() {
    if ($this->definesEntityPermissions) {
      if ($this->implementsOwnerInterface) {
        return "view own $this->pluginId entity";
      }
    }
    return FALSE;
  }

  /**
   * Gets the name of the view own unpublished permission for the entity.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  protected function getEntityViewOwnUnpublishedPermission() {
    if ($this->definesEntityPermissions) {
      if ($this->implementsPublishedInterface) {
        if ($this->implementsOwnerInterface) {
          return "view own unpublished $this->pluginId entity";
        }
      }
    }
    return FALSE;
  }

}
