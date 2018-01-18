<?php

namespace Drupal\group\Entity\Enhancer;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhances a group content entity route with the appropriate controller.
 */
class GroupContentEntityRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!empty($defaults['_group_content_entity_form'])) {
      $defaults = $this->enhanceEntityForm($defaults, $request);
    }
    elseif (!empty($defaults['_group_content_entity_view'])) {
      $defaults = $this->enhanceEntityView($defaults, $request);
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return !$route->hasDefault('_controller') &&
      ($route->hasDefault('_group_content_entity_form')
        || $route->hasDefault('_group_content_entity_view')
      );
  }

  /**
   * Update defaults for group content entity forms.
   *
   * @param array $defaults
   *   The defaults to modify.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults.
   */
  protected function enhanceEntityForm(array $defaults, Request $request) {
    $defaults['_controller'] = 'controller.group_content.entity_form:getContentResult';
    unset($defaults['_group_content_entity_form']);
    return $defaults;
  }

  /**
   * Update defaults for a group content entity view.
   *
   * @param array $defaults
   *   The defaults to modify.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request instance.
   *
   * @return array
   *   The modified defaults.
   *
   * @throws \RuntimeException
   *   Thrown when an entity of a type cannot be found in a route.
   */
  protected function enhanceEntityView(array $defaults, Request $request) {
    $defaults['_controller'] = '\Drupal\group\Entity\Controller\GroupContentEntityController::view';
    $defaults['view_mode'] = $defaults['_group_content_entity_view'];
    unset($defaults['_group_content_entity_view']);
    return $defaults;
  }

}
