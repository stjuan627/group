<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupTypeCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group type" caching.
 *
 * Cache context ID: 'group.type'.
 */
class GroupTypeCacheContext extends GroupCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group type');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return !empty($this->group) ? $this->group->bundle() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // You can't update a group type's ID and neither can you change a group's
    // type. So if this cache context gets optimized away, we don't need to set
    // any cache tags.
    return new CacheableMetadata();
  }

}
