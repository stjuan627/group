<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\GroupContentEnabler\GroupMembership.
 */

namespace Drupal\group\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Symfony\Component\Routing\Route;

/**
 * Provides a content enabler for users.
 *
 * @GroupContentEnabler(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 *   description = @Translation("Adds users to groups as members."),
 *   entity_type_id = "user",
 *   cardinality = 1,
 *   paths = {
 *     "collection" = "/group/{group}/members",
 *     "add-form" = "/group/{group}/members/add",
 *     "canonical" = "/group/{group}/members/{group_content}",
 *     "edit-form" = "/group/{group}/members/{group_content}/edit",
 *     "delete-form" = "/group/{group}/members/{group_content}/delete",
 *     "join-form" = "/group/{group}/join",
 *     "leave-form" = "/group/{group}/leave"
 *   },
 *   enforced = TRUE
 * )
 */
class GroupMembership extends GroupContentEnablerBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityForms() {
    return [
      'group-join' => 'Drupal\group\Form\GroupJoinForm',
      'group-leave' => 'Drupal\group\Form\GroupLeaveForm',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Implement these on the corresponding routes.
   */
  public function getPermissions() {
    $permissions['administer members'] = [
      'title' => 'Administer group members',
      'description' => 'Administer the group members',
      'restrict access' => TRUE,
    ];

    $permissions['access member overview'] = [
      'title' => 'Access the member overview page',
    ];

    $permissions['view members'] = [
      'title' => 'View group members',
    ];

    $permissions['join group'] = [
      'title' => 'Join group',
      'description' => 'Join a group by filling out the configured fields',
      'allowed for' => ['outsider'],
    ];

    $permissions['edit own membership'] = [
      'title' => 'Edit own membership',
      'description' => 'Edit own membership information',
      'allowed for' => ['member'],
    ];

    $permissions['leave group'] = [
      'title' => 'Leave group',
      'allowed for' => ['member'],
    ];

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute() {
    $route = parent::getCollectionRoute();

    // Reset the default requirements and add our own group permissions. The '+'
    // signifies that only one permission needs to be set for the user. We also
    // don't set the _group_enabled_content requirement again because we know
    // this plugin will always be installed.
    $route->setRequirements([])->setRequirement('_group_permission', 'administer members+access member overview');

    // Swap out the GroupContent list controller for our own.
    // @todo Implement this after we've completed the above list controller.

    return $route;
  }

  /**
   * Gets the join form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getJoinFormRoute() {
    if ($path = $this->getPath('join-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Controller\GroupMembershipController::join',
          '_title_callback' => '\Drupal\group\Controller\GroupMembershipController::joinTitle',
          'plugin_id' => $this->getPluginId(),
        ])
        ->setRequirement('_group_permission', 'join group')
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * Gets the leave form route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getLeaveFormRoute() {
    if ($path = $this->getPath('leave-form')) {
      $route = new Route($path);

      $route
        ->setDefaults([
          '_controller' => '\Drupal\group\Controller\GroupMembershipController::leave',
          'plugin_id' => $this->getPluginId(),
        ])
        ->setRequirement('_group_permission', 'leave group')
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    $routes = parent::getRoutes();
    $route_prefix = 'entity.group_content.group_membership';

    if ($join_route = $this->getJoinFormRoute()) {
      $routes["$route_prefix.join_form"] = $join_route;
    }

    if ($leave_route = $this->getLeaveFormRoute()) {
      $routes["$route_prefix.leave_form"] = $leave_route;
    }

    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceSettings() {
    $settings = parent::getEntityReferenceSettings();
    $settings['handler_settings'] = ['include_anonymous' => FALSE];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall() {
    $group_content_type_id = $this->getContentTypeConfigId();

    // Add the group_roles field to the newly added group content type. The
    // field storage for this is defined in the config/install folder. The
    // default handler for 'group_role' target entities in the 'group_type'
    // handler group is GroupTypeRoleSelection.
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('group_content', 'group_roles'),
      'bundle' => $group_content_type_id,
      'label' => $this->t('Roles'),
      'settings' => [
        'handler' => 'group_type:group_role',
        'handler_settings' => [
          'group_type_id' => $this->getGroupTypeId(),
        ],
      ],
    ])->save();

    // Build the 'default' display ID for both the entity form and view mode.
    $default_display_id = "group_content.$group_content_type_id.default";

    // Build or retrieve the 'default' form mode.
    if (!$form_display = EntityFormDisplay::load($default_display_id)) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'group_content',
        'bundle' => $group_content_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Build or retrieve the 'default' view mode.
    if (!$view_display = EntityViewDisplay::load($default_display_id)) {
      $view_display = EntityViewDisplay::create([
        'targetEntityType' => 'group_content',
        'bundle' => $group_content_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Assign widget settings for the 'default' form mode.
    $form_display->setComponent('group_roles', [
      'type' => 'options_buttons',
    ])->save();

    // Assign display settings for the 'default' view mode.
    $view_display->setComponent('group_roles', [
      'label' => 'above',
      'type' => 'entity_reference_label',
      'settings' => [
        'link' => 0,
      ],
    ])->save();
  }

}
