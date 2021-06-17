<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Entity\GroupInterface;

/**
 * Trait for group relation permission providers.
 *
 * This trait takes care of common logic for permission providers. Please make
 * sure your handler service asks for the entity_type.manager service and sets
 * to the $this->entityTypeManager property in its constructor.
 */
trait AccessControlTrait {

  use RelationHandlerTrait {
    init as traitInit;
  }

  /**
   * The plugin's permission provider.
   *
   * @var \Drupal\group\Plugin\GroupContentPermissionProviderInterface
   */
  protected $permissionProvider;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, array $definition) {
    $this->traitInit($plugin_id, $definition);
    $this->permissionProvider = $this->groupRelationManager()->getPermissionProvider($plugin_id);
  }

  /**
   * Checks the provided permission alongside the admin permission.
   *
   * Important: Only one permission needs to match.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to check for access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   * @param string $permission
   *   The names of the permission to check for.
   * @param bool $return_as_object
   *   Whether to return the result as an object or boolean.
   *
   * @return bool|\Drupal\Core\Access\AccessResult
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  protected function combinedPermissionCheck(GroupInterface $group, AccountInterface $account, $permission, $return_as_object) {
    $result = AccessResult::neutral();

    // Add in the admin permission and filter out the unsupported permissions.
    $permissions = [$permission, $this->permissionProvider->getAdminPermission()];
    $permissions = array_filter($permissions);

    // If we still have permissions left, check for access.
    if (!empty($permissions)) {
      $result = GroupAccessResult::allowedIfHasGroupPermissions($group, $account, $permissions, 'OR');
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

}
