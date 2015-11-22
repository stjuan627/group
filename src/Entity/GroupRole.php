<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupRole.
 *
 * @todo Other edit/delete paths, perhaps use a route provider?
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group role configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_role",
 *   label = @Translation("Group role"),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupRoleStorage",
 *     "access" = "Drupal\group\Entity\Access\GroupRoleAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupRoleForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupRoleForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupRoleDeleteForm"
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupRoleListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "role",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/group/roles",
 *     "edit-form" = "/admin/group/roles/manage/{group_role}",
 *     "delete-form" = "/admin/group/roles/manage/{group_role}/delete",
 *     "permissions-form" = "/admin/group/roles/manage/{group_role}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "permissions"
 *   }
 * )
 */
class GroupRole extends ConfigEntityBase implements GroupRoleInterface {

  /**
   * The machine name of this group role.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group role.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of the group role in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * The permissions belonging to the group role.
   *
   * @var array
   */
  protected $permissions = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    return $this->grantPermissions(array($permission));
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermissions($permissions) {
    $this->permissions = array_unique(array_merge($this->permissions, $permissions));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    return $this->revokePermissions(array($permission));
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermissions($permissions) {
    $this->permissions = array_diff($this->permissions, $permissions);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function changePermissions(array $permissions = []) {
    // Grant new permissions to the role.
    $grant = array_filter($permissions);
    if (!empty($grant)) {
      $this->grantPermissions(array_keys($grant));
    }

    // Revoke permissions from the role.
    $revoke = array_diff_assoc($permissions, $grant);
    if (!empty($revoke)) {
      $this->revokePermissions(array_keys($revoke));
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight) && ($group_roles = $storage->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($group_roles, function($max, $group_role) {
        return $max > $group_role->weight ? $max : $group_role->weight;
      });

      $this->weight = $max + 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // @todo
    // Update all references to the group role. Should only be group types and
    // group memberships.

    /*
    if ($update && $this->getOriginalId() != $this->id()) {
      $update_count = node_type_update_nodes($this->getOriginalId(), $this->id());
      if ($update_count) {
        drupal_set_message(\Drupal::translation()->formatPlural($update_count,
          'Changed the content type of 1 post from %old-type to %type.',
          'Changed the content type of @count posts from %old-type to %type.',
          array(
            '%old-type' => $this->getOriginalId(),
            '%type' => $this->id(),
          )));
      }
    }
    */
  }

}
