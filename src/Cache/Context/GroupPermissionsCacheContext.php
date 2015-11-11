<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupPermissionsCacheContext.
 *
 * @todo research this, enable the service and use it in GroupAccessResult.
 */

namespace Drupal\group\Cache\Context;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\PermissionsHashGeneratorInterface;

/**
 * Defines the GroupPermissionsCacheContext service, for "per permission" caching.
 *
 * Cache context ID: 'group.permissions'.
 */
class GroupPermissionsCacheContext implements CacheContextInterface {


  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The group object.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The permissions hash generator.
   *
   * @var \Drupal\Core\Session\PermissionsHashGeneratorInterface
   */
  protected $permissionsHashGenerator;


  /**
   * Constructs a new GroupPermissionsCacheContext class.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group the permission was checked on.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Session\PermissionsHashGeneratorInterface $permissions_hash_generator
   *   The permissions hash generator.
   */
  public function __construct(GroupInterface $group, AccountInterface $user, PermissionsHashGeneratorInterface $permissions_hash_generator) {
    $this->group = $group;
    $this->user = $user;
    $this->permissionsHashGenerator = $permissions_hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Group's permissions");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->permissionsHashGenerator->generate($this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // The permissions hash changes when:
    // - a user is updated to have different roles;
    $tags = ['user:' . $this->user->id()];
    // - a role is updated to have different permissions.
    foreach ($this->user->getRoles() as $rid) {
      $tags[] = "config:user.role.$rid";
    }

    return $cacheable_metadata->setCacheTags($tags);
  }

}
