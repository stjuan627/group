<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Form\GroupRoleForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for group role forms.
 */
class GroupRoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
    $form = parent::form($form, $form_state);
    $group_role = $this->entity;

    if ($group_role->isInternal()) {
      return [
        '#title' => t('Error'),
        'description' => ['#markup' => '<p>' . t('Cannot edit an internal group role directly.') . '</p>'],
      ];
    }

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add group role');
    }
    else {
      $form['#title'] = $this->t('Edit %label group role', array('%label' => $group_role->label()));
    }

    $form['label'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $group_role->label(),
      '#description' => t('The human-readable name of this group role. This text will be displayed on the group permissions page.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $group_role->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => array(
        'exists' => ['Drupal\group\Entity\GroupRole', 'load'],
        'source' => array('label'),
      ),
      '#description' => t('A unique machine-readable name for this group role. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['weight'] = array(
      '#type' => 'value',
      '#value' => $group_role->getWeight(),
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Do not show action buttons for an internal group role.
    if ($this->entity->isInternal()) {
      return [];
    }

    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save group role');
    $actions['delete']['#value'] = t('Delete group role');
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
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }

    // Do not allow reserved prefixes.
    if (preg_match('/^(a|o|m)_/i', $id)) {
      $form_state->setErrorByName('id', $this->t("Group role machine names may not start with 'a_', 'o_' or 'm_'."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
    $group_role = $this->entity;
    $group_role->set('label', trim($group_role->label()));

    $status = $group_role->save();
    $t_args = array('%label' => $group_role->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The group role %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The group role %label has been added.', $t_args));

      $context = array_merge($t_args, array('link' => $group_role->link($this->t('View'), 'collection')));
      $this->logger('group')->notice('Added group role %label.', $context);
    }

    $form_state->setRedirectUrl($group_role->urlInfo('collection'));
  }

}
