<?php
/**
 * @file
 * Contains Drupal\group\Entity\Form\GroupContentForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group content edit forms.
 *
 * @ingroup group
 */
class GroupContentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // @todo Read a redirect from the plugin?
    $form_state->setRedirect('entity.group.collection');
    return parent::save($form, $form_state);
  }

}
