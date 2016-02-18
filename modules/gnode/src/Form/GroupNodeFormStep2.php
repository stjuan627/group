<?php

/**
 * @file
 * Contains \Drupal\gnode\Form\GroupNodeFormStep2.
 */

namespace Drupal\gnode\Form;

use Drupal\group\Entity\Form\GroupContentForm;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form creating a node in a group.
 */
class GroupNodeFormStep2 extends GroupContentForm {

  /**
   * The private store for temporary group nodes.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a GroupNodeFormStep2 object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($entity_manager);
    $this->privateTempStore = $temp_store_factory->get('gnode_add_temp');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['entity_id']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // @todo Limit validation errors.
    $actions['back'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::submitForm', '::back'],
    );

    return $actions;
  }

  // @todo Validate if there is a node in temp store.

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($node = $this->privateTempStore->get('node')) {
      // We can now safely save the node and set its ID on the group content.
      $node->save();
      $this->entity->set('entity_id', $node->id());

      // We also clear the private store so we can start fresh next time around.
      $this->privateTempStore->delete('step');
      $this->privateTempStore->delete('node');
      $this->privateTempStore->delete('group_content');

      // @todo Read a redirect from the plugin?
      $form_state->setRedirect('entity.group.collection');
      return parent::save($form, $form_state);
    }
  }

  /**
   * Goes back to step 1 of group node creation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\gnode\Controller\GroupNodeController::add()
   * @see \Drupal\gnode\Form\GroupNodeFormStep1
   */
  public function back(array &$form, FormStateInterface $form_state) {
    $this->privateTempStore->set('group_content', $this->entity);
    $this->privateTempStore->set('step', 1);
  }

}
