<?php

/**
 * @file
 * Contains \Drupal\gnode\Form\GroupNodeFormStep1.
 */

namespace Drupal\gnode\Form;

use Drupal\node\NodeForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a creating a node without it being saved yet.
 */
class GroupNodeFormStep1 extends NodeForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Continue to final step'),
      '#submit' => array('::submitForm', '::saveTemporary'),
    );

    // @todo Cancel button to empty the temp store.

    return $actions;
  }

  /**
   * Saves a temporary node and continues to step 2 of group node creation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\gnode\Controller\GroupNodeController::add()
   * @see \Drupal\gnode\Form\GroupNodeFormStep2
   */
  public function saveTemporary(array &$form, FormStateInterface $form_state) {
    $store = $this->tempStoreFactory->get('gnode_add_temp');
    $store->set('node', $this->entity);
    $store->set('step', 2);
  }

}
