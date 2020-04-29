<?php

namespace Drupal\group\Plugin;

/**
 * Provides a common interface for group content permission provides.
 */
interface GroupContentPermissionProviderInterface {

  /**
   * Gets the name of the admin permission.
   *
   * @return string|false
   *   The admin permission name or FALSE if none was set.
   */
  public function getAdminPermission();

  /**
   * Gets the name of the view permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getRelationViewPermission($scope = 'any');

  /**
   * Gets the name of the update permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getRelationUpdatePermission($scope = 'any');

  /**
   * Gets the name of the delete permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getRelationDeletePermission($scope = 'any');

  /**
   * Gets the name of the create permission for the relation.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getRelationCreatePermission();

  /**
   * Gets the name of the view permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getEntityViewPermission($scope = 'any');

  /**
   * Gets the name of the view unpublished permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getEntityViewUnpublishedPermission($scope = 'any');

  /**
   * Gets the name of the update permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getEntityUpdatePermission($scope = 'any');

  /**
   * Gets the name of the delete permission for the relation.
   *
   * @param string $scope
   *   (optional) Whether the 'any' or 'own' permission name should be returned.
   *   Defaults to 'any'.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getEntityDeletePermission($scope = 'any');

  /**
   * Gets the name of the create permission for the relation.
   *
   * @return string|false
   *   The permission name or FALSE if it does not apply.
   */
  public function getEntityCreatePermission();

  /**
   * Provides a list of group permissions the plugin exposes.
   *
   * If you have some group permissions that would only make sense when your
   * plugin is installed, you may define those here. They will not be shown on
   * the permission configuration form unless the plugin is installed.
   *
   * @return array
   *   An array of group permissions, see GroupPermissionHandlerInterface for
   *   the structure of a group permission.
   *
   * @see GroupPermissionHandlerInterface::getPermissions()
   */
  public function getPermissions();

}
