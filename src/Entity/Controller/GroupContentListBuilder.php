<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for group content entities.
 *
 * @ingroup group
 */
class GroupContentListBuilder extends EntityListBuilder {

  /**
   * The group to show the content for.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupContentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RedirectDestinationInterface $redirect_destination, RouteMatchInterface $route_match, EntityTypeInterface $entity_type, AccountInterface $current_user) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->entityTypeManager = $entity_type_manager;
    $this->redirectDestination = $redirect_destination;
    $this->currentUser = $current_user;
    // There should always be a group on the route for group content lists.
    $this->group = $route_match->getParameters()->get('group');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('redirect.destination'),
      $container->get('current_route_match'),
      $entity_type,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->sort($this->entityType->getKey('id'));

    // Only show group content for the group on the route.
    $query->condition('gid', $this->group->id());

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Content label'),
      'entity_type' => $this->t('Entity type'),
      'plugin' => $this->t('Plugin used'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $row['id'] = $entity->id();

    // EntityListBuilder sets the table rows using the #rows property, so we
    // need to add links as render arrays using the 'data' key.
    $row['label']['data'] = $entity->toLink()->toRenderable();
    $entity_type_id = $entity->getContentPlugin()->getEntityTypeId();
    $row['entity_type'] = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
    $row['plugin'] = $entity->getContentPlugin()->getLabel();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no entities related to this group yet.');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    // Improve the edit and delete operation labels.
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Edit relation');
    }
    if (isset($operations['delete'])) {
      $operations['delete']['title'] = $this->t('Delete relation');
    }

    // Slap on redirect destinations for the administrative operations.
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    // Add operations to view, update and delete the related entity.
    $plugin = $entity->getContentPlugin();
    if ($plugin->checkEntityAccess($entity, 'view', $this->currentUser)) {
      $operations['view_entity'] = [
        'title' => $this->t('View entity'),
        'weight' => 101,
        'url' => $entity->toUrl('entity-view'),
      ];
    }
    if ($plugin->checkEntityAccess($entity, 'update', $this->currentUser)) {
      $operations['update_entity'] = [
        'title' => $this->t('Edit related entity'),
        'weight' => 102,
        'url' => $entity->toUrl('entity-edit-form'),
      ];
    }
    if ($plugin->checkEntityAccess($entity, 'delete', $this->currentUser)) {
      $operations['delete_entity'] = [
        'title' => $this->t('Delete related entity'),
        'weight' => 103,
        'url' => $entity->toUrl('entity-delete-form'),
      ];
    }

    return $operations;
  }

}
