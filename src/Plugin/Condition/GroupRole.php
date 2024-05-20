<?php

namespace Drupal\group\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Group Role' condition.
 *
 * @Condition(
 *   id = "group_role",
 *   label = @Translation("Group Role"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", label = @Translation("Group"))
 *   }
 * )
 *
 */
class GroupRole extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\group\Entity\Storage\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Creates a new GroupType instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param array                                      $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string                                     $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed                                      $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, AccountProxyInterface $current_user, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_storage;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('group_role'),
      $container->get('current_user'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Show a series of checkboxes for group role selection.
    $form['group_roles'] = [
      '#title' => $this->t('Group roles'),
      '#type' => 'checkboxes',
      '#options' => $this->getGroupRolesArray(),
      '#default_value' => $this->configuration['group_roles'],
    ];

    $form['bypass_group_access'] = [
      '#title' => $this->t('Include users with "bypass group access" permission'),
      '#type' => 'checkbox',
      '#description' => $this->t('If checked, users with the global "bypass group access permission" will also match this condition regardless of group role.'),
      '#default_value' => $this->configuration['bypass_group_access'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['group_roles'] = array_filter($form_state->getValue('group_roles'));
    if (TRUE == $form_state->getValue('bypass_group_access')) {
      $this->configuration['bypass_group_access'] = $form_state->getValue('bypass_group_access');
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $group_roles = $this->configuration['group_roles'];
    $bypass_group_access = $this->configuration['bypass_group_access'];

    $keyed_group_roles = $this->getGroupRolesArray();
    $labeled_group_roles = [];

    // Build labels for the keys.
    foreach ($group_roles as $group_role) {
      $labeled_group_roles[] = $keyed_group_roles[$group_role];
    }

    // Format a pretty string if multiple group roles were selected.
    if (count($group_roles) > 1) {
      $last = array_pop($labeled_group_roles);
      $group_roles = implode(', ', $labeled_group_roles);
      $return_string = $this->t('The group role is %group_roles or %last', [
        '%group_roles' => $group_roles,
        '%last' => $last
      ]);
    }
    else {
      // If just one was selected, return a simple string.
      $return_string = $this->t('The group role is %group_role', ['%group_role' => reset($labeled_group_roles)]);
    }

    if ($bypass_group_access) {
      $return_string .= ' ' . $this->t('or the user has "bypass group access" permission');
    }

    return $return_string;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {

    // If there are no group roles selected and the condition is not negated, we
    // return TRUE because it means all group types are valid.
    if (empty($this->configuration['group_roles']) && !$this->isNegated()) {
      return TRUE;
    }

    if ($this->configuration['bypass_group_access'] && !$this->isNegated()) {
      if ($this->currentUser->hasPermission('bypass group access')) {
        return TRUE;
      }
    }

    $user_roles = $this->entityStorage->loadByUserAndGroup($this->currentUser, $this->getContextValue('group'));
    foreach ($user_roles as $role) {
      if (in_array($role->id(), $this->configuration['group_roles'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['group_roles' => [], 'bypass_group_access' => FALSE] + parent::defaultConfiguration();
  }

  /**
   * Builds a list of group role labels.
   */
  private function getGroupRolesArray() {
    $options = [];
    $group_roles = $this->entityStorage->loadMultiple();
    foreach ($group_roles as $role) {
      $options[$role->id()] = $role->label() . ' (' . ($role->id()) . ')';
    }

    return $options;
  }

}