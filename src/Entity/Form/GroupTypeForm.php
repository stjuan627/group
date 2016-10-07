<?php

namespace Drupal\group\Entity\Form;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for group type forms.
 */
class GroupTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add group type');
    }
    else {
      $form['#title'] = $this->t('Edit %label group type', ['%label' => $type->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this group type. This text will be displayed as part of the list on the %group-add page. This name must be unique.', [
        '%group-add' => $this->t('Add group'),
      ]),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => GroupTypeInterface::ID_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\group\Entity\GroupType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this group type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %group-add page, in which underscores will be converted into hyphens.', [
        '%group-add' => $this->t('Add group'),
      ]),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('This text will be displayed on the <em>Add group</em> page.'),
    ];

    if ($this->operation == 'add') {
      $form['add_admin_role'] = [
        '#title' => $this->t('Automatically configure an administrative role'),
        '#type' => 'checkbox',
        '#default_value' => 0,
        '#description' => $this->t("This will create an 'Admin' role by default which will have all possible permissions."),
      ];
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save group type');
    $actions['delete']['#value'] = $this->t('Delete group type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we might check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $type = $this->entity;
    $type->set('label', trim($type->label()));

    $status = $type->save();
    $t_args = ['%label' => $type->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The group type %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The group type %label has been added. You may now configure which roles a group creator will receive by editing the group type.', $t_args));
      $context = array_merge($t_args, ['link' => $type->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('group')->notice('Added group type %label.', $context);
    }

    // Create a default admin role if instructed to do so.
    if ($form_state->getValue('add_admin_role')) {
      $storage = $this->entityTypeManager->getStorage('group_role');

      /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
      $group_role = $storage->create([
        'id' => $type->id() . '-admin',
        'label' => $this->t('Admin'),
        'weight' => 100,
        'group_type' => $type->id(),
      ]);

      $group_role->grantAllPermissions()->save();
    }

    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
