<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Provides a form for deleting a group content entity.
 */
class GroupContentDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Returns the plugin responsible for this piece of group content.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The responsible group relation.
   */
  protected function getPlugin() {
    $group_content = $this->getEntity();
    assert($group_content instanceof GroupContentInterface);
    return $group_content->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    $group_content = $this->getEntity();
    assert($group_content instanceof GroupContentInterface);
    $group = $group_content->getGroup();
    $route_params = [
      'group' => $group->id(),
      'group_content' => $group_content->id(),
    ];
    return new Url('entity.group_content.canonical', $route_params);
  }

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
    $group_content = $this->getEntity();
    assert($group_content instanceof GroupContentInterface);
    $group = $group_content->getGroup();
    $group_content->delete();

    \Drupal::logger('group_content')->notice('@type: deleted %title.', [
      '@type' => $group_content->bundle(),
      '%title' => $group_content->label(),
    ]);

    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
