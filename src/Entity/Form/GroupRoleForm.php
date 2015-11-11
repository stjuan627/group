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
 *
 * @todo Global vs Type.
 */
class GroupRoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $role \Drupal\group\Entity\GroupRole */
    $form = parent::form($form, $form_state);
    $role = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add group role');
    }
    else {
      $form['#title'] = $this->t('Edit %label group role', array('%label' => $role->label()));
    }

    $form['label'] = array(
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $role->label(),
      '#description' => t('The human-readable name of this group role. This text will be displayed on the group permissions page.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $role->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $role->isLocked(),
      '#machine_name' => array(
        'exists' => ['Drupal\group\Entity\GroupRole', 'load'],
        'source' => array('label'),
      ),
      '#description' => t('A unique machine-readable name for this group role. It must only contain lowercase letters, numbers, and underscores.'),
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
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
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", array('%invalid' => $id)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var $role \Drupal\group\Entity\GroupRole */
    $role = $this->entity;
    $role->set('id', trim($role->id()));
    $role->set('label', trim($role->label()));

    $status = $role->save();
    $t_args = array('%label' => $role->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The group role %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The group role %label has been added.', $t_args));

      // @todo Exact link.
      $context = array_merge($t_args, array('link' => $role->link($this->t('View'), 'collection')));
      $this->logger('group')->notice('Added group role %label.', $context);
    }

    // @todo Exact link.
    $form_state->setRedirectUrl($role->urlInfo('collection'));
  }

}
