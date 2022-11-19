<?php

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Context\GroupRouteContextTrait;

/**
 * Defines a cache context for "per group content from route" caching.
 *
 * Cache context ID: 'route.group_content'.
 */
class RouteGroupContentCacheContext implements CacheContextInterface {

  use GroupRouteContextTrait;

  /**
   * Constructs a new RouteGroupCacheContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group Content from route');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($group_content = $this->getGroupContentFromRoute()) {
      // If a group content was found on the route, returns group content ID.
      return $group_content->id();
    }

    // If no group content was found on the route.
    return 'group_content.none';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
