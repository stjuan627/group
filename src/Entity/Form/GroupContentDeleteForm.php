<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a group content entity.
 */
class GroupContentDeleteForm extends GroupContentBaseConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupContent $group_content */
    $group_content = $this->getEntity();
    $group = $group_content->getGroup();
    $group_content->delete();

    \Drupal::logger('group_content')->notice('@type: deleted %title.', [
      '@type' => $group_content->bundle(),
      '%title' => $group_content->label(),
    ]);

    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
