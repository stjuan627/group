<?php

namespace Drupal\group\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\group\Plugin\views\argument\GroupId;

/**
 * Filter class which allows filtering by entity bundles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("group_role")
 */
class GroupRoleFilter extends InOperator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   *   The group route context.

   */
  protected $contextProvider;

  /**
   * Constructs a new GroupRoleFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ContextProviderInterface $context_provider
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->contextProvider = $context_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('group.group_route_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueOptions = [];

      $group = NULL;
      // Try to get group ID from view arguments.
      if (is_array($this->view->argument)) {
        foreach ($this->view->argument as $argument) {
          if ($argument instanceof GroupId && $group_id = $argument->getValue()) {
            $group = $this->entityTypeManager->getStorage('group')->load($group_id);
            break;
          }
        }
      }

      // Try to get group ID from context otherwise.
      if (is_null($group)) {
        $contexts = $this->contextProvider->getRuntimeContexts(['group']);
        if (array_key_exists('group', $contexts)) {
          $group = $contexts['group']->getContextValue();
        }
      }

      $property_filters = [
        'internal' => FALSE,
      ];
      if (!is_null($group)) {
        $property_filters['group_type'] = $group->bundle();
      }

      $roles = $this->entityTypeManager->getStorage('group_role')->loadByProperties($property_filters);

      foreach ($roles as $role_id => $role) {
        $this->valueOptions[$role_id] = $role->label();
      }
    }
    return $this->valueOptions;
  }

}
