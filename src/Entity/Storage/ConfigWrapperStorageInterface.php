<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for group config wrapper entity storage classes.
 */
interface ConfigWrapperStorageInterface extends ContentEntityStorageInterface {

  /**
   * Retrieves a ConfigWrapper entity for a given config entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity ID to retrieve a wrapper for.
   * @param bool $create_if_missing
   *   (optional) Whether to create a wrapper if none exists yet. Defaults to
   *   TRUE.
   *
   * @return \Drupal\group\Entity\ConfigWrapperInterface|false
   *   A new or loaded ConfigWrapper entity or FALSE if $create_if_missing was
   *   set to FALSE and no wrapper could be loaded.
   */
  public function wrapEntity(ConfigEntityInterface $entity, $create_if_missing = TRUE);

  /**
   * Retrieves a ConfigWrapper entity for a given config entity ID.
   *
   * @param string $entity_type_id
   *   The config entity type ID to retrieve a wrapper for.
   * @param string $entity_id
   *   The config entity ID to retrieve a wrapper for.
   * @param bool $create_if_missing
   *   (optional) Whether to create a wrapper if none exists yet. Defaults to
   *   TRUE.
   *
   * @return \Drupal\group\Entity\ConfigWrapperInterface|false
   *   A new or loaded ConfigWrapper entity or FALSE if $create_if_missing was
   *   set to FALSE and no wrapper could be loaded.
   */
  public function wrapEntityId($entity_type_id, $entity_id, $create_if_missing = TRUE);

}
