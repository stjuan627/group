<?php

namespace Drupal\group\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler base for group content entity references.
 */
abstract class GroupContentToEntityBase extends RelationshipPluginBase {

  /**
   * The Views join plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs an GroupContentToEntityBase object.
   *
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager, GroupRelationTypeManagerInterface $plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('group_relation_type.manager')
    );
  }

  /**
   * Retrieves the entity type ID this plugin targets.
   *
   * Do not return 'group_content', but the actual entity type ID you're trying
   * to link up to the group_content entity type.
   *
   * @return string
   *   The target entity type ID.
   */
  protected abstract function getTargetEntityType();

  /**
   * Retrieves type of join field to use.
   *
   * Can be either 'field' or 'left_field'.
   *
   * @return string
   *   The type of join field to use.
   */
  protected abstract function getJoinFieldType();

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_content_plugins']['default'] = [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Retrieve all of the plugins that can serve this entity type.
    $options = [];
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $group_relation_type) {
      /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type */
      if ($group_relation_type->getEntityTypeId() === $this->getTargetEntityType()) {
        $options[$plugin_id] = $group_relation_type->getLabel();
      }
    }

    $form['group_content_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Filter by plugin'),
      '#description' => $this->t('Refine the result by plugin. Leave empty to select all plugins, including those that could be added after this relationship was configured.'),
      '#options' => $options,
      '#weight' => -2,
      '#default_value' => $this->options['group_content_plugins'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Build the join definition.
    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $this->definition['base field'];
    $def['left_table'] = $this->tableAlias;
    $def['left_field'] = $this->realField;
    $def['adjusted'] = TRUE;

    // Change the join to INNER if the relationship is required.
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    // If there were extra join conditions added in the definition, use them.
    if (!empty($this->definition['extra'])) {
      $def['extra'] = $this->definition['extra'];
    }

    // Add the plugin IDs to the query if any were selected.
    $plugin_ids = array_filter($this->options['group_content_plugins']);
    if (!empty($plugin_ids)) {
      $def['extra'][] = [
        $this->getJoinFieldType() => 'plugin_id',
        'value' => $plugin_ids,
      ];
    }

    // Use the standard join plugin unless instructed otherwise.
    $join_id = !empty($def['join_id']) ? $def['join_id'] : 'standard';
    $join = $this->joinManager->createInstance($join_id, $def);

    // Add the join using a more verbose alias.
    $alias = $def['table'] . '_' . $this->table;
    $this->alias = $this->query->addRelationship($alias, $join, $this->definition['base'], $this->relationship);

    // Add access tags if the base table provides it.
    $table_data = $this->viewsData->get($def['table']);
    if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
      $access_tag = $table_data['table']['base']['access query tag'];
      $this->query->addTag($access_tag);
    }
  }

}
