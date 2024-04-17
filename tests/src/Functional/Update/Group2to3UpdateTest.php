<?php

namespace Drupal\group\Tests\Functional\Update;

use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
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
   * Tests that fields referring to group_content are updated correctly.
   */
  public function testEntityReferenceFields(): void {
    $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
    assert($last_installed_schema_repository instanceof EntityLastInstalledSchemaRepositoryInterface);

    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('node');
    $this->assertSame('entity_reference', $field_storage_definitions['field_member_highlight']->getType());
    $this->assertSame('group_content', $field_storage_definitions['field_member_highlight']->getSetting('target_type'));

    $this->runUpdates();

    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('node');
    $this->assertSame('entity_reference', $field_storage_definitions['field_member_highlight']->getType());
    $this->assertSame('group_relationship', $field_storage_definitions['field_member_highlight']->getSetting('target_type'));
  }

  /**
   * Tests that the field tables are updated correctly.
   */
  public function testFieldTableMapping(): void {
    $database = \Drupal::database();
    $database_schema = $database->schema();

    // Results gotten from DefaultTableMapping::getDedicatedDataTableName().
    $fields = [
      'field_really_long_field_title_00' => [
        'table_old' => 'group_content__field_really_long_field_title_00',
        'table_new' => 'group_relationship__5eb81ace03;',
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

  /**
   * Tests that group_content_type is converted to group_relationship_type.
   */
  public function testGroupRelationshipTypes() {
    $this->assertEquals([
      'group.content_type.class-group_membership',
      'group.content_type.class-group_node-page',
    ], \Drupal::configFactory()->listAll('group.content_type.'));
    $this->assertEquals([], \Drupal::configFactory()->listAll('group.relationship_type.'));

    $this->runUpdates();

    $this->assertEquals([], \Drupal::configFactory()->listAll('group.content_type.'));
    $this->assertEquals([
      'group.relationship_type.class-group_membership',
      'group.relationship_type.class-group_node-page',
    ], \Drupal::configFactory()->listAll('group.relationship_type.'));
  }

}
