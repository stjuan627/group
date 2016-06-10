<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group" caching.
 *
 * Cache context ID: 'group'.
 */
class GroupCacheContext extends GroupCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If we have an existing group, we can simply return its ID because that is
    // a unique identifier. However, when dealing with unsaved groups, they all
    // share the same ID 0. In order to avoid collisions when the 'group.type'
    // context gets optimized away, we need to make the unsaved groups unique
    // per type as well.
    return $this->hasExistingGroup()
      ? $this->group->id()
      : $this->group->bundle() . '-0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // You can't update a group's ID. So even if somehow this top-level cache
    // context got optimized away, it does not need to set a cache tag for a
    // group entity as the ID is not invalidated by a save.
    return new CacheableMetadata();
  }

}
