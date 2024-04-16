<?php

namespace Drupal\group\Tests\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the Group v2 to v3 update path.
 *
 * @group group
 */
class Group2to3UpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/group-v-2-3-x.php.gz',
    ];
  }

  /**
   * Tests that the field table mapping is updated correctly.
   */
  public function testFieldTableMapping() {
    $database = \Drupal::database();
    $database_schema = $database->schema();

    $fields = [
      'field_really_long_field_title_00' => [
        'table_old' => 'group_content__field_really_long_field_title_00',
        'table_new' => 'group_relationship__field_really_long_field_title_00',
      ],
      'field_short_field' => [
        'table_old' => 'group_content__field_short_field',
        'table_new' => 'group_relationship__field_short_field',
      ],
      'group_roles' => [
        'table_old' => 'group_content__group_roles',
        'table_new' => 'group_relationship__group_roles',
      ],
    ];

    $field_data = [];
    foreach ($fields as $field_name => $field_info) {
      $this->assertTrue($database_schema->tableExists($field_info['table_old']));
      $this->assertFalse($database_schema->tableExists($field_info['table_new']));
      $field_data[$field_name] = $database->select($field_info['table_old'], 't')->fields('t')->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    $this->runUpdates();

    foreach ($fields as $field_name => $field_info) {
      $this->assertFalse($database_schema->tableExists($field_info['table_old']));
      $this->assertTrue($database_schema->tableExists($field_info['table_new']));
      $this->assertSame($field_data[$field_name], $database->select($field_info['table_new'], 't')->fields('t')->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
  }

}
