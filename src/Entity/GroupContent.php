<?php
/**
 * @file
 * Contains \Drupal\group\Entity\GroupContent.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Group content entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_content",
 *   label = @Translation("Group content entity"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentDeleteForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupContentAccessControlHandler",
 *   },
 *   base_table = "group_content",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "group_content_type",
 *   field_ui_base_route = "entity.group_content_type.edit_form",
 *   permission_granularity = "bundle"
 * )
 */
class GroupContent extends ContentEntityBase implements GroupContentInterface {

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group content ID'))
      ->setDescription(t('The ID of the Group content entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Group content entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The group content type.'))
      ->setSetting('target_type', 'group_content_type')
      ->setReadOnly(TRUE);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent group'))
      ->setDescription(t('The group containing the entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE);

    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content'))
      ->setDescription(t('The entity to add to the group.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The group content language code.'))
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
      ->setReadOnly(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    if ($group_content_type = GroupContentType::load($bundle)) {
      $fields['entity_id'] = clone $base_field_definitions['entity_id'];
      $fields['entity_id']->setSetting('target_type', $group_content_type->getContentPlugin()->getEntityTypeId());
      return $fields;
    }

    return [];
  }

}
