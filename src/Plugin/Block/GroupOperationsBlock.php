<?php

namespace Drupal\group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides a block with operations the user can perform on a group.
 *
 * @Block(
 *   id = "group_operations",
 *   admin_label = @Translation("Group operations"),
 *   context = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class GroupOperationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();

    // Create an operations element that will hold all of the links.
    $build['#type'] = 'operations';

    // This block varies per group type and per current user's group membership
    // permissions. Different group types could have different content plugins
    // enabled, influencing which group operations are available to them. The
    // active user's group permissions define which actions are accessible.
    //
    // We do not need to specify the current user or group as cache contexts
    // because, in essence, a group membership is a union of both.
    $cache->addCacheContexts(['group.type', 'group_membership.roles.permissions']);

    // Of special note is the cache context 'group_membership.audience'. Where
    // the above cache contexts should suffice if everything is ran through the
    // permission system, group operations are an exception. Some operations
    // such as 'join' and 'leave' not only check for a permission, but also the
    // audience the user belongs to. I.e.: whether they're a 'member', an
    // 'outsider' or 'anonymous'.
    $cache->addCacheContexts(['group_membership.audience']);

    /** @var \Drupal\group\Entity\GroupInterface $group */
    if (($group = $this->getContextValue('group')) && $group->id()) {
      $links = [];

      // Retrieve the operations from the installed content plugins.
      foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        $links += $plugin->getGroupOperations($group);
      }

      if ($links) {
        // Allow modules to alter the collection of gathered links.
        \Drupal::moduleHandler()->alter('group_operations', $links, $group);

        // Sort the operations by weight.
        uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

        // @todo We should have operation links provide cacheable metadata that
        // we could then merge in here.
        $build['#links'] = $links;
      }
    }

    // Apply the cacheable metadata to the block.
    $cache->applyTo($build);

    // Return the operations, even if there were none, so the result is cached.
    return $build;
  }

}
