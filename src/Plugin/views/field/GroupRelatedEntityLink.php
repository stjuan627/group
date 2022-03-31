<?php

namespace Drupal\group\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Field handler to present a link to the group content related entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("group_related_entity_link")
 */
class GroupRelatedEntityLink extends EntityLink {

  /**
   * Returns the entity link template name identifying the link route.
   *
   * @returns string
   *   The link template name.
   */
  protected function getEntityLinkTemplate() {
    return 'entity-view';
  }

}
