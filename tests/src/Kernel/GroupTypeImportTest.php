<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Site\Settings;

/**
 * Tests the import or synchronization of group type entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeImportTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The system.site key is required for import validation.
    // See: https://www.drupal.org/project/drupal/issues/2995062
    $this->installConfig(['system']);
  }

  /**
   * Tests special behavior during group type import.
   *
   * @covers ::postSave
   * @covers \Drupal\group\EventSubscriber\ConfigSubscriber::onConfigImport
   */
  public function testImport() {
    // Simulate config data to import.
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    // Manually add the 'import' group type to the synchronization directory.
    $test_dir = __DIR__ . '/../../modules/group_test_config/sync';
    $sync_dir = Settings::get('config_sync_directory');
    $file_system = $this->container->get('file_system');
    $file_system->copy("$test_dir/group.type.import.yml", "$sync_dir/group.type.import.yml");

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the group type was created.
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('import');
    $this->assertNotNull($group_type, 'Group type was loaded successfully.');

    // Check that enforced plugins were installed.
    /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationInterface $plugin */
    $plugin_config = ['group_type_id' => 'import', 'id' => 'group_membership'];
    $plugin = $this->pluginManager->createInstance('group_membership', $plugin_config);

    $group_content_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    $group_content_type = $group_content_type_storage->load(
      $group_content_type_storage->getGroupContentTypeId($group_type->id(), $plugin->getRelationTypeId())
    );
    $this->assertNotNull($group_content_type, 'Enforced plugins were installed after config import.');
  }

}
