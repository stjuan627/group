<?php

namespace Drupal\group;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Wrapper class for a GroupContent entity representing a membership.
 *
 * Should be loaded through the 'group.membership_loader' service.
 */
class GroupMembership implements CacheableDependencyInterface {

  /**
   * The group content entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $groupContent;

  /**
   * Constructs a new GroupMembership.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity representing the membership.
   *
   * @throws \Exception
   *   Exception thrown when trying to instantiate this class with a
   *   GroupContent entity that was not based on the GroupMembership content
   *   enabler plugin.
   */
  public function __construct(GroupContentInterface $group_content) {
    if ($group_content->getGroupContentType()->getContentPluginId() == 'group_membership') {
      $this->groupContent = $group_content;
    }
    else {
      throw new \Exception('Trying to create a GroupMembership from an incompatible GroupContent entity.');
    }
  }

  /**
   * Returns the fieldable GroupContent entity for the membership.
   *
   * @return \Drupal\group\Entity\GroupContentInterface
   *   The GroupContent entity associated with membership.
   *   This entity represents the content within a group and
   *   provides access to the fields and data related
   *   to that content.
   */
  public function getGroupContent() {
    return $this->groupContent;
  }

  /**
   * Returns the group for the membership.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The Group entity related to membership. This entity represents group
   *   that the membership belongs to and
   *   provides access to the group's data and fields.
   */
  public function getGroup() {
    return $this->groupContent->getGroup();
  }

  /**
   * Returns the user for the membership.
   *
   * @return \Drupal\user\UserInterface
   *   The User entity related to membership. This entity represents user
   *   who is part of the membership and
   *   provides access to the user's data and fields.
   */
  public function getUser() {
    return $this->groupContent->getEntity();
  }

  /**
   * Returns the group roles for the membership.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group roles, keyed by their ID.
   */
  public function getRoles() {
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
    return $group_role_storage->loadByUserAndGroup($this->getUser(), $this->getGroup());
  }

  /**
   * Checks whether the member has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   Whether the member has the requested permission.
   */
  public function hasPermission($permission) {
    return $this->groupPermissionChecker()->hasPermissionInGroup($permission, $this->getUser(), $this->getGroup());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getGroupContent()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getGroupContent()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getGroupContent()->getCacheMaxAge();
  }

  /**
   * Gets the group permission checker.
   *
   * @return \Drupal\group\Access\GroupPermissionCheckerInterface
   *   The group permission checker service,
   *   which is used to evaluate permissions
   *   for group content and determine access based on defined rules.
   */
  protected function groupPermissionChecker() {
    return \Drupal::service('group_permission.checker');
  }

}
