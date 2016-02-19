<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Group routes.
 */
class GroupController extends ControllerBase {

  /**
   * Displays add content links for available group types.
   *
   * Redirects to group/add/[type] if only one group type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the group types that can be added; however,
   *   if there is only one group type defined for the site, the function
   *   will return a RedirectResponse to the group add page for that one group
   *   type.
   */
  public function addPage() {
    $group_types = [];

    // Only use group types the user has access to.
    foreach (GroupType::loadMultiple() as $group_type) {
      if ($this->entityTypeManager()->getAccessControlHandler('group')->createAccess($group_type->id())) {
        $group_types[$group_type->id()] = $group_type;
      }
    }

    // Bypass the group/add listing if only one content type is available.
    if (count($group_types) == 1) {
      $group_type = array_shift($group_types);
      return $this->redirect('entity.group.add_form', ['group_type' => $group_type->id()]);
    }

    return [
      '#theme' => 'group_add_list',
      '#group_types' => $group_types,
    ];
  }

  /**
   * Provides the group submission form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type of the group to add.
   *
   * @return array
   *   A group submission form.
   */
  public function add(GroupTypeInterface $group_type) {
    $group = Group::create(['type' => $group_type->id()]);
    $form = $this->entityFormBuilder()->getForm($group, 'add');
    return $form;
  }

  /**
   * The _title_callback for the entity.group.add_form route.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to base the title on.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(GroupTypeInterface $group_type) {
    return $this->t('Create @name', ['@name' => $group_type->label()]);
  }

}
