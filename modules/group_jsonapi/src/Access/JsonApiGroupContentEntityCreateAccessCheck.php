<?php

namespace Drupal\group_jsonapi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentType;
use Symfony\Component\Routing\Route;

/**
 * Group content entity creation access check for JSON:API calls.
 *
 * Special access check which act as a compatibility layer between JSON:API
 * routes and access check provided by the group module.
 *
 * It ensure that the required 'group' context properties is passed to group
 * access check.
 *
 * @see \Drupal\Core\Entity\EntityCreateAccessCheck
 * @see \Drupal\jsonapi\Routing\Routes
 * @see \Drupal\group\Entity\Access\GroupContentAccessControlHandler
 */
class JsonApiGroupContentEntityCreateAccessCheck implements AccessInterface {

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var \Drupal\Component\Serialization\Json */
  protected $json;

  /** @var \Symfony\Component\HttpFoundation\RequestStack */
  protected $requestStack;

  /**
   * JsonApiGroupContentEntityCreateAccessCheck constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Component\Serialization\Json $json
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(\Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager, \Drupal\Component\Serialization\Json $json, \Symfony\Component\HttpFoundation\RequestStack $requestStack) {
    $this->entityTypeManager = $entityTypeManager;
    $this->json = $json;
    $this->requestStack = $requestStack;
  }

  /**
   * Check handling method.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {

    $requestBody = $this->json->decode($this->requestStack->getCurrentRequest()->getContent());

    // TODO gid could be missing
    $group_uuid = $requestBody['data']['relationships']['gid']['data']['id'];
    // TODO getStorage() could throw an exception
    $groups = $this->entityTypeManager->getStorage('group')
      ->loadByProperties(['uuid' => $group_uuid]);

    // TODO loading of group entity could fail
    $group = reset($groups);

    /** @var \Drupal\jsonapi\ResourceType\ResourceType $resourceType */
    $resourceType = $route_match->getParameter('resource_type');
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $groupContentType */

    // TODO loading of group_type entity could fail
    $groupContentType = GroupContentType::load($resourceType->getBundle());

    // Fetch the access control hander of group_content entity and call it
    // with the required context data.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_content');
    $access = $access_control_handler->createAccess($groupContentType->id(), $account, ['group' => $group]);
    return AccessResult::allowedIf($access);

  }

}
