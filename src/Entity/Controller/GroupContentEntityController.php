<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an GroupContentEntityController object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->controllerResolver = $controller_resolver;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('controller_resolver'),
      $container->get('entity_type.manager'),
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

  // @todo Document the below routes.
  // @todo Join the below two forms into one with a _default route operation.
  public function editForm(GroupContentInterface $group_content) {
    $entity = $group_content->getEntity();
    $operation = $entity->getEntityType()->getFormClass('edit') ? 'edit' : 'default';
    $form_object = $this->entityTypeManager->getFormObject($entity->getEntityTypeId(), $operation);
    $form_object->setEntity($entity);
    return $form_object;
  }

  public function editTitle(GroupContentInterface $group_content) {
    return $this->t('Edit %label', ['%label' => $group_content->getEntity()->label()]);
  }

  public function deleteForm(GroupContentInterface $group_content) {
    $entity = $group_content->getEntity();
    $form_object = $this->entityTypeManager->getFormObject($entity->getEntityTypeId(), 'delete');
    $form_object->setEntity($entity);
    return $form_object;
  }

  public function deleteTitle(GroupContentInterface $group_content) {
    return $this->t('Delete %label', ['%label' => $group_content->getEntity()->label()]);
  }

}
