<?php

/**
 * @file
 * Post update functions for Group.
 */

/**
 * Update view displays to use the new group_permission access plugin.
 */
function group_post_update_view_group_permission() {
  /** @var \Drupal\views\ViewEntityInterface[] $views */
  $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();
  foreach ($views as $view) {
    $displays = $view->get('display');
    $changed = FALSE;
    foreach ($displays as &$display) {
      if (empty($display['display_options']['access'])) {
        continue;
      }
      $access_option = &$display['display_options']['access'];
      $access_type = $access_option['type'] ?? NULL;
      if ($access_type == 'group_permission') {
        $access_option['options']['group_permission'] = [$access_option['options']['group_permission'] => $access_option['options']['group_permission']];
        $access_option['options']['operator'] = 'AND';
        $changed = TRUE;
      }
    }
    if ($changed) {
      $view->set('display', $displays);
      $view->save();
    }
  }
}
