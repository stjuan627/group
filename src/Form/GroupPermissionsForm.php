<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupPermissionsForm.
 */

namespace Drupal\group\Form;

use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the group permissions administration form.
 */
abstract class GroupPermissionsForm extends FormBase {

  /**
   * The permission handler.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $groupPermissionHandler;

  /**
   * The group role storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected $groupRoleStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function __construct(GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler) {
    $this->groupPermissionHandler = $permission_handler;
    $this->groupRoleStorage = $group_role_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group.permissions'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_admin_permissions';
  }

  /**
   * Gets the group roles to display in this form.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group role objects.
   */
  protected function getRoles() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $role_names = $role_permissions = [];

    foreach ($this->getRoles() as $role_name => $group_role) {
      // Retrieve group role names for columns.
      $role_names[$role_name] = $group_role->label();

      // Fetch permissions for the group roles.
      $role_permissions[$role_name] = $group_role->getPermissions();
    }

    // Render the link for hiding descriptions.
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    // Render the role/permissions table.
    $form['permissions'] = [
      '#type' => 'table',
      '#header' => array($this->t('Permission')),
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];

    // Create a column with header for every group role.
    foreach ($role_names as $name) {
      $form['permissions']['#header'][] = [
        'data' => $name,
        'class' => array('checkbox'),
      ];
    }

    // Create a list of group permissions ordered by their provider.
    $permissions_by_provider = [];
    foreach ($this->groupPermissionHandler->getPermissions() as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    // Render the permission as sections of rows.
    $hide_descriptions = system_admin_compact_mode();
    foreach ($permissions_by_provider as $provider => $permissions) {
      // Start each section with a full width row containing the provider name.
      $form['permissions'][$provider] = [[
        '#wrapper_attributes' => [
          'colspan' => count($role_names) + 1,
          'class' => ['module'],
          'id' => 'module-' . $provider,
        ],
        '#markup' => $this->moduleHandler->getName($provider),
      ]];

      // Then list all of the permissions for that provider.
      foreach ($permissions as $perm => $perm_item) {
        // Fill in default values for the permission.
        $perm_item += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
          'allowed for' => ['anonymous', 'outsider', 'member'],
        ];

        // Create a row for the permission, starting with the description cell.
        $form['permissions'][$perm]['description'] = array(
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em><br />{% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => array(
            'title' => $perm_item['title'],
          ),
        );

        // Show the permission description and warning if toggled on.
        if (!$hide_descriptions) {
          $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        }

        // Finally build a checkbox cells for every group role.
        foreach ($role_names as $rid => $name) {
          $form['permissions'][$perm][$rid] = array(
            '#title' => $name . ': ' . $perm_item['title'],
            '#title_display' => 'invisible',
            '#wrapper_attributes' => array(
              'class' => array('checkbox'),
            ),
            '#type' => 'checkbox',
            '#default_value' => in_array($perm, $role_permissions[$rid]) ? 1 : 0,
            '#attributes' => array('class' => array('rid-' . $rid, 'js-rid-' . $rid)),
            '#parents' => array($rid, $perm),
          );
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    // @todo.
    $form['#attached']['library'][] = 'user/drupal.user.permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getRoles() as $role_name => $group_role) {
      /* @var $group_role \Drupal\group\Entity\GroupRoleInterface */
      $permissions = $form_state->getValue($role_name);
      $group_role->changePermissions($permissions)->trustData()->save();
    }

    drupal_set_message($this->t('The changes have been saved.'));
  }

}
