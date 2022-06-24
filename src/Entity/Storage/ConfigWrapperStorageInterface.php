<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for group config wrapper entity storage classes.
 */
interface ConfigWrapperStorageInterface extends ContentEntityStorageInterface {

  /**
   * Creates a ConfigWrapper entity a config entity is none exists yet.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The content entity to add to the group.
   *
   * @return \Drupal\group\Entity\ConfigWrapperInterface
   *   A new or loaded ConfigWrapper entity.
   */
  public function wrapEntity(ConfigEntityInterface $entity);

}
