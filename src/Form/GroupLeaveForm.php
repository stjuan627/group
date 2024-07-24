<?php

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupContentDeleteForm;

/**
 * Provides a form for leaving a group.
 */
class GroupLeaveForm extends GroupContentDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // Define the message with a placeholder.
    $message = $this->t('Are you sure you want to leave @group?', ['@group' => $this->getEntity()->getGroup()->label()]);
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Leave group');
  }

}
