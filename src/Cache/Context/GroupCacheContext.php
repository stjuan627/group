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
    return new CacheableMetadata();
  }

}
