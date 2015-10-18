<?php
/**
 * @file
 * Contains \Drupal\content_entity_example\Entity\ContentEntityExample.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\group\GroupInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;


/**
 * Defines the Group entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group",
 *   label = @Translation("Group entity"),
 *   bundle_label = @Translation("Group type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\group\Form\GroupForm",
 *       "edit" = "Drupal\group\Form\GroupForm",
 *       "delete" = "Drupal\group\Form\GroupDeleteForm",
 *     },
 *     "access" = "Drupal\group\GroupAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "groups",
 *   admin_permission = "administer group",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/group/{group}",
 *     "edit-form" = "/group/{group}/edit",
 *     "delete-form" = "/group/{group}/delete",
 *     "collection" = "/group/list"
 *   },
 *   bundle_entity_type = "group_type",
 *   field_ui_base_route = "entity.group_type.edit_form",
 *   permission_granularity = "bundle"
 * )
 */
class Group extends ContentEntityBase implements GroupInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      return parent::access($operation, $account, $return_as_object);
    }

    return \Drupal::entityManager()
      ->getAccessControlHandler($this->entityTypeId)
      ->access($this, $operation, $this->prepareLangcode(), $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareLangcode() {
    $langcode = $this->language()->getId();

    // Try to show Groups in the content language if it's set.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $group_translations = $this->getTranslationLanguages();
      $content_negotiation_langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

      // If there is a translation available, use it.
      if (isset($group_translations[$content_negotiation_langcode])) {
        $langcode = $content_negotiation_langcode;
      }
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t('The ID of the Group entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Group entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The group type.'))
      ->setSetting('target_type', 'group_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The node language code.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      /*->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))*/
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);


    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group creator'))
      ->setDescription(t('The username of the group creator.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\group\Entity\Group::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
