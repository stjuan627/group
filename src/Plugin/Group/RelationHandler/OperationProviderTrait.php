<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;

/**
 * Trait for group relation operation providers.
 *
 * This trait takes care of common logic for operation providers. Please make
 * sure your handler service asks for the entity_type.manager service and sets
 * to the $this->entityTypeManager property in its constructor.
 */
trait OperationProviderTrait {

  use RelationHandlerTrait {
    init as traitInit;
  }
  use StringTranslationTrait;

  /**
   * The entity type the plugin handler is for.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The plugin's permission provider.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface
   */
  protected $permissionProvider;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type) {
    $this->traitInit($plugin_id, $group_relation_type);
    $this->entityType = $this->entityTypeManager()->getDefinition($group_relation_type->getEntityTypeId());
    $this->permissionProvider = $this->groupRelationTypeManager()->getPermissionProvider($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(GroupTypeInterface $group_type) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using OperationProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getOperations($group_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using OperationProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getGroupOperations($group);
  }

  /**
   * Gets the group content type ID for the plugin on the group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to get the group content type ID from.
   *
   * @return string|false
   *   Either the group content type ID if the plugin was installed on the group
   *   type or FALSE otherwise.
   */
  protected function getGroupContentTypeId(GroupTypeInterface $group_type) {
    if ($group_type->hasPlugin($this->pluginId)) {
      $storage = $this->entityTypeManager()->getStorage('group_content_type');
      assert($storage instanceof GroupContentTypeStorageInterface);
      return $storage->getGroupContentTypeId($group_type->id(), $this->pluginId);
    }
    return FALSE;
  }

}
