<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Block\GroupOperationsBlock.
 */

namespace Drupal\group\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
    // Regardless of whether there is a group on the route, we still list the
    // group and group type as cache contexts because both cache contexts treat
    // every route that does not have a group as one and the same.
    $build['#cache']['contexts'] = ['group', 'group.type'];

    // @todo Cache context for member roles or permissions.

    /** @var \Drupal\group\Entity\GroupInterface $group */
    if ($group = $this->getContextValue('group')) {
      $build['#type'] = 'operations';
      $build['#cache']['tags'] = $group->getCacheTags();

      $links = [];
      foreach ($group->getGroupType()->enabledContent() as $plugin) {
        $links += $plugin->getGroupOperations($group);
      }
      uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      $build['#links'] = $links;
    }

    // If no group was found, cache the empty result on the route.
    return $build;
  }

}
