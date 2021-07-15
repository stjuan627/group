<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the GroupContent entity.
 *
 * @see \Drupal\group\Entity\GroupContent.
 */
class GroupContentAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationManagerInterface
   */
  protected $groupRelationManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static($entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->groupRelationManager = $container->get('plugin.manager.group_relation');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $access_control = $this->groupRelationManager->getAccessControlHandler($entity->getRelationPlugin()->getPluginId());
    return $access_control->relationAccess($entity, $operation, $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
    $group_content_type = $this->entityTypeManager->getStorage('group_content_type')->load($entity_bundle);
    $access_control = $this->groupRelationManager->getAccessControlHandler($group_content_type->getRelationPluginId());
    return $access_control->relationCreateAccess($context['group'], $account, TRUE);
  }

}
