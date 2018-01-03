<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for group content target entities.
 */
class GroupContentEntityAccessCheck implements AccessInterface {

  /**
   * Checks target entity access for group content routes.
   *
   * All routes using this access check should have a group content parameter
   * and have the _group_content_entity_access requirement set to the name of
   * the operation to check access for.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content to retrieve the target entity from.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupContentInterface $group_content) {
    $operation = $route->getRequirement('_group_content_entity_access');

    // We first check if the user can access the entity. If another module is
    // denying access, we do not need to check for the group permission.
    if (!$group_content->getEntity()->access($operation, $account)) {
      return AccessResult::forbidden();
    }

    // @todo Distinguish ANY vs OWN. Retrieve both permissions.
    // Retrieve the permission name from the plugin and check whether the user
    // has said permission in the group.
    $permission = $group_content->getContentPlugin()->getEntityOperationPermission($operation);
    if ($permission !== FALSE) {
      return AccessResult::allowedIf($group_content->getGroup()->hasPermission($permission, $account));
    }

    // If we got this far, it means we could not retrieve a permission name and
    // as such should default to the most sane option we have left: Deny access.
    return AccessResult::forbidden();
  }

}
