<?php

/**
 * @file
 * Contains \Drupal\gnode\Controller\GroupNodeController.
 */

namespace Drupal\gnode\Controller;

use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeTypeInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group node route controllers.
 *
 * This only controls the routes that are not supported out of the box by the
 * plugin base \Drupal\group\Plugin\GroupContentEnablerBase.
 */
class GroupNodeController extends ControllerBase {

  /**
   * The private store for temporary group nodes.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a new GroupNodeController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->privateTempStore = $temp_store_factory->get('gnode_add_temp');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * Provides the form for creating a node in a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a node in.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type to create.
   *
   * @return array
   *   todo.
   */
  public function add(GroupInterface $group, NodeTypeInterface $node_type) {
    // If we are on step one, we need to build a node form.
    if ($this->privateTempStore->get('step') !== 2) {
      $this->privateTempStore->set('step', 1);

      // Only create a new node if we have nothing stored.
      if (!$entity = $this->privateTempStore->get('node')) {
        $entity = Node::create(['type' => $node_type->id()]);
      }
    }
    // If we are on step two, we need to build a group content form.
    else {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin('group_node:' . $node_type->id());
      $entity = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
      ]);
    }

    return $this->entityFormBuilder()->getForm($entity, 'gnode-form');
  }

  /**
   * The _title_callback for the add node form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create a node in.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type to create.
   *
   * @return string
   *   The page title.
   */
  public function addTitle(GroupInterface $group, NodeTypeInterface $node_type) {
    return $this->t('Create %type in %label', ['%type' => $node_type->label(), '%label' => $group->label()]);
  }

}
