<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupPermissionsForm.
 */

namespace Drupal\group\Form;

use Drupal\Component\Render\FormattableMarkup;
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
   * Gets a few basic instructions to show the user.
   *
   * @return string
   *   A translated string to display atop the form.
   */
  protected function getInfo() {
    $red_x = new FormattableMarkup('<span style="color: #ff0000;">x</span>', []);
    return '<p>' . $this->t('Cells with an @red_x indicate that the permission is not available for that role.', ['@red_x' => $red_x]) . '</p>';
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
    $role_info = [];

    // Sort the group roles using the static sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    $group_roles = $this->getRoles();
    uasort($group_roles, '\Drupal\group\Entity\GroupRole::sort');

    // Retrieve information for every role to user further down. We do this to
    // prevent the same methods from being fired (rows * permissions) times.
    foreach ($group_roles as $role_name => $group_role) {
      $role_info[$role_name] = [
        'label' => $group_role->prettyLabel(),
        'permissions' => $group_role->getPermissions(),
        'is_anonymous' => $group_role->isAnonymous(),
        'is_outsider' => $group_role->isOutsider(),
        'is_member' => $group_role->isMember(),
      ];
    }

    // Render the general information.
    if ($info = $this->getInfo()) {
      $form['info'] = [
        '#markup' => new FormattableMarkup($info, []),
      ];
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
    foreach ($role_info as $info) {
      $form['permissions']['#header'][] = [
        'data' => $info['label'],
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
          'colspan' => count($group_roles) + 1,
          'class' => ['module'],
          'id' => 'module-' . $provider,
        ],
        '#markup' => $this->moduleHandler->getName($provider),
      ]];

      // Then list all of the permissions for that provider.
      foreach ($permissions as $perm => $perm_item) {
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

        // Finally build a checkbox cell for every group role.
        foreach ($role_info as $role_name => $info) {
          // Determine whether the permission is available for this role.
          $na = $info['is_anonymous'] && !in_array('anonymous', $perm_item['allowed for']);
          $na = $na || ($info['is_outsider'] && !in_array('outsider', $perm_item['allowed for']));
          $na = $na || ($info['is_member'] && !in_array('member', $perm_item['allowed for']));

          // Show a red 'x' if the permission is unavailable.
          if ($na) {
            $form['permissions'][$perm][$role_name] = array(
              '#title' => $info['label'] . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => array(
                'class' => array('checkbox'),
                'style' => 'color: #ff0000;',
              ),
              '#markup' => 'x',
            );
          }
          // Show a checkbox if the permissions is available.
          else {
            $form['permissions'][$perm][$role_name] = array(
              '#title' => $info['label'] . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => array(
                'class' => array('checkbox'),
              ),
              '#type' => 'checkbox',
              '#default_value' => in_array($perm, $info['permissions']) ? 1 : 0,
              '#attributes' => array('class' => array('rid-' . $role_name, 'js-rid-' . $role_name)),
              '#parents' => array($role_name, $perm),
            );
          }
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
