<?php

namespace Drupal\group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Swaps out the revision page access callback.
 */
class LatestRevisionRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group.latest_version')) {
      $requirements = $route->getRequirements();
      unset($requirements['_content_moderation_latest_version']);
      $requirements['_group_moderation_latest_version'] = 'TRUE';
      $route->setRequirements($requirements);
    }
  }

}
