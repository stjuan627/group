<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityHandlerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\InvalidLinkTemplateException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Plugin\GroupContentEnablerCollection;
use Drupal\group\Plugin\GroupContentEnablerManager;
use Drupal\group\Plugin\GroupContentHandlerBase;
use Drupal\group\Plugin\GroupContentHandlerInterface;
use Drupal\group\Plugin\GroupContentPermissionProviderInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the GroupContentEnabler plugin manager.
 *
 * @coversDefaultClass \Drupal\group\Plugin\GroupContentEnablerManager
 * @group group
 */
class GroupContentEnablerManagerTest extends UnitTestCase {

  /**
   * The group content enabler manager under test.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManager
   */
  protected $groupContentEnablerManager;

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
    $this->moduleHandler->alter('group_content_info', Argument::type('array'))->willReturn(NULL);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $storage = $this->prophesize(ContentEntityStorageInterface::class);
    $this->entityTypeManager->getStorage('group')->willReturn($storage->reveal());
    $storage = $this->prophesize(ConfigEntityStorageInterface::class);
    $this->entityTypeManager->getStorage('group_type')->willReturn($storage->reveal());

    $this->groupContentEnablerManager = new TestGroupContentEnablerManager(new \ArrayObject(), $this->cacheBackend->reveal(), $this->moduleHandler->reveal(), $this->entityTypeManager->reveal());
    $this->discovery = $this->prophesize(DiscoveryInterface::class);
    $this->groupContentEnablerManager->setDiscovery($this->discovery->reveal());
  }

  /**
   * Sets up the entity type manager to be tested.
   *
   * @param array $definitions
   *   (optional) An array of group content enabler definitions.
   */
  protected function setUpPluginDefinitions($definitions = []) {
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
  }

  /**
   * Tests the hasHandler() method.
   *
   * @param string $plugin_id
   *   The ID of the plugin to check the handler for.
   * @param bool $expected
   *   Whether the handler is expected to be found.
   *
   * @covers ::hasHandler
   * @dataProvider providerTestHasHandler
   */
  public function testHasHandler($plugin_id, $expected) {
    $apple = [
      'handlers' => [
        'foo_handler' => $this->getTestHandlerClass(),
      ],
    ];
    $banana = ['handlers' => ['foo_handler' => FALSE]];
    $this->setUpPluginDefinitions(['apple' => $apple, 'banana' => $banana]);
    $this->assertSame($expected, $this->groupContentEnablerManager->hasHandler($plugin_id, 'foo_handler'));
  }

  /**
   * Provides test data for testHasHandler().
   *
   * @return array
   *   Test data.
   */
  public function providerTestHasHandler() {
    return [
      ['apple', TRUE],
      ['banana', FALSE],
      ['pear', FALSE],
    ];
  }

  /**
   * Tests the getHandler() method.
   *
   * @covers ::getHandler
   * @covers ::createHandlerInstance
   */
  public function testGetHandler() {
    $class = $this->getTestHandlerClass();
    $apple = ['handlers' => ['foo_handler' => $class]];
    $this->setUpPluginDefinitions(['apple' => $apple]);
    $handler = $this->groupContentEnablerManager->getHandler('apple', 'foo_handler');
    $this->assertInstanceOf($class, $handler);
    $this->assertAttributeInstanceOf(ModuleHandlerInterface::class, 'moduleHandler', $handler);
  }

  /**
   * Tests the getHandler() method when no controller is defined.
   *
   * @covers ::getHandler
   */
  public function testGetHandlerMissingHandler() {
    $this->setUpPluginDefinitions(['apple' => ['handlers' => []]]);
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->groupContentEnablerManager->getHandler('apple', 'foo_handler');
  }

  /**
   * Tests the getPermissionProvider() method.
   *
   * @covers ::getPermissionProvider
   */
  public function testGetPermissionProvider() {
    $class = $this->getTestHandlerClass();
    $apple = ['handlers' => ['permission_provider' => $class]];
    $this->setUpPluginDefinitions(['apple' => $apple]);
    $this->assertInstanceOf($class, $this->groupContentEnablerManager->getPermissionProvider('apple'));
  }

  /**
   * Gets a mock controller class name.
   *
   * @return string
   *   A mock controller class name.
   */
  protected function getTestHandlerClass() {
    return get_class($this->getMockForAbstractClass(GroupContentHandlerBase::class));
  }

}

class TestGroupContentEnablerManager extends GroupContentEnablerManager {

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
