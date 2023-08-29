<?php

namespace Drupal\group\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for groups.
 */
class GroupTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function getTranslationAccess(EntityInterface $entity, $op) {
    if (!$entity instanceof Group) {
      return parent::getTranslationAccess($entity, $op);
    }

    // We always override the access for groups based on group permissions.
    if ($entity->hasPermission('translate group', $this->currentUser)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
