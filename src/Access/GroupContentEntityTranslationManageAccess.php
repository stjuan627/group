<?php

namespace Drupal\group\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for grouped entity translation management.
 */
class GroupContentEntityTranslationManageAccess implements AccessInterface {

  /**
   * The decorated access check.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface
   */
  protected $inner;

  /**
   * Constructs a GroupContentEntityTranslationManageAccess object.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $inner
   *   The decorated access check.
   */
  public function __construct(AccessInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * Checks translation access for the entity and operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $source
   *   (optional) For a create operation, the language code of the source.
   * @param string $target
   *   (optional) For a create operation, the language code of the translation.
   * @param string $language
   *   (optional) For an update or delete operation, the language code of the
   *   translation being updated or deleted.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Access\AccessResultInterface $access */
    $access = $this->inner->access($route, $route_match, $account, $source, $target, $language, $entity_type_id);

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
