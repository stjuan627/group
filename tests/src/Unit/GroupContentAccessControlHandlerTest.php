<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Access\GroupContentAccessControlHandler;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the group content access control handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Access\GroupContentAccessControlHandler
 * @group group
 */
class GroupContentAccessControlHandlerTest extends UnitTestCase {

  /**
   * The account to test with.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $groupRelationManager;

  /**
   * The access control handler.
   *
   * @var \Drupal\group\Entity\Access\GroupContentAccessControlHandler|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $accessControlHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->prophesize(AccountInterface::class);
    $this->account->id()->willReturn(1986);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->groupRelationManager = $this->prophesize(GroupRelationManagerInterface::class);
    $moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $moduleHandler->invokeAll(Argument::cetera())->willReturn([]);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('entity_type.manager')->willReturn($this->entityTypeManager->reveal());
    $container->get('plugin.manager.group_relation')->willReturn($this->groupRelationManager->reveal());
    $container->get('module_handler')->willReturn($moduleHandler->reveal());
    \Drupal::setContainer($container->reveal());

    $entityType = $this->prophesize(EntityTypeInterface::class);
    $this->accessControlHandler = GroupContentAccessControlHandler::createInstance(
      $container->reveal(),
      $entityType->reveal()
    );
  }

  /**
   * Tests access.
   *
   * @covers ::checkAccess
   * @uses ::access
   */
  public function testCheckAccess() {
    $group_relation = $this->prophesize(GroupRelationInterface::class);
    $group_relation->getPluginId()->willReturn('bar');

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn('nl');

    $group_content = $this->prophesize(GroupContentInterface::class);
    $group_content->id()->willReturn(1337);
    $group_content->uuid()->willReturn('baz');
    $group_content->language()->willReturn($language->reveal());
    $group_content->getRevisionId()->willReturn(9001);
    $group_content->getEntityTypeId()->willReturn('group_content');
    $group_content->getContentPlugin()->willReturn($group_relation->reveal());

    $access_result = AccessResult::allowed();
    $access_control = $this->prophesize(AccessControlInterface::class);
    $access_control->relationAccess($group_content->reveal(), 'some_operation', $this->account->reveal(), TRUE)->shouldBeCalled()->willReturn($access_result);
    $this->groupRelationManager->getAccessControlHandler('bar')->willReturn($access_control->reveal());

    $result = $this->accessControlHandler->access(
      $group_content->reveal(),
      'some_operation',
      $this->account->reveal()
    );
    $this->assertEquals($access_result->isAllowed(), $result);
  }

  /**
   * Tests create access.
   *
   * @covers ::checkCreateAccess
   * @uses ::createAccess
   */
  public function testCheckCreateAccess() {
    $group = $this->prophesize(GroupInterface::class);

    $group_content_type = $this->prophesize(GroupContentTypeInterface::class);
    $group_content_type->getContentPluginId()->willReturn('bar');
    $group_content_type_storage = $this->prophesize(GroupContentTypeStorageInterface::class);
    $group_content_type_storage->load('foo')->willReturn($group_content_type->reveal());
    $this->entityTypeManager->getStorage('group_content_type')->willReturn($group_content_type_storage->reveal());

    $access_result = AccessResult::allowed();
    $access_control = $this->prophesize(AccessControlInterface::class);
    $access_control->relationCreateAccess($group->reveal(), $this->account->reveal(), TRUE)->shouldBeCalled()->willReturn($access_result);
    $this->groupRelationManager->getAccessControlHandler('bar')->willReturn($access_control->reveal());

    $result = $this->accessControlHandler->createAccess(
      'foo',
      $this->account->reveal(),
      ['group' => $group->reveal()]
    );
    $this->assertEquals($access_result->isAllowed(), $result);
  }

}
