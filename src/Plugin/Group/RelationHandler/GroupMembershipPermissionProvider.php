<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides group permissions for the group_membership relation plugin.
 */
class GroupMembershipPermissionProvider implements PermissionProviderInterface {

  use RelationHandlerTrait;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The default permission provider.
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
    // The following permissions are handled by the admin permission or have a
    // different permission name.
    if ($target === 'relation') {
      switch ($operation) {
        case 'create':
          return FALSE;

        case 'delete':
          return $scope === 'own' ? 'leave group' : FALSE;

        case 'update':
          if ($scope === 'any') {
            return FALSE;
          }
          break;
      }
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->parent->buildPermissions();

    // Add in the join group permission.
    $permissions['join group'] = [
      'title' => 'Join group',
      'allowed for' => ['outsider'],
    ];

    // Alter the update own permission.
    if ($name = $this->parent->getPermission('update', 'relation', 'own')) {
      $permissions[$name]['title'] = 'Edit own membership';
      $permissions[$name]['allowed for'] = ['member'];
    }

    // Alter and rename the delete own permission.
    if ($name = $this->parent->getPermission('delete', 'relation', 'own')) {
      $permissions[$name]['title'] = 'Leave group';
      $permissions[$name]['allowed for'] = ['member'];
      $permissions[$this->getPermission('delete', 'relation', 'own')] = $permissions[$name];
      unset($permissions[$name]);
    }

    // The following permissions are handled by the admin permission.
    foreach (['create', 'update', 'delete'] as $operation) {
      if ($name = $this->parent->getPermission($operation, 'relation')) {
        unset($permissions[$name]);
      }
    }

    // Update the labels of the default permissions.
    $permissions[$this->getAdminPermission()]['title'] = 'Administer group members';
    $permissions[$this->getPermission('view', 'relation')]['title'] = 'View individual group members';

    return $permissions;
  }

}
