<?php

namespace Drupal\group\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for grouped entity translation overview.
 */
class GroupContentEntityTranslationOverviewAccess implements AccessInterface {

  /**
   * The decorated access check.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface
   */
  protected $inner;

  /**
   * Constructs a GroupContentEntityTranslationOverviewAccess object.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $inner
   *   The decorated access check.
   */
  public function __construct(AccessInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * Checks access to the translation overview for the entity and bundle.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id) {
    /** @var \Drupal\Core\Access\AccessResultInterface $access */
    $access = $this->inner->access($route_match, $account, $entity_type_id);

    // If we were to define this access check as a tagged service, the result
    // would be andIf()ed to the original service's result. In that case, an
    // AccessResultAllowed would still not grant access if the original service
    // returned AccessResultNeutral. So we decorate the original and orIf() our
    // entity access check to that service's result instead.
    if ($entity = $route_match->getParameter($entity_type_id)) {
      /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $access = $access->orIf($entity->access('translate', $account, TRUE));
    }

    return $access;
  }

}
