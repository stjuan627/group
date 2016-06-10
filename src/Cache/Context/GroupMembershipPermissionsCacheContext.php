<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipPermissionsCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Access\GroupPermissionsHashGeneratorInterface;

/**
 * Defines a cache context for "per group membership permissions" caching.
 *
 * Cache context ID: 'group_membership.roles.permissions'.
 */
class GroupMembershipPermissionsCacheContext extends GroupMembershipCacheContextBase implements CacheContextInterface {

  /**
   * The permissions hash generator.
   *
   * @var \Drupal\group\Access\GroupPermissionsHashGeneratorInterface
   */
  protected $permissionsHashGenerator;

  /**
   * Constructs a new GroupMembershipPermissionsCacheContext class.
   *
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\group\Access\GroupPermissionsHashGeneratorInterface $hash_generator
   *   The permissions hash generator.
   */
  public function __construct(ContextProviderInterface $context_provider, AccountInterface $user, GroupPermissionsHashGeneratorInterface $hash_generator) {
    parent::__construct($context_provider, $user);
    $this->permissionsHashGenerator = $hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Group membership permissions");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If there was no existing group on the route, there can be no membership.
    if (!$this->hasExistingGroup()) {
      return 'none';
    }

    return $this->permissionsHashGenerator->generate($this->group, $this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // If any of the membership's roles are updated, it could mean the list of
    // permissions changed as well. We therefore need to set the membership's
    // roles' cacheable metadata.
    //
    // Note that we do not set the membership's cacheable metadata because that
    // one is taken care of in the parent 'group_membership.roles' context.
    if ($this->hasExistingGroup()) {
      // Gather the member's roles if they have a membership.
      if ($group_membership = $this->group->getMember($this->user)) {
        $group_roles = $group_membership->getRoles();
      }
      // Otherwise retrieve the 'anonymous' or 'outsider' role.
      else {
        $role_name = $this->user->id() == 0
          ? $this->group->bundle() . '-anonymous'
          : $this->group->bundle() . '-outsider';
        $group_roles[$role_name] = GroupRole::load($role_name);
      }

      // Merge the cacheable metadata of all the roles.
      foreach ($group_roles as $group_role) {
        $group_role_cacheable_metadata = new CacheableMetadata();
        $group_role_cacheable_metadata->createFromObject($group_role);
        $cacheable_metadata->merge($group_role_cacheable_metadata);
      }
    }

    return $cacheable_metadata;
  }

}
