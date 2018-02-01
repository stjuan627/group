<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
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
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Creates an GroupContentEntityController object.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, EntityFormBuilderInterface $entity_form_builder, TranslationInterface $string_translation) {
    $this->controllerResolver = $controller_resolver;
    $this->entityFormBuilder = $entity_form_builder;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('controller_resolver'),
      $container->get('entity.form_builder'),
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
   * Builds the entity edit form for the target entity.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to retrieve the target entity from.
   *
   * @return array
   *   The target entity edit form.
   */
  public function editForm(GroupContentInterface $group_content) {
    $entity = $group_content->getEntity();
    $operation = $entity->getEntityType()->getFormClass('edit') ? 'edit' : 'default';
    $extra = $this->getFormStateValues($group_content, $operation);
    return $this->entityFormBuilder->getForm($entity, $operation, $extra);
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
  public function editFormTitle(GroupContentInterface $group_content) {
    return $this->t('Edit %label', ['%label' => $group_content->getEntity()->label()]);
  }

  /**
   * Builds the entity delete form for the target entity.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to retrieve the target entity from.
   *
   * @return array
   *   The target entity delete form.
   */
  public function deleteForm(GroupContentInterface $group_content) {
    $extra = $this->getFormStateValues($group_content, 'delete');
    return $this->entityFormBuilder->getForm($group_content->getEntity(), 'delete', $extra);
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
  public function deleteFormTitle(GroupContentInterface $group_content) {
    return $this->t('Delete %label', ['%label' => $group_content->getEntity()->label()]);
  }

  /**
   * Builds extra form state values we can use to track this form.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to track.
   * @param string $operation
   *   The operation to track.
   *
   * @return array
   *   The extra form state values.
   */
  protected function getFormStateValues(GroupContentInterface $group_content, $operation) {
    return [
      'entity_form_in_group_scope' => TRUE,
      'group_content' => $group_content,
      'group' => $group_content->getGroup(),
    ];
  }

}
