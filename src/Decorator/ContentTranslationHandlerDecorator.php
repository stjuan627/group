<?php

namespace Drupal\group\Decorator;

use Drupal\content_translation\ContentTranslationHandlerInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Decorates the content translation handler.
 */
class ContentTranslationHandlerDecorator implements ContentTranslationHandlerInterface, EntityHandlerInterface {

  /**
   * The decorated translation handler.
   *
   * @var \Drupal\content_translation\ContentTranslationHandlerInterface
   */
  protected $inner;

  /**
   * Constructs an ContentTranslationHandlerDecorator object.
   *
   * @param \Drupal\content_translation\ContentTranslationHandlerInterface $inner
   *   The decorated translation handler.
   */
  public function __construct(ContentTranslationHandlerInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationAccess(EntityInterface $entity, $op) {
    $access = $this->inner->getTranslationAccess($entity, $op);
    $access = $access->orIf($entity->access('translate', \Drupal::currentUser(), TRUE));
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return $this->inner->getFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangcode(FormStateInterface $form_state) {
    return $this->inner->getSourceLangcode($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function retranslate(EntityInterface $entity, $langcode = NULL) {
    return $this->inner->retranslate($entity, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    return $this->inner->entityFormAlter($form, $form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    // Should never be called because we don't swap out the handler class.
  }

}
