<?php

namespace Drupal\group;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Provides the Entity Type Bundle Info Service.
 */
trait EntityTypeBundleInfoTrait {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Gets the entity type bundle info service.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   The entity type bundle info service.
   */
  protected function getEntityTypeBundleInfo(): EntityTypeBundleInfoInterface {
    if (!$this->entityTypeBundleInfo) {
      $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle');
    }

    return $this->entityTypeBundleInfo;
  }

  /**
   * Gets the entity type bundle info service to use.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   *
   * @return $this
   */
  public function setEntityTypeBundleInfo(EntityTypeBundleInfoInterface $entity_type_bundle_info): self {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;

    return $this;
  }

}
