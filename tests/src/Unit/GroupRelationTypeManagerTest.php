<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager;
use Drupal\group\Plugin\Group\RelationHandler\RelationHandlerInterface;
use Drupal\group\Plugin\Group\RelationHandler\RelationHandlerTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the group relation type manager.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
 * @group group
 */
class GroupRelationTypeManagerTest extends UnitTestCase {

  /**
   * The group relation type manager under test.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
   */
  protected $groupRelationTypeManager;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $container;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $discovery;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheBackend;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cacheBackend = $this->prophesize(CacheBackendInterface::class);

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->moduleHandler->getImplementations('entity_type_build')->willReturn([]);
    $this->moduleHandler->alter('group_relation_type', Argument::type('array'))->willReturn(NULL);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $storage = $this->prophesize(ContentEntityStorageInterface::class);
    $this->entityTypeManager->getStorage('group')->willReturn($storage->reveal());
    $storage = $this->prophesize(ConfigEntityStorageInterface::class);
    $this->entityTypeManager->getStorage('group_type')->willReturn($storage->reveal());

    $this->groupRelationTypeManager = new TestGroupRelationTypeManager(new \ArrayObject(), $this->cacheBackend->reveal(), $this->moduleHandler->reveal(), $this->entityTypeManager->reveal());

    $this->discovery = $this->prophesize(DiscoveryInterface::class);
    $this->groupRelationTypeManager->setDiscovery($this->discovery->reveal());

    $this->container = $this->prophesize(ContainerInterface::class);
    $this->groupRelationTypeManager->setContainer($this->container->reveal());
  }

  /**
   * Sets up the group relation type manager to be tested.
   *
   * @param array $definitions
   *   (optional) An array of group relation type definitions.
   */
  protected function setUpPluginDefinitions($definitions = [], $handlers = []) {
    $this->discovery->getDefinition(Argument::cetera())
      ->will(function ($args) use ($definitions) {
        $plugin_id = $args[0];
        $exception_on_invalid = $args[1];
        if (isset($definitions[$plugin_id])) {
          return $definitions[$plugin_id];
        }
        elseif (!$exception_on_invalid) {
          return NULL;
        }
        else {
          throw new PluginNotFoundException($plugin_id);
        }
      });
    $this->discovery->getDefinitions()->willReturn($definitions);

    foreach ($definitions as $plugin_id => $definition) {
      foreach ($handlers as $handler_name => $class_name) {
        $service_name = "group.relation_handler.$handler_name.{$definition->id()}";
        if ($class_name === FALSE) {
          $this->container->has($service_name)->willReturn(FALSE);
        }
        else {
          // Our relation handlers are defined as non-shared services, so we
          // need to return a new copy every time to mimic that behavior.
          $this->container->has($service_name)->willReturn(TRUE);
          $this->container->get($service_name)->will(function () use ($class_name) {
            return new $class_name();
          });
        }
      }
    }
  }

  /**
   * Tests the createHandlerInstance() method.
   *
   * @covers ::createHandlerInstance
   */
  public function testCreateHandlerInstance() {
    $this->setUpPluginDefinitions(
      ['some_plugin' => new GroupRelationType(['id' => 'some_plugin'])],
      ['foo_handler' => TestGroupRelationHandler::class]
    );

    $handler = $this->groupRelationTypeManager->createHandlerInstance('some_plugin', 'foo_handler');
    $this->assertInstanceOf(RelationHandlerInterface::class, $handler);
  }

  /**
   * Tests exception thrown when a handler does not implement the interface.
   *
   * @covers ::createHandlerInstance
   */
  public function testCreateHandlerInstanceNoInterface() {
    $this->setUpPluginDefinitions(
      ['some_plugin' => new GroupRelationType(['id' => 'some_plugin'])],
      ['foo_handler' => TestGroupRelationHandlerWithoutInterface::class]
    );

    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('Trying to instantiate a handler that does not implement \Drupal\group\Plugin\Group\RelationHandler\RelationHandlerInterface.');
    $this->groupRelationTypeManager->createHandlerInstance('some_plugin', 'foo_handler');
  }

  /**
   * Tests the getHandler() method.
   *
   * @covers ::getHandler
   * @depends testCreateHandlerInstance
   */
  public function testGetHandler() {
    $this->setUpPluginDefinitions(
      ['apple' => new GroupRelationType(['id' => 'apple'])],
      ['foo_handler' => TestGroupRelationHandler::class]
    );

    $first_call_result = $this->groupRelationTypeManager->getHandler('apple', 'foo_handler');
    $second_call_result = $this->groupRelationTypeManager->getHandler('apple', 'foo_handler');
    $direct_call_result = $this->groupRelationTypeManager->createHandlerInstance('apple', 'foo_handler');

    $this->assertEquals(
      $first_call_result,
      $direct_call_result,
      'Got the same result as if createHandlerInstance() were called directly.'
    );

    $this->assertSame(
      $first_call_result,
      $second_call_result,
      'Got the exact same handler instance when called another time.'
    );

    $this->assertNotSame(
      $first_call_result,
      $direct_call_result,
      'Calling createHandlerInstance() creates a fresh copy regardless of internal cache.'
    );
  }

  /**
   * Tests the getHandler() method with derivative IDs.
   *
   * @covers ::getHandler
   * @depends testGetHandler
   */
  public function testGetHandlerWithDerivatives() {
    $this->setUpPluginDefinitions(
      ['apple:red' => new GroupRelationType(['id' => 'apple'])],
      ['foo_handler' => TestGroupRelationHandler::class]
    );

    $handler = $this->groupRelationTypeManager->createHandlerInstance('apple:red', 'foo_handler');
    $this->assertInstanceOf(RelationHandlerInterface::class, $handler);
  }

  /**
   * Tests exception thrown when a plugin has not defined the requested handler.
   *
   * @covers ::getHandler
   */
  public function testGetHandlerMissingHandler() {
    $this->setUpPluginDefinitions(
      ['apple' => new GroupRelationType(['id' => 'apple'])],
      ['foo_handler' => FALSE]
    );

    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The "apple" plugin did not specify a foo_handler handler service (group.relation_handler.foo_handler.apple).');
    $this->groupRelationTypeManager->getHandler('apple', 'foo_handler');
  }

  /**
   * Tests the getAccessControlHandler() method.
   *
   * @covers ::getAccessControlHandler
   */
  public function testGetAccessControlHandler() {
    $this->setUpPluginDefinitions(
      ['apple' => new GroupRelationType(['id' => 'apple'])],
      ['access_control' => TestGroupRelationHandler::class]
    );

    $this->assertInstanceOf(RelationHandlerInterface::class, $this->groupRelationTypeManager->getAccessControlHandler('apple'));
  }

  /**
   * Tests the getPermissionProvider() method.
   *
   * @covers ::getPermissionProvider
   */
  public function testGetPermissionProvider() {
    $this->setUpPluginDefinitions(
      ['apple' => new GroupRelationType(['id' => 'apple'])],
      ['permission_provider' => TestGroupRelationHandler::class]
    );

    $this->assertInstanceOf(RelationHandlerInterface::class, $this->groupRelationTypeManager->getPermissionProvider('apple'));
  }

}

class TestGroupRelationTypeManager extends GroupRelationTypeManager {

  /**
   * Sets the discovery for the manager.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The discovery object.
   */
  public function setDiscovery(DiscoveryInterface $discovery) {
    $this->discovery = $discovery;
  }

}

class TestGroupRelationHandler implements RelationHandlerInterface {
  use RelationHandlerTrait;
}

class TestGroupRelationHandlerWithoutInterface {

}
