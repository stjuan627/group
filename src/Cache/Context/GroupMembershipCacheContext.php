<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group membership" caching.
 *
 * Cache context ID: 'group_membership'.
 */
class GroupMembershipCacheContext extends GroupMembershipCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group membership');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If there was no existing group on the route, there can be no membership.
    if (!$this->hasExistingGroup()) {
      return 'none';
    }

    // If there is a membership, we return the membership ID.
    if ($group_membership = $this->group->getMember($this->user)) {
      return $group_membership->getGroupContent()->id();
    }

    // Otherwise, return the name of the 'outsider' or 'anonymous' group role,
    // depending on the user. This is necessary to have a unique identifier to
    // distinguish between 'outsider' or 'anonymous' users for different group
    // types.
    return $this->user->id() == 0
      ? $this->group->bundle() . '-anonymous'
      : $this->group->bundle() . '-outsider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // You can't update a group content's ID. So even if somehow this top-level
    // cache context got optimized away, it does not need to set a cache tag for
    // a group content entity as the ID is not invalidated by a save.
    return new CacheableMetadata();
  }

}
