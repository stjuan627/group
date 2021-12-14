<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the group content schema handler.
 */
class GroupContentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        'group_content__entity_fields' => ['type', 'entity_id'],
        'group_content__plugin_id' => ['plugin_id', 'group_type'],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    if ($table_name = $this->storage->getDataTable()) {
      $field_name = $storage_definition->getName();

      switch ($field_name) {
        case 'group_type':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'plugin_id':
          // Improves the performance of the group_content__plugin_id index
          // defined in ::getEntitySchema() above.
          $schema['fields'][$field_name]['not null'] = TRUE;

          // The default field size would be 255, which is far too long. We can
          // reasonably assume that the total length of a plugin ID and perhaps
          // derivative ID would not exceed 64 characters. If we ever get a
          // complaint about this, we can bump it up to 128, but for now let's
          // choose performance over edge cases.
          $schema['fields'][$field_name]['length'] = 64;
          break;
      }
    }

    return $schema;
  }

}
