<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for GroupContent entity routes.
 */
class GroupContentEntityController implements ContainerInjectionInterface {

  use StringTranslationTrait;

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
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TranslationInterface $string_translation) {
    $this->controllerResolver = $controller_resolver;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('controller_resolver'),
      $container->get('string_translation')
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

  /**
   * Provides the page title for the target entity edit form.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to retrieve the target entity from.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function editTitle(GroupContentInterface $group_content) {
    return $this->t('Edit %label', ['%label' => $group_content->getEntity()->label()]);
  }

  /**
   * Provides the page title for the target entity delete form.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to retrieve the target entity from.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function deleteTitle(GroupContentInterface $group_content) {
    return $this->t('Delete %label', ['%label' => $group_content->getEntity()->label()]);
  }

}
