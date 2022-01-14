<?php

namespace Drupal\group\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLinkDelete;

/**
 * Field handler to present a link to delete a group content related entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_related_entity_link_delete")
 */
class GroupRelatedEntityLinkDelete extends EntityLinkDelete {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'entity-delete-form';
  }

}
