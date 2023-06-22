<?php

namespace Drupal\group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for joining a group.
 */
class GroupListFilterForm extends FormBase {

  /**
   * @inerhitDoc
   */
  public function getFormId() {
    return 'group_list_filter';
  }

  /**
   * @inerhitDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $entityTypeManager = \Drupal::service('entity_type.manager');

    $types = ['0' => $this->t('--Any--')];
    $groupTypes = $entityTypeManager->getStorage('group_type')->loadMultiple();
    foreach ($groupTypes as $groupType) {
      $types[$groupType->id()] = $groupType->label();
    }

    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
    ];

    $form['filter']['name'] = [
      '#type' => 'textfield',
      '#title' => 'Name',
      '#default_value' => $request->get('name') ?? "",
    ];

    $form['filter']['type'] = [
      '#type' => 'select',
      '#title' => 'Type',
      '#options' => $types,
      '#default_value' => $request->get('type') ?? 0,
    ];

    $form['actions']['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-item']],
    ];

    $form['actions']['wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Filter',
    ];

    if ($request->getQueryString()) {
      $form['actions']['wrapper']['reset'] = [
        '#type' => 'submit',
        '#value' => 'Reset',
        '#submit' => ['::resetForm'],
      ];
    }

    return $form;
  }

  /**
   * @inerhitDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];

    $type = $form_state->getValue('type') ?? 0;
    if ($type) {
      $query['type'] = $type;
    }
    $name = $form_state->getValue('name') ?? "";
    if ($name) {
      $query['name'] = $name;
    }
    $form_state->setRedirect('entity.group.collection', $query);
  }

  /**
   * Reset handler method.
   */
  public function resetForm(array $form, FormStateInterface &$form_state) {
    $form_state->setRedirect('entity.group.collection');
  }

}
