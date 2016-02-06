<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the GroupCacheContext service, for "per group" caching.
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
    return !empty($this->group) ? $this->group->id() : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // This needs to be invalidated when either the group or the group type is
    // updated. We can't set cache tags for a non-existent group, however.
    if (!empty($this->group)) {
      $tags = ['group:' . $this->group->id(), 'group_type:' . $this->group->bundle()];
      return $cacheable_metadata->setCacheTags($tags);
    }

    return $cacheable_metadata;
  }

}
