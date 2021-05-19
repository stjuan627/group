<?php

namespace Drupal\group;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\group\Plugin\GroupContentEnablerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines the group content fields installer service.
 */
class GroupContentFieldsInstaller implements GroupContentFieldsInstallerInterface {

  use StringTranslationTrait;

  const STATUS_FIELD_CONFIG = [
    'field_name' => 'status',
    'entity_type' => 'group_content',
    'type' => 'boolean',
    'locked' => TRUE,
    'cardinality' => 1,
    'translatable' => FALSE,
    'persist_with_no_fields' => TRUE,
  ];

  const ENTITY_TYPE_ID = 'group_content';
  const STATUS_FIELD = 'status';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $configInstaller;

  /**
   * Constructs a new Group Content Enabler plugin object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigInstallerInterface $config_installer
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configInstaller = $config_installer;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldInstallStatus(GroupContentEnablerInterface $plugin) {
    // Add status field for publishable entity types.
    $definition = $this->entityTypeManager->getDefinition($plugin->getEntityTypeId());
    if ($definition->entityClassImplements(EntityPublishedInterface::class)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function installStatusField(GroupContentEnablerInterface $plugin) {
    // Only create config objects while config import is not in progress.
    if ($this->configInstaller->isSyncing()) {
      return;
    }

    $group_content_type_id = $plugin->getContentTypeConfigId();

    // Add the status field to the newly added group content type. The
    // field storage for this is defined in the config/install folder.
    $field_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $storage_config = $field_storage->load(static::ENTITY_TYPE_ID . '.' . static::STATUS_FIELD);
    if (!$storage_config) {
      $storage_config = $field_storage->create(static::STATUS_FIELD_CONFIG);
      $storage_config->save();
    }
    $field_config = $this->entityTypeManager->getStorage('field_config');
    $field_config->create([
      'field_storage' => $storage_config,
      'bundle' => $group_content_type_id,
      'label' => $this->t('Status'),
      'default_value' => [0 => ['value' => TRUE]],
    ])->save();

    // Build or retrieve the 'default' form mode.
    $form_display_storage = $this->entityTypeManager->getStorage('entity_form_display');
    if (!$form_display = $form_display_storage->load("group_content.$group_content_type_id.default")) {
      $form_display = $form_display_storage->create([
        'targetEntityType' => static::ENTITY_TYPE_ID,
        'bundle' => $group_content_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Assign widget settings for the 'default' form mode.
    $form_display->setComponent(static::STATUS_FIELD, [
      'type' => 'boolean_checkbox',
      'settings' => [
        'display_label' => TRUE,
      ],
    ])->save();
  }

}
