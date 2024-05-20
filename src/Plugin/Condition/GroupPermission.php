<?php

namespace Drupal\group\Plugin\Condition;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a condition for members of the current group.
 *
 * This condition evaluates to TRUE when in a group context, and the current
 * user is a member of the group. When the condition is negated, the condition
 * is shown when either not in group context, or in group context but the
 * current user is not a member of the group.
 *
 * @Condition(
 *   id = "group_permission",
 *   label = @Translation("Group permission"),
 * )
 */
class GroupPermission extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The group permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The group entity from the route.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The group context from the route.
   *
   * @var \Drupal\Core\Plugin\Context\ContextInterface
   */
  protected $context;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, ContextProviderInterface $context_provider, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;

    $contexts = $context_provider->getRuntimeContexts(['group']);
    $this->context = $contexts['group'];
    $this->group = $this->context->getContextValue();
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('group.group_route_context'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['group_permission' => null] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get list of permissions.
    $permissions = [];
    foreach ($this->permissionHandler->getPermissions(TRUE) as $permission_name => $permission) {
      $display_name = $this->moduleHandler->getName($permission['provider']);
      $permissions[$display_name . ' : ' . $permission['section']][$permission_name] = strip_tags($permission['title']);
    }

    $form['group_permission'] = [
      '#type' => 'select',
      '#options' => $permissions,
      '#title' => $this->t('Group permission'),
      '#default_value' => $this->configuration['group_permission'],
      '#description' => $this->t('Only users with the selected group permission will be able to access this display.<br /><strong>Warning:</strong> This will only work if there is a {group} parameter in the route. If not, it will always deny access.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Save the submitted value to configuration.
    $this->configuration['group_permission'] = $form_state->getValue('group_permission');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->configuration['group_permission']) {
      // Check if the 'negate condition' checkbox was checked.
      if ($this->isNegated()) {
        // The condition is enabled and negated.
        return $this->t('Shown on not has group permission @group_permission', ["@group_permission" => $this->configuration['group_permission']]);
      }
      else {
        // The condition is enabled.
        return $this->t('Shown on has group permission @group_permission', ["@group_permission" => $this->configuration['group_permission']]);
      }
    }

    // The condition is not enabled.
    return $this->t('Not Restricted');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (!empty($this->group)) {
      return $this->group->hasPermission($this->configuration['group_permission'], $this->currentUser);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    return Cache::mergeContexts(['user.group_permissions'], $this->context->getCacheContexts(), $contexts);
  }

}
