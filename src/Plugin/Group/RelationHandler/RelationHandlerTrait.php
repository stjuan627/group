<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Trait for group relation plugin handlers.
 *
 * This trait contains a few service getters for services that are often needed
 * in plugin handlers. When using one of these getters, please make sure you
 * inject the dependency into the corresponding property from within your
 * service's constructor.
 */
trait RelationHandlerTrait {

  /**
   * The plugin ID as read from the definition.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin definition.
   *
   * @var array
   *
   * @todo Plugin definition should become a class.
   */
  protected $definition;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation manager.
   *
   * @var \Drupal\group\Plugin\GroupRelationManagerInterface
   */
  protected $groupRelationManager;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, array $definition) {
    $this->pluginId = $plugin_id;
    $this->definition = $definition;
  }

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function entityTypeManager() {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

  /**
   * Gets the group relation manager service.
   *
   * @return \Drupal\group\Plugin\GroupRelationManagerInterface
   *   The group relation manager service.
   */
  protected function groupRelationManager() {
    if (!$this->groupRelationManager) {
      $this->groupRelationManager = \Drupal::service('plugin.manager.group_content_enabler');
    }
    return $this->groupRelationManager;
  }

}
