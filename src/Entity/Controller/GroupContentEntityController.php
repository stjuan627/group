<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for GroupContent entity routes.
 */
class GroupContentEntityController implements ContainerInjectionInterface {

  /**
   * The controller resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Creates an GroupContentEntityController object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver) {
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('controller_resolver')
    );
  }

  /**
   * Provides a page to render a group content's target entity.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group to add the group content to.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function view(GroupContentInterface $group_content, $view_mode = 'full') {
    $callback = $this->controllerResolver->getControllerFromDefinition('\Drupal\Core\Entity\Controller\EntityViewController::view');
    return $callback($group_content->getEntity(), $view_mode);
  }

  /**
   * The _title_callback for the entity.group_content.entity_view route.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group to create the group content in.
   *
   * @return string
   *   The page title.
   */
  public function viewTitle(GroupContentInterface $group_content) {
    return $group_content->getEntity()->label();
  }

}
