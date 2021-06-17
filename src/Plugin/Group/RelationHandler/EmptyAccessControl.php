<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a default access control handler.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyAccessControl implements AccessControlInterface {

  /**
   * The default plugin handler.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface
   */
  protected $default;

  /**
   * Constructs a new EmptyAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $default
   *   The default permission provider.
   */
  public function __construct(PermissionProviderInterface $default) {
    $this->default = $default;
  }

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, array $definition) {
    // Intentionally left blank.
  }

  /**
   * {@inheritdoc}
   */
  public function relationAccess(GroupContentInterface $group_content, $operation, AccountInterface $account, $return_as_object = FALSE) {
    return $this->default->relationAccess($group_content, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function relationCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    return $this->default->relationCreateAccess($group, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    return $this->default->entityAccess($entity, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    return $this->default->entityCreateAccess($group, $account, $return_as_object);
  }

}
