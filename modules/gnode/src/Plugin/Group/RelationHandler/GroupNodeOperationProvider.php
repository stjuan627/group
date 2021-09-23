<?php

namespace Drupal\gnode\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for the group_node relation plugin.
 */
class GroupNodeOperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupNodeOperationProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface $parent
   *   The default operation provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(OperationProviderInterface $parent, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, AccountProxyInterface $current_user) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = $this->parent->getGroupOperations($group);

    $node_type_id = $this->groupRelationType->getEntityBundle();
    $node_type = $this->entityTypeManager()->getStorage('node_type')->load($node_type_id);

    if ($group->hasPermission("create $this->pluginId entity", $this->currentUser)) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $this->pluginId];
      $operations["gnode-create-$node_type_id"] = [
        'title' => $this->t('Add @type', ['@type' => $node_type->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

}
