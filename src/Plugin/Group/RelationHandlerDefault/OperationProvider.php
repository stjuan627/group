<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for group relations.
 */
class OperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new OperationProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(GroupTypeInterface $group_type) {
    $operations = [];

    $ui_allowed = !$this->groupRelationType->isEnforced() && !$this->groupRelationType->isCodeOnly();
    if ($group_content_type_id = $this->getGroupContentTypeId($group_type)) {
      $route_params = ['group_content_type' => $group_content_type_id];
      $operations['configure'] = [
        'title' => $this->t('Configure'),
        'url' => new Url('entity.group_content_type.edit_form', $route_params),
      ];

      if ($ui_allowed) {
        $operations['uninstall'] = [
          'title' => $this->t('Uninstall'),
          'weight' => 99,
          'url' => new Url('entity.group_content_type.delete_form', $route_params),
        ];
      }

      // This could be in its own decorator, but then it would live in a module
      // of its own purely for field_ui support. So let's keep it here.
      if ($this->moduleHandler->moduleExists('field_ui')) {
        $group_content_type = $this->entityTypeManager()->getStorage('group_content_type')->load($group_content_type_id);
        $operations += field_ui_entity_operation($group_content_type);
      }
    }
    elseif ($ui_allowed) {
      $operations['install'] = [
        'title' => $this->t('Install'),
        'url' => new Url('entity.group_content_type.add_form', [
          'group_type' => $group_type->id(),
          'plugin_id' => $this->pluginId,
        ]),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    return [];
  }

}
