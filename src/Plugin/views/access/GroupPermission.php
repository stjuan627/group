<?php

namespace Drupal\group\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides group permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "group_permission",
 *   title = @Translation("Group permission"),
 *   help = @Translation("Access will be granted to users with the specified group permission string.")
 * )
 */
class GroupPermission extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Current group context.
   */
  protected ?ContextInterface $context = NULL;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permissionHandler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList|\Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module extension list.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $contextHandler
   *   The context handler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly GroupPermissionHandlerInterface $permissionHandler,
    protected readonly ModuleHandlerInterface|ModuleExtensionList $moduleHandler,
    protected readonly ContextRepositoryInterface $contextRepository,
    protected readonly ContextHandlerInterface $contextHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if ($moduleHandler instanceof ModuleHandlerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with a $moduleHandler argument as \Drupal\Core\Extension\ModuleHandlerInterface instead of \Drupal\Core\Extension\ModuleExtensionList is deprecated in group:3.3.0 and will be required in group:4.0.0. See https://www.drupal.org/node/3431243', E_USER_DEPRECATED);
      $moduleHandler = \Drupal::service('extension.list.module');
    }
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
      $container->get('extension.list.module'),
      $container->get('context.repository'),
      $container->get('context.handler'),
      $container->get('group.group_route_context')
    );
  }

  /**
   * Group context getter.
   */
  protected function getGroupContext(): ?ContextInterface {
    if ($this->context !== NULL) {
      return $this->context;
    }
    $contexts = $this->contextRepository->getRuntimeContexts([$this->options['context_provider']]);
    if (\array_key_exists($this->options['context_provider'], $contexts)) {
      $this->context = $contexts[$this->options['context_provider']];
    }
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    $group = $this->getGroupContext()->getContextValue();
    if ($group === NULL) {
      return FALSE;
    }
    return $group->hasPermission($this->options['group_permission'], $account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_group_permission', $this->options['group_permission']);

    // Upcast any %group path key the user may have configured so the
    // '_group_permission' access check will receive a properly loaded group.
    $route->setOption('parameters', ['group' => ['type' => 'entity:group']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $permissions = $this->permissionHandler->getPermissions(TRUE);
    if (isset($permissions[$this->options['group_permission']])) {
      return $permissions[$this->options['group_permission']]['title'];
    }

    return $this->t($this->options['group_permission']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_permission'] = ['default' => 'view group'];
    $options['context_provider'] = ['default' => '@group.group_route_context:group'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

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
      '#default_value' => $this->options['group_permission'],
      '#description' => $this->t('Only users with the selected group permission will be able to access this display.<br /><strong>Warning:</strong> This will only work if there is a {group} parameter in the route. If not, it will always deny access.'),
    ];

    // Context provider selector.
    $form['context_provider'] = [
      '#type' => 'radios',
      '#title' => $this->t('Context provider'),
      '#description' => $this->t("The context provider will return a group that represents the active site.<br /><em>Warning</em>: Using context providers that don't always return a group context is ill-advised.<br />The \"Group from Views argument\" context that ships with the Group module is the most obvious choice."),
      '#default_value' => $this->options['context_provider'],
      '#required' => TRUE,
      '#options' => [],
    ];

    $definition = EntityContextDefinition::fromEntityTypeId('group');
    $contexts = $this->contextRepository->getAvailableContexts();
    $contexts = $this->contextHandler->getMatchingContexts($contexts, $definition);
    foreach ($contexts as $context_id => $context) {
      $context_definition = $context->getContextDefinition();
      $form['context_provider']['#options'][$context_id] = $context_definition->getLabel();
      $description = $context_definition->getDescription();
      if ($description !== NULL) {
        $form['context_provider'][$context_id]['#description'] = $description;
      }
    }
    if (\count($form['context_provider']['#options']) === 1) {
      $form['context_provider'] = [
        '#type' => 'value',
        '#value' => $context_id,
      ];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::mergeMaxAges(Cache::PERMANENT, $this->getGroupContext()->getCacheMaxAge());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(['user.group_permissions'], $this->getGroupContext()->getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getGroupContext()->getCacheTags();
  }

}
