<?php

namespace Drupal\group\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLinkEdit;

/**
 * Field handler to present a link to edit a group content related entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_related_entity_link_edit")
 */
class GroupRelatedEntityLinkEdit extends EntityLinkEdit {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'entity-edit-form';
  }

}
