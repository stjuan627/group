<?php

namespace Drupal\group\Decorator;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Decorates the entity type manager to swap out content translation handlers.
 */
class EntityTypeManagerDecorator implements EntityTypeManagerInterface {

  /**
   * The decorated access check.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface
   */
  protected $inner;

  /**
   * Constructs an EntityTypeManagerDecorator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $inner
   *   The decorated access check.
   */
  public function __construct(EntityTypeManagerInterface $inner) {
    $this->inner = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($entity_type_id, $handler_type) {
    $handler = $this->inner->getHandler($entity_type_id, $handler_type);
    if ($handler_type == 'translation') {
      $handler = new ContentTranslationHandlerDecorator($handler);
    }
    return $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessControlHandler($entity_type_id) {
    return $this->inner->getAccessControlHandler($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage($entity_type_id) {
    return $this->inner->getStorage($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBuilder($entity_type_id) {
    return $this->inner->getViewBuilder($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getListBuilder($entity_type_id) {
    return $this->inner->getListBuilder($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject($entity_type_id, $operation) {
    return $this->inner->getFormObject($entity_type_id, $operation);
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteProviders($entity_type_id) {
    return $this->inner->getRouteProviders($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandler($entity_type_id, $handler_type) {
    return $this->inner->hasHandler($entity_type_id, $handler_type);
  }

  /**
   * {@inheritdoc}
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = NULL) {
    return $this->inner->createHandlerInstance($class, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    return $this->inner->getDefinition($entity_type_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->inner->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    return $this->inner->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    return $this->inner->useCaches($use_caches);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return $this->inner->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->inner->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->inner->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDefinition($entity_type_id) {
    return $this->inner->getActiveDefinition($entity_type_id);
  }

}
