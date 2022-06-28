<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for group config wrapper entities.
 */
class ConfigWrapperStorage extends SqlContentEntityStorage implements ConfigWrapperStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function wrapEntity(ConfigEntityInterface $entity) {
    $properties = [
      'bundle' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id()
    ];

    if ($wrappers = $this->loadByProperties($properties)) {
      return reset($wrappers);
    }

    $wrapper = $this->create($properties);
    $this->save($wrapper);
    return $wrapper;
  }

}