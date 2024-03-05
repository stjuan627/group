<?php

namespace Drupal\group\Entity;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;

/**
 * Functionality trait for a group_membership bundle class.
 */
trait GroupMembershipTrait {

  /**
   * {@inheritdoc}
   */
  public function getRoles($include_synchronized = TRUE) {
    $group_role_storage = $this->entityTypeManager()->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);
    return $group_role_storage->loadByUserAndGroup($this->getEntity(), $this->getGroup(), $include_synchronized);
  }

  /**
   * {@inheritdoc}
   */
  public function addRole(string $role_id): void {
    // Do nothing if the role is already present.
    foreach ($this->group_roles as $group_role_ref) {
      if ($group_role_ref->target_id === $role_id) {
        return;
      }
    }

    // @todo Add the below two checks to a preSave() hook.
    $storage = \Drupal::entityTypeManager()->getStorage('group_role');
    if (!$group_role = $storage->load($role_id)) {
      throw new \InvalidArgumentException(sprintf('Could not add role with ID %s, role does not exist.', $role_id));
    }
    assert($group_role instanceof GroupRoleInterface);
    if ($group_role->getGroupTypeId() !== $this->getGroupTypeId()) {
      throw new \InvalidArgumentException(sprintf('Could not add role with ID %s, role belongs to a different group type.', $role_id));
    }

    $this->group_roles[] = $role_id;
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole(string $role_id): void {
    foreach ($this->group_roles as $key => $group_role_ref) {
      if ($group_role_ref->target_id === $role_id) {
        $this->group_roles->removeItem($key);
      }
    }
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->getGroup()->hasPermission($permission, $this->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadSingle(GroupInterface $group, AccountInterface $account) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership')
      ->execute();

    return $ids ? $storage->load(reset($ids)) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByGroup(GroupInterface $group, $roles = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');

    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

}
