<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Plugin\Group\RelationHandler\EntityReferenceInterface;
use Drupal\group\Plugin\Group\RelationHandler\EntityReferenceTrait;

/**
 * Provides post install tasks for group relations.
 */
class EntityReference implements EntityReferenceInterface {

  use EntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function configureField(BaseFieldDefinition $entity_reference) {
    $entity_reference->setSetting('target_type', $this->groupRelationType->getEntityTypeId());

    if ($bundle = $this->groupRelationType->getEntityBundle()) {
      $handler_settings = $entity_reference->getSetting('handler_settings');
      $handler_settings['target_bundles'] = [$bundle];
      $entity_reference->setSetting('handler_settings', $handler_settings);
    }

    if ($label = $this->groupRelationType->getEntityReferenceLabel()) {
      $entity_reference->setLabel($label);
    }
    if ($description = $this->groupRelationType->getEntityReferenceDescription()) {
      $entity_reference->setDescription($description);
    }
  }

}
