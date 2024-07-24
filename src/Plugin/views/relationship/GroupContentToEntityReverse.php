<?php

namespace Drupal\group\Plugin\views\relationship;

/**
 * A relationship handler which reverses group content entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_content_to_entity_reverse")
 */
class GroupContentToEntityReverse extends GroupContentToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJoinFieldType() {
    return 'field';
  }

}
