<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupType.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_type",
 *   label = @Translation("Group type"),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\GroupTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupTypeListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "type",
 *   bundle_of = "group",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/group/types",
 *     "edit-form" = "/admin/group/types/manage/{group_type}",
 *     "delete-form" = "/admin/group/types/manage/{group_type}/delete",
 *     "permissions-form" = "/admin/group/types/manage/{group_type}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "roles"
 *   }
 * )
 */
class GroupType extends ConfigEntityBundleBase implements GroupTypeInterface {

  /**
   * The machine name of the group type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group type.
   *
   * @var string
   */
  protected $description;

  /**
   * A list of group roles the group type uses.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface[]
   */
  protected $roles = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return GroupRole::loadMultiple($this->roles);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleIds() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    foreach ($this->getRoles() as $group_role) {
      $this->addDependency('config', $group_role->getConfigDependencyName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Store the id in a short variable for readability.
    $id = $this->id();

    // Create three internal group roles for the group type.
    if ($this->isNew()) {
      $replace = ['%group_type' => $this->label()];
      GroupRole::create([
        'id' => "a_$id",
        'label' => t('Anonymous (%group_type)', $replace),
        'internal' => TRUE,
        'weight' => -102
      ])->save();
      GroupRole::create([
        'id' => "o_$id",
        'label' => t('Outsider (%group_type)', $replace),
        'internal' => TRUE,
        'weight' => -101,
      ])->save();
      GroupRole::create([
        'id' => "m_$id",
        'label' => t('Member (%group_type)', $replace),
        'internal' => TRUE,
        'weight' => -100,
      ])->save();
    }

    // Assign the three internal roles to the group type.
    array_push($this->roles, "a_$id", "o_$id", "m_$id");
    $this->roles = array_unique($this->roles);

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the internal group roles along with the group type.
    foreach ($entities as $entity) {
      $id = $entity->id();
      entity_delete_multiple('group_role', ["a_$id", "o_$id", "m_$id"]);
    }
  }

}
