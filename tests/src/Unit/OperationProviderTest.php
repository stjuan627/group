<?php

namespace Drupal\Tests\group\Unit {

  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\StringTranslation\TranslationInterface;
  use Drupal\group\Entity\GroupContentTypeInterface;
  use Drupal\group\Entity\GroupInterface;
  use Drupal\group\Entity\GroupTypeInterface;
  use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
  use Drupal\group\Plugin\Group\Relation\GroupRelationType;
  use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
  use Drupal\group\Plugin\Group\RelationHandlerDefault\OperationProvider;
  use Drupal\Tests\UnitTestCase;
  use Prophecy\Argument;

  /**
   * Tests the default group relation operation_provider handler.
   *
   * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\OperationProvider
   * @group group
   */
  class OperationProviderTest extends UnitTestCase {

    /**
     * Tests the retrieval of operations.
     *
     * @param mixed $expected
     *   The expected operation keys.
     * @param string $plugin_id
     *   The plugin ID.
     * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
     *   The plugin definition.
     * @param bool $installed
     *   Whether the plugin is installed.
     * @param bool $field_ui
     *   Whether Field UI is enabled.
     *
     * @covers ::getOperations
     * @dataProvider getOperationsProvider
     */
    public function testGetOperations($expected, $plugin_id, GroupRelationTypeInterface $definition, $installed, $field_ui) {
      $group_type = $this->prophesize(GroupTypeInterface::class);
      $group_type->id()->willReturn('some_type');
      $group_type->hasRelationPlugin($plugin_id)->willReturn($installed);

      $operation_provider = $this->createOperationProvider($plugin_id, $definition, $field_ui);
      $this->assertEquals($expected, array_keys($operation_provider->getOperations($group_type->reveal())));
    }

    /**
     * Data provider for testGetOperations().
     *
     * @return array
     *   A list of testGetOperations method arguments.
     */
    public function getOperationsProvider() {
      $cases = [];

      foreach ($this->getOperationProviderScenarios() as $key => $scenario) {
        $operation_keys = [];

        $case = $scenario;
        $ui_allowed = !$case['definition']->isEnforced() && !$case['definition']->isCodeOnly();

        if ($case['installed']) {
          $operation_keys[] = 'configure';
          if ($ui_allowed) {
            $operation_keys[] = 'uninstall';
          }
          if ($case['field_ui']) {
            $operation_keys[] = 'bar';
          }
        }
        elseif ($ui_allowed) {
          $operation_keys[] = 'install';
        }

        $case['expected'] = $operation_keys;
        $cases[$key] = $case;
      }

      return $cases;
    }


    /**
     * Tests the retrieval of group operations.
     *
     * @param mixed $expected
     *   The expected operation keys.
     * @param string $plugin_id
     *   The plugin ID.
     * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
     *   The plugin definition.
     *
     * @covers ::getGroupOperations
     * @dataProvider getGroupOperationsProvider
     */
    public function testGetGroupOperations($expected, $plugin_id, GroupRelationTypeInterface $definition) {
      $group = $this->prophesize(GroupInterface::class);
      $operation_provider = $this->createOperationProvider($plugin_id, $definition, FALSE);
      $this->assertEquals($expected, array_keys($operation_provider->getGroupOperations($group->reveal())));
    }

    /**
     * Data provider for testGetGroupOperations().
     *
     * @return array
     *   A list of testGetGroupOperations method arguments.
     */
    public function getGroupOperationsProvider() {
      $cases = [];

      foreach ($this->getOperationProviderScenarios() as $key => $scenario) {
        $cases[$key] = $scenario;
        $cases[$key]['expected'] = [];
      }

      return $cases;
    }

    /**
     * All possible scenarios for an operation provider.
     *
     * @return array
     *   A set of test cases to be used in data providers.
     */
    protected function getOperationProviderScenarios() {
      $scenarios = [];

      foreach ([TRUE, FALSE] as $installed) {
        $keys[0] = $installed ? 'installed' : 'not_installed';

        foreach ([TRUE, FALSE] as $field_ui) {
          $keys[1] = $field_ui ? 'field_ui' : 'no_field_ui';

          foreach ([TRUE, FALSE] as $is_enforced) {
            $keys[2] = $is_enforced ? 'enforced' : 'not_enforced';

            foreach ([TRUE, FALSE] as $is_code_only) {
              $keys[3] = $is_code_only ? 'code_only' : 'not_code_only';

              $scenarios[implode('-', $keys)] = [
                'expected' => NULL,
                // We use a derivative ID to prove these work.
                'plugin_id' => 'foo:baz',
                'definition' => new GroupRelationType([
                  'id' => 'foo',
                  'label' => 'Foo',
                  'entity_type_id' => 'bar',
                  'enforced' => $is_enforced,
                  'code_only' => $is_code_only,
                ]),
                'installed' => $installed,
                'field_ui' => $field_ui,
              ];
            }
          }
        }
      }

      return $scenarios;
    }

    /**
     * Instantiates a default operation provider handler.
     *
     * @return \Drupal\group\Plugin\Group\RelationHandlerDefault\OperationProvider
     *   The default permission provider handler.
     */
    protected function createOperationProvider($plugin_id, $definition, $field_ui) {
      $entity = $this->prophesize(GroupContentTypeInterface::class);
      $storage = $this->prophesize(GroupContentTypeStorageInterface::class);
      $storage->getGroupContentTypeId(Argument::cetera())->willReturn('foobar');
      $storage->load('foobar')->willReturn($entity->reveal());

      $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
      $entity_type_manager->getStorage('group_content_type')->willReturn($storage->reveal());

      $module_handler = $this->prophesize(ModuleHandlerInterface::class);
      $module_handler->moduleExists('field_ui')->willReturn($field_ui);
      $string_translation = $this->prophesize(TranslationInterface::class);

      $operation_provider = new OperationProvider($entity_type_manager->reveal(), $module_handler->reveal(), $string_translation->reveal());
      $operation_provider->init($plugin_id, $definition);
      return $operation_provider;
    }

  }
}

namespace {

  /**
   * Dummy replacement function for Field UI's actual one.
   *
   * @param mixed $foo
   *   Can accept anything.
   *
   * @return array
   *   A dummy operation.
   */
  function field_ui_entity_operation($foo) {
    return ['bar' => []];
  }

}

