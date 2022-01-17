<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupContentStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the default group relation access_control handler.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl
 * @group group
 */
class AccessControlTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * Tests whether the operation is supported.
   *
   * @param bool $expected
   *   The expected outcome.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param string $operation
   *   The permission operation. Usually "create", "view", "update" or "delete".
   * @param string $target
   *   The target of the operation. Can be 'relation' or 'entity'.
   * @param string|false $permission
   *   The operation permission.
   * @param string|false $own_permission
   *   The owner operation permission.
   * @param bool $is_ownable
   *   Whether the entity can be owned.
   * @param bool $is_publishable
   *   Whether the entity can be (un)published.
   *
   * @covers ::supportsOperation
   * @dataProvider supportsOperationProvider
   */
  public function testSupportsOperation($expected, $plugin_id, GroupRelationTypeInterface $definition, $operation, $target, $permission, $own_permission, $is_ownable, $is_publishable) {
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn($is_publishable);
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn($is_ownable);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getPermission($operation, $target, 'any')->willReturn($permission);
    if ($target === 'relation' || $is_ownable) {
      $permission_provider->getPermission($operation, $target, 'own')->willReturn($own_permission);
    }
    else {
      $permission_provider->getPermission($operation, $target, 'own')->shouldNotBeCalled();
    }

    $relation_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $relation_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider->reveal());

    $access_control_handler = new AccessControl($entity_type_manager->reveal(), $relation_manager->reveal());
    $access_control_handler->init($plugin_id, $definition);

    $this->assertSame($expected, $access_control_handler->supportsOperation($operation, $target));
  }

  /**
   * Data provider for testSupportsOperation().
   *
   * @return array
   *   A list of testSupportsOperation method arguments.
   */
  public function supportsOperationProvider() {
    foreach (['relation', 'entity'] as $target) {
      $keys[0] = $target;

      foreach (['administer foo', FALSE] as $admin_permission) {
        $keys[1] = $admin_permission ? 'admin' : 'noadmin';

        foreach (['any some permission name', FALSE] as $any_permission) {
          $keys[2] = $any_permission ? 'anyperm' : 'noanyperm';

          foreach (['own some permission name', FALSE] as $own_permission) {
            $keys[3] = $own_permission ? 'ownperm' : 'noownperm';

            foreach ([TRUE, FALSE] as $is_ownable) {
              $keys[4] = $is_ownable ? 'ownable' : 'noownable';

              foreach ([TRUE, FALSE] as $is_publishable) {
                $keys[5] = $is_publishable ? 'pub' : 'nopub';

                if ($target === 'relation') {
                  $expected = $any_permission || $own_permission;
                }
                else {
                  $expected = $any_permission !== FALSE;
                  if (!$expected && $is_ownable) {
                    $expected = $own_permission !== FALSE;
                  }
                }

                $case = [
                  'expected' => $expected,
                  // We use a derivative ID to prove these work.
                  'plugin_id' => 'foo:baz',
                  'definition' => new GroupRelationType([
                    'id' => 'foo',
                    'label' => 'Foo',
                    'entity_type_id' => 'bar',
                    'admin_permission' => $admin_permission,
                  ]),
                  'operation' => 'some operation',
                  'target' => $target,
                  'any_permission' => $any_permission,
                  'own_permission' => $own_permission,
                  'is_ownable' => $is_ownable,
                  'is_publishable' => $is_publishable,
                ];

                yield implode('-', $keys) => $case;
              }
            }
          }
        }
      }
    }
  }

  /**
   * Tests the relation operation access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param bool $has_own_permission
   *   Whether the account has the required owner permission.
   * @param string|false $permission
   *   The operation permission.
   * @param string|false $own_permission
   *   The owner operation permission.
   * @param bool $is_owner
   *   Whether the account owns the relation.
   *
   * @covers ::relationAccess
   * @dataProvider relationAccessProvider
   */
  public function testRelationAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $has_own_permission, $permission, $own_permission, $is_owner) {
    $operation = $this->randomMachineName();

    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission($operation, 'relation', 'any')->willReturn($permission);
    $permission_provider->getPermission($operation, 'relation', 'own')->willReturn($own_permission);

    $relation_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $relation_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider->reveal());

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(Argument::any())->willReturn(FALSE);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $access_control_handler = new AccessControl($entity_type_manager->reveal(), $relation_manager->reveal());
    $access_control_handler->init($plugin_id, $definition);

    $account_id = rand(1, 100);
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($account_id);
    $account = $account->reveal();

    $group = $this->prophesize(GroupInterface::class);
    $group_content = $this->prophesize(GroupContentInterface::class);
    $group_content->getGroup()->willReturn($group->reveal());
    $group_content->getOwnerId()->willReturn($is_owner ? $account_id : $account_id + 1);
    $group_content->getCacheContexts()->willReturn([]);
    $group_content->getCachetags()->willReturn(['group_content:foo']);
    $group_content->getCacheMaxAge()->willReturn(9999);

    if ($definition->getAdminPermission()) {
      $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
    }
    else {
      $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
    }

    if ($permission) {
      $group->hasPermission($permission, $account)->willReturn($has_permission);
    }
    else {
      $group->hasPermission($permission, $account)->shouldNotBeCalled();
    }

    if ($own_permission) {
      $group->hasPermission($own_permission, $account)->willReturn($has_own_permission);
    }
    else {
      $group->hasPermission($own_permission, $account)->shouldNotBeCalled();
    }

    $result = $access_control_handler->relationAccess($group_content->reveal(), $operation, $account, TRUE);
    $this->assertEquals($expected(), $result);
  }

  /**
   * Data provider for testRelationAccess().
   *
   * @return array
   *   A list of testRelationAccess method arguments.
   */
  public function relationAccessProvider() {
    $cases = [];

    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['any some permission name', FALSE] as $any_permission) {
        $keys[1] = $any_permission ? 'anyperm' : 'noanyperm';

        foreach (['own some permission name', FALSE] as $own_permission) {
          $keys[2] = $own_permission ? 'ownperm' : 'noownperm';

          foreach ([TRUE, FALSE] as $has_own_permission) {
            $keys[3] = $has_own_permission ? 'hasown' : 'nohasown';

            foreach ([TRUE, FALSE] as $is_owner) {
              $keys[4] = $is_owner ? 'isowner' : 'noisowner';
              $case = $scenario;
              $case['definition'] = clone $scenario['definition'];

              // Default is neutral result if no permissions are defined.
              $case['expected'] = function() {
                return AccessResult::neutral();
              };

              $admin_permission = $case['definition']->getAdminPermission();
              if ($admin_permission || $any_permission || $own_permission) {
                $has_admin = $admin_permission && $case['has_admin_permission'];
                $has_any = $any_permission && $case['has_permission'];
                $has_own = $is_owner && $own_permission && $has_own_permission;

                $permissions_were_checked = $admin_permission || $any_permission || ($is_owner && $own_permission);
                $case['expected'] = function() use ($has_admin, $has_any, $has_own, $permissions_were_checked, $own_permission) {
                  $result = AccessResult::allowedIf($has_admin || $has_any || $has_own);

                  // Only add the permissions context if they were checked.
                  if ($permissions_were_checked) {
                    $result->addCacheContexts(['user.group_permissions']);
                  }

                  // Add the user context and the relation's cache metadata if
                  // we're dealing with an owner permission.
                  if ($own_permission) {
                    $result->addCacheContexts(['user']);

                    // Tags and max-age as defined in ::testRelationAccess().
                    $result->addCacheTags(['group_content:foo']);
                    $result->mergeCacheMaxAge(9999);
                  }
                  return $result;
                };
              }

              $case['has_own_permission'] = $has_own_permission;
              $case['any_permission'] = $any_permission;
              $case['own_permission'] = $own_permission;
              $case['is_owner'] = $is_owner;
              $cases[implode('-', $keys)] = $case;
            }
          }
        }
      }
    }

    return $cases;
  }

  /**
   * Tests the relation create access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param string|false $permission
   *   The relation create permission.
   *
   * @covers ::relationCreateAccess
   * @dataProvider relationCreateAccessProvider
   */
  public function testRelationCreateAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $permission) {
    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission('create', 'relation')->willReturn($permission);

    $relation_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $relation_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider->reveal());

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(Argument::any())->willReturn(FALSE);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $access_control_handler = new AccessControl($entity_type_manager->reveal(), $relation_manager->reveal());
    $access_control_handler->init($plugin_id, $definition);

    $group = $this->prophesize(GroupInterface::class);
    $account = $this->prophesize(AccountInterface::class)->reveal();

    if ($definition->getAdminPermission()) {
      $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
    }
    else {
      $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
    }

    if ($permission) {
      $group->hasPermission($permission, $account)->willReturn($has_permission);
    }
    else {
      $group->hasPermission($permission, $account)->shouldNotBeCalled();
    }

    $result = $access_control_handler->relationCreateAccess($group->reveal(), $account, TRUE);
    $this->assertEquals($expected(), $result);
  }

  /**
   * Data provider for testRelationCreateAccess.
   *
   * @return array
   *   A list of testRelationCreateAccess method arguments.
   */
  public function relationCreateAccessProvider() {
    $cases = [];

    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['some permission name', FALSE] as $permission) {
        $keys[1] = $permission ? 'perm' : 'noperm';
        $case = $scenario;
        $case['definition'] = clone $scenario['definition'];

        // Default is neutral result if no permissions are defined or entity
        // access control is turned off for the plugin.
        $case['expected'] = function() {
          return AccessResult::neutral();
        };

        $permission_exists = $case['definition']->getAdminPermission() || $permission;
        if ($permission_exists) {
          $has_admin = $case['definition']->getAdminPermission() && $case['has_admin_permission'];
          $has_regular = $permission && $case['has_permission'];
          $case['expected'] = function() use ($has_admin, $has_regular) {
            return AccessResult::allowedIf($has_admin || $has_regular)->addCacheContexts(['user.group_permissions']);
          };
        }

        $case['permission'] = $permission;
        $cases[implode('-', $keys)] = $case;
      }
    }

    return $cases;
  }

  /**
   * Tests the entity operation access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param bool $has_own_permission
   *   Whether the account has the required owner permission.
   * @param string|false $permission
   *   The operation permission.
   * @param string|false $own_permission
   *   The owner operation permission.
   * @param bool $is_grouped
   *   Whether the entity is grouped.
   * @param bool $is_ownable
   *   Whether the entity can be owned.
   * @param bool $is_owner
   *   Whether the account owns the entity.
   * @param bool $is_publishable
   *   Whether the entity can be (un)published.
   * @param bool $is_published
   *   Whether the entity is be published.
   * @param string $operation
   *   The operation to check access for.
   *
   * @covers ::entityAccess
   * @dataProvider entityAccessProvider
   */
  public function testEntityAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $has_own_permission, $permission, $own_permission, $is_grouped, $is_ownable, $is_owner, $is_publishable, $is_published, $operation) {
    $storage = $this->prophesize(GroupContentStorageInterface::class);
    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn($is_publishable);
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn($is_ownable);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('group_content')->willReturn($storage->reveal());
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission($operation, 'entity', 'any')->willReturn($permission);
    $permission_provider->getPermission($operation, 'entity', 'own')->willReturn($own_permission);

    $check_published = $operation === 'view' && $is_publishable;
    if ($check_published && !$is_published) {
      $permission_provider->getPermission("$operation unpublished", 'entity', 'any')->willReturn($permission);
      $permission_provider->getPermission("$operation unpublished", 'entity', 'own')->willReturn($own_permission);
    }

    $relation_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $relation_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider->reveal());

    $access_control_handler = new AccessControl($entity_type_manager->reveal(), $relation_manager->reveal());
    $access_control_handler->init($plugin_id, $definition);

    $account_id = rand(1, 100);
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($account_id);
    $account = $account->reveal();

    $entity = $this->prophesize(ContentEntityInterface::class);
    $entity->willImplement(EntityOwnerInterface::class);
    if ($is_publishable) {
      $entity->willImplement(EntityPublishedInterface::class);
      $entity->isPublished()->willReturn($is_published);
    }
    $entity->getOwnerId()->willReturn($is_owner ? $account_id : $account_id + 1);
    $entity->getCacheContexts()->willReturn([]);
    $entity->getCachetags()->willReturn(['some_entity:foo']);
    $entity->getCacheMaxAge()->willReturn(9999);
    $entity = $entity->reveal();

    if (!$is_grouped) {
      $storage->loadByEntity($entity, $plugin_id)->willReturn([]);
    }
    else {
      $group = $this->prophesize(GroupInterface::class);
      $group_content = $this->prophesize(GroupContentInterface::class);
      $group_content->getGroup()->willReturn($group->reveal());
      $group_content->getPluginId()->willReturn('foo:baz');
      $group_content = $group_content->reveal();

      $group_content_2 = $this->prophesize(GroupContentInterface::class);
      $group_content_2->getGroup()->willReturn($group->reveal());
      $group_content_2->getPluginId()->willReturn('cat:dog');
      $group_content_2 = $group_content_2->reveal();

      $storage->loadByEntity($entity, $plugin_id)->willReturn([1 => $group_content, 2 => $group_content_2]);

      if ($definition->getAdminPermission()) {
        $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
      }
      else {
        $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
      }

      $checked_and_found_admin = $definition->getAdminPermission() && $has_admin_permission;
      if ($permission && !$checked_and_found_admin) {
        $group->hasPermission($permission, $account)->willReturn($has_permission);
      }
      else {
        $group->hasPermission($permission, $account)->shouldNotBeCalled();
      }

      $checked_and_found_any = $permission && $has_permission;
      if ($own_permission && !$checked_and_found_admin && !$checked_and_found_any) {
        $group->hasPermission($own_permission, $account)->willReturn($has_own_permission);
      }
      else {
        $group->hasPermission($own_permission, $account)->shouldNotBeCalled();
      }
    }

    $result = $access_control_handler->entityAccess($entity, $operation, $account, TRUE);
    $this->assertEqualsCanonicalizing($expected(), $result);
  }

  /**
   * Data provider for testEntityAccess().
   *
   * @return array
   *   A list of testEntityAccess method arguments.
   */
  public function entityAccessProvider() {
    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach (['any some permission name', FALSE] as $any_permission) {
        $keys[1] = $any_permission ? 'anyperm' : 'noanyperm';

        foreach (['own some permission name', FALSE] as $own_permission) {
          $keys[2] = $own_permission ? 'ownperm' : 'noownperm';

          foreach ([TRUE, FALSE] as $has_own_permission) {
            $keys[3] = $has_own_permission ? 'hasown' : 'nohasown';

            foreach ([TRUE, FALSE] as $is_grouped) {
              $keys[4] = $is_grouped ? 'grouped' : 'nogrouped';

              foreach ([TRUE, FALSE] as $is_ownable) {
                $keys[5] = $is_ownable ? 'ownable' : 'noownable';

                foreach ([TRUE, FALSE] as $is_owner) {
                  $keys[6] = $is_owner ? 'isowner' : 'noisowner';

                  foreach ([TRUE, FALSE] as $is_publishable) {
                    $keys[7] = $is_publishable ? 'pub' : 'nopub';

                    foreach ([TRUE, FALSE] as $is_published) {
                      $keys[8] = $is_published ? 'ispub' : 'noispub';

                      foreach (['view', $this->randomMachineName()] as $operation) {
                        $keys[9] = $operation === 'view' ? 'opview' : 'noopview';

                        $case = $scenario;
                        $case['definition'] = clone $scenario['definition'];

                        $is_supported = $any_permission !== FALSE;
                        if (!$is_supported && $is_ownable) {
                          $is_supported = $own_permission !== FALSE;
                        }

                        if (!$is_supported) {
                          $case['expected'] = function() {
                            return AccessResult::neutral();
                          };
                        }
                        else {
                          $check_published = $operation === 'view' && $is_publishable;

                          // Default varies on whether the entity is grouped.
                          $case['expected'] = function() use ($is_grouped, $own_permission, $check_published) {
                            $result = AccessResult::forbiddenIf($is_grouped);
                            if ($is_grouped) {
                              $result->addCacheContexts(['user.group_permissions']);

                              if ($own_permission) {
                                $result->addCacheContexts(['user']);
                              }

                              if ($own_permission || $check_published) {
                                $result->addCacheTags(['some_entity:foo']);
                                $result->mergeCacheMaxAge(9999);
                              }
                            }
                            return $result;
                          };

                          $admin_permission = $case['definition']->getAdminPermission();
                          if ($is_grouped && ($admin_permission || $any_permission || $own_permission)) {
                            $admin_access = $admin_permission && $case['has_admin_permission'];

                            if (!$check_published || $is_published) {
                              $any_access = $any_permission && $case['has_permission'];
                              $own_access = $is_ownable && $is_owner && $own_permission && $has_own_permission;
                            }
                            elseif ($check_published && !$is_published) {
                              $any_access = $any_permission && $case['has_permission'];
                              $own_access = $is_ownable && $is_owner && $own_permission && $has_own_permission;
                            }
                            else {
                              $any_access = FALSE;
                              $own_access = FALSE;
                            }

                            $case['expected'] = function() use ($admin_access, $any_access, $own_access, $own_permission, $check_published) {
                              $result = AccessResult::allowedIf($admin_access || $any_access || $own_access);

                              if (!$result->isAllowed()) {
                                $result = AccessResult::forbidden();
                              }

                              if ($own_permission) {
                                $result->addCacheContexts(['user']);
                              }

                              if ($own_permission || $check_published) {
                                $result->addCacheTags(['some_entity:foo']);
                                $result->mergeCacheMaxAge(9999);
                              }

                              return $result->addCacheContexts(['user.group_permissions']);
                            };
                          }
                        }

                        $case['has_own_permission'] = $has_own_permission;
                        $case['any_permission'] = $any_permission;
                        $case['own_permission'] = $own_permission;
                        $case['is_grouped'] = $is_grouped;
                        $case['is_ownable'] = $is_ownable;
                        $case['is_owner'] = $is_owner;
                        $case['is_publishable'] = $is_publishable;
                        $case['is_published'] = $is_published;
                        $case['operation'] = $operation;
                        yield implode('-', $keys) => $case;
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Tests the entity create access.
   *
   * @param \Closure $expected
   *   A closure returning the expected access result.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $has_admin_permission
   *   Whether the account has the admin permission.
   * @param bool $has_permission
   *   Whether the account has the required permission.
   * @param string|false $permission
   *   The entity create permission.
   *
   * @covers ::entityCreateAccess
   * @dataProvider entityCreateAccessProvider
   */
  public function testEntityCreateAccess(\Closure $expected, $plugin_id, GroupRelationTypeInterface $definition, $has_admin_permission, $has_permission, $permission) {
    $permission_provider = $this->prophesize(PermissionProviderInterface::class);
    $permission_provider->getAdminPermission()->willReturn($definition->getAdminPermission());
    $permission_provider->getPermission('create', 'entity')->willReturn($permission);

    $relation_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $relation_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider->reveal());

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(Argument::any())->willReturn(FALSE);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $access_control_handler = new AccessControl($entity_type_manager->reveal(), $relation_manager->reveal());
    $access_control_handler->init($plugin_id, $definition);

    $group = $this->prophesize(GroupInterface::class);
    $account = $this->prophesize(AccountInterface::class)->reveal();

    if ($definition->getAdminPermission()) {
      $group->hasPermission($definition->getAdminPermission(), $account)->willReturn($has_admin_permission);
    }
    else {
      $group->hasPermission($definition->getAdminPermission(), $account)->shouldNotBeCalled();
    }

    if ($permission) {
      $group->hasPermission($permission, $account)->willReturn($has_permission);
    }
    else {
      $group->hasPermission($permission, $account)->shouldNotBeCalled();
    }

    $result = $access_control_handler->entityCreateAccess($group->reveal(), $account, TRUE);
    $this->assertEquals($expected(), $result);
  }

  /**
   * Data provider for entityCreateAccessProvider.
   *
   * @return array
   *   A list of entityCreateAccessProvider method arguments.
   */
  public function entityCreateAccessProvider() {
    $cases = [];

    foreach ($this->getAccessControlHandlerScenarios() as $key => $scenario) {
      $keys[0] = $key;

      foreach ([TRUE, FALSE] as $entity_access) {
        $keys[1] = $entity_access ? 'entacc' : 'noentacc';

        foreach (['some permission name', FALSE] as $permission) {
          $keys[2] = $permission ? 'perm' : 'noperm';
          $case = $scenario;
          $case['definition'] = clone $scenario['definition'];

          // Default is neutral result if no permissions are defined or entity
          // access control is turned off for the plugin.
          $case['expected'] = function() {
            return AccessResult::neutral();
          };

          $permission_exists = $case['definition']->getAdminPermission() || $permission;
          if ($permission_exists && $entity_access) {
            $has_admin = $case['definition']->getAdminPermission() && $case['has_admin_permission'];
            $has_regular = $permission && $case['has_permission'];
            $case['expected'] = function() use ($has_admin, $has_regular) {
              return AccessResult::allowedIf($has_admin || $has_regular)->addCacheContexts(['user.group_permissions']);
            };
          }

          $case['definition']->set('entity_access', $entity_access);
          $case['permission'] = $permission;
          $cases[implode('-', $keys)] = $case;
        }
      }
    }

    return $cases;
  }

  /**
   * All possible scenarios for an access control handler.
   *
   * @return array
   *   A set of test cases to be used in data providers.
   */
  protected function getAccessControlHandlerScenarios() {
    $scenarios = [];

    foreach (['administer foo', FALSE] as $admin_permission) {
      $keys[0] = $admin_permission ? 'admin' : 'noadmin';

      foreach ([TRUE, FALSE] as $has_admin_permission) {
        $keys[1] = $has_admin_permission ? 'hasadmin' : 'nohasadmin';

        foreach ([TRUE, FALSE] as $has_permission) {
          $keys[2] = $has_permission ? 'hasperm' : 'nohasperm';

          $scenarios[implode('-', $keys)] = [
            'expected' => NULL,
            // We use a derivative ID to prove these work.
            'plugin_id' => 'foo:baz',
            'definition' => new GroupRelationType([
              'id' => 'foo',
              'label' => 'Foo',
              'entity_type_id' => 'bar',
              'admin_permission' => $admin_permission,
            ]),
            'has_admin_permission' => $has_admin_permission,
            'has_permission' => $has_permission,
          ];
        }
      }
    }

    return $scenarios;
  }

}
