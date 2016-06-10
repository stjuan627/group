<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipRolesCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;

/**
 * Defines a cache context for "per group membership roles" caching.
 *
 * Only use this cache context when checking explicitly for certain roles. For
 * instance when you want to show a block listing all of the member's roles. Use
 * group_membership.roles.permissions for anything that checks permissions.
 *
 * Cache context ID: 'group_membership.roles' (to vary by all roles).
 * Calculated cache context ID: 'group_membership.roles:%group_role', e.g.
 * 'group_membership.roles:%administrator' (to vary by the presence or absence
 * of the 'administrator' group role).
 */
class GroupMembershipRolesCacheContext extends GroupMembershipCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group membership roles');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($group_role = NULL) {
    // If there was no existing group on the route, there can be no membership.
    // As a consequence, there can be no group role. We need to make sure we
    // return an invalid group role ID to avoid collisions.
    if (!$this->hasExistingGroup()) {
      return '...none...';
    }

    // Gather all of the user's group roles for the current group.
    if ($group_membership = $this->group->getMember($this->user)) {
      $group_roles = array_keys($group_membership->getRoles());
    }
    else {
      $group_roles = $this->user->id() == 0
        ? [$this->group->bundle() . '-anonymous']
        : [$this->group->bundle() . '-outsider'];
    }

    if ($group_role === NULL) {
      return implode(',', $group_roles);
    }
    else {
      return in_array($group_role, $group_roles) ? '0' : '1';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($group_role = NULL) {
    $cacheable_metadata =  new CacheableMetadata();

    // If the membership is updated, it could mean the list of roles changed as
    // well. We therefore need to set the membership's cacheable metadata.
    if ($this->hasExistingGroup()) {
      if ($group_membership = $this->group->getMember($this->user)) {
        $cacheable_metadata->createFromObject($group_membership);
      }
    }

    return $cacheable_metadata;
  }

}
