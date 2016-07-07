<?php

/**
 * @file
 * Contains \Drupal\gnode\Controller\GroupNodeWizardController.
 */

namespace Drupal\gnode\Controller;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeTypeInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for 'group_node' GroupContent routes.
 */
class GroupNodeWizardController extends GroupContentController {

  /**
   * The private store for temporary group nodes.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a new GroupNodeWizardController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    parent::__construct($entity_type_manager, $entity_form_builder, $renderer);
    $this->privateTempStore = $temp_store_factory->get('gnode_add_temp');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
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
   *   The form array for either step 1 or 2 of the group node creation wizard.
   */
  public function addFormWizard(GroupInterface $group, NodeTypeInterface $node_type) {
    $plugin_id = 'group_node:' . $node_type->id();
    $storage_id = $plugin_id . ':' . $group->id();

    // If we are on step one, we need to build a node form.
    if ($this->privateTempStore->get("$storage_id:step") !== 2) {
      $this->privateTempStore->set("$storage_id:step", 1);

      // Only create a new node if we have nothing stored.
      if (!$entity = $this->privateTempStore->get("$storage_id:node")) {
        $entity = Node::create(['type' => $node_type->id()]);
      }
    }
    // If we are on step two, we need to build a group content form.
    else {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
      $entity = GroupContent::create([
        'type' => $plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
      ]);
    }

    // Return the form with the group and storage ID added to the form state.
    $extra = ['group' => $group, 'storage_id' => $storage_id];
    return $this->entityFormBuilder()->getForm($entity, 'gnode-form', $extra);
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
  public function addFormWizardTitle(GroupInterface $group, NodeTypeInterface $node_type) {
    return $this->t('Create %type in %label', ['%type' => $node_type->label(), '%label' => $group->label()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function addPageBundles(GroupInterface $group) {
    $plugins = $group->getGroupType()->getInstalledContentPlugins();

    $bundle_names = [];
    foreach ($plugins as $plugin_id => $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      list($base_plugin_id, $derivative_id) = explode(':', $plugin->getPluginId() . ':');

      // Only select the group_node plugins.
      if ($base_plugin_id == 'group_node') {
        $bundle_names[$plugin_id] = $plugin->getContentTypeConfigId();
      }
    }

    return $bundle_names;
  }

  /**
   * {@inheritdoc}
   */
  protected function addPageBundleMessage(GroupInterface $group) {
    // We do not set the 'add_bundle_message' variable because we deny access to
    // the add page if no bundle is available.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function addPageFormRoute(GroupInterface $group) {
    return 'entity.group_content.group_node_add_form';
  }

}
