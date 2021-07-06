<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for GroupContentEnabler plugins.
 *
 * @see \Drupal\group\Annotation\GroupRelation
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationManager
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
 * @see plugin_api
 */
abstract class GroupRelationBase extends PluginBase implements GroupRelationInterface {

  /**
   * The ID of group type this plugin was instantiated for.
   *
   * @var string
   */
  protected $groupTypeId;

  /**
   * Backwards compatible permission array.
   *
   * @var array
   */
  private $_permissions;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Only support setting the group type ID during construction.
    if (!empty($configuration['group_type_id'])) {
      $this->groupTypeId = $configuration['group_type_id'];
    }

    // Include the default configuration by calling ::setConfiguration().
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type_id'];
  }

  /**
   * Returns the entity type definition the plugin supports.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  protected function getEntityType() {
    return \Drupal::entityTypeManager()->getDefinition($this->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle() {
    return $this->pluginDefinition['entity_bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrettyPathKey() {
    return $this->pluginDefinition['pretty_path_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupCardinality() {
    return $this->configuration['group_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCardinality() {
    return $this->configuration['entity_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    if ($id = $this->getGroupTypeId()) {
      return GroupType::load($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->groupTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function definesEntityAccess() {
    return $this->pluginDefinition['entity_access'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnforced() {
    return $this->pluginDefinition['enforced'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCodeOnly() {
    return $this->pluginDefinition['code_only'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLabel(GroupContentInterface $group_content) {
    return $group_content->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeConfigId() {
    $preferred_id = $this->getGroupTypeId() . '-' . str_replace(':', '-', $this->getPluginId());

    // Return a hashed ID if the readable ID would exceed the maximum length.
    if (strlen($preferred_id) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
      $hashed_id = 'group_content_type_' . md5($preferred_id);
      $preferred_id = substr($hashed_id, 0, EntityTypeInterface::BUNDLE_MAX_LENGTH);
    }

    return $preferred_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeLabel() {
    return $this->getGroupType()->label() . ': ' . $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeDescription() {
    return $this->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperationsCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceLabel() {
    return isset($this->pluginDefinition['reference_label'])
      ? $this->pluginDefinition['reference_label']
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceDescription() {
    return isset($this->pluginDefinition['reference_description'])
      ? $this->pluginDefinition['reference_description']
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceSettings() {
    $settings['target_type'] = $this->getEntityTypeId();
    if ($bundle = $this->getEntityBundle()) {
      $settings['handler_settings']['target_bundles'] = [$bundle];
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Do not allow the changing of the group type ID after construction.
    unset($configuration['group_type_id']);

    // Merge in the default configuration.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Warning: For every key defined here you need to have a matching config
    // schema entry following the pattern group_relation.config.MY_KEY!
    // @see group.schema.yml
    return [
      'group_cardinality' => 0,
      'entity_cardinality' => 0,
      'use_creation_wizard' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $replace = [
      '%entity_type' => $entity_type_manager->getDefinition($this->getEntityTypeId())->getLabel(),
      '%group_type' => $this->getGroupType()->label(),
      '%plugin' => $this->getLabel(),
    ];

    $form['group_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Group cardinality'),
      '#description' => $this->t('The amount of %group_type groups a single %entity_type entity can be added to as a %plugin. Set to 0 for unlimited.', $replace),
      '#default_value' => $this->configuration['group_cardinality'],
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['entity_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Entity cardinality'),
      '#description' => $this->t('The amount of times a single %entity_type entity can be added to the same %group_type group as a %plugin. Set to 0 for unlimited.', $replace),
      '#default_value' => $this->configuration['entity_cardinality'],
      '#min' => 0,
      '#required' => TRUE,
    ];

    if ($this->definesEntityAccess()) {
      $form['use_creation_wizard'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use 2-step wizard when creating a new %entity_type entity within a %group_type group', $replace),
        '#description' => $this->t('This will first show you the form to create the actual entity and then a form to create the relationship between the entity and the group.<br />You can choose to disable this wizard if you did not or will not add any fields to the relationship (i.e. this plugin).<br /><strong>Warning:</strong> If you do have fields on the relationship and do not use the wizard, you may end up with required fields not being filled out.'),
        '#default_value' => $this->configuration['use_creation_wizard'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * Only override this function if you need to do something specific to the
   * submitted data before it is saved as configuration on the plugin. The data
   * gets saved on the plugin in \Drupal\group\Entity\Form\GroupContentTypeForm.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies['module'][] = $this->getProvider();
    $dependencies['module'][] = $this->getEntityType()->getProvider();
    return $dependencies;
  }

}
