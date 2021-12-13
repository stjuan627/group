<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for GroupContent routes.
 */
class GroupContentController extends ControllerBase {

  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupContentController.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, GroupRelationTypeManagerInterface $groupRelationTypeManager, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->groupRelationTypeManager = $groupRelationTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('group_relation_type.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * Provides the group content creation overview page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param bool $create_mode
   *   (optional) Whether the target entity still needs to be created. Defaults
   *   to FALSE, meaning the target entity is assumed to exist already.
   * @param string|null $base_plugin_id
   *   (optional) A base plugin ID to filter the bundles on. This can be useful
   *   when you want to show the add page for just a single plugin that has
   *   derivatives for the target entity type's bundles.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The group content creation overview page or a redirect to the form for
   *   adding group content if there is only one group content type.
   */
  public function addPage(GroupInterface $group, $create_mode = FALSE, $base_plugin_id = NULL) {
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];
    $form_route = $this->addPageFormRoute($group, $create_mode);
    $group_content_types = $this->addPageBundles($group, $create_mode, $base_plugin_id);

    // Set the add bundle message if available.
    $add_bundle_message = $this->addPageBundleMessage($group, $create_mode);
    if ($add_bundle_message !== FALSE) {
      $build['#add_bundle_message'] = $add_bundle_message;
    }

    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_content');
    foreach ($group_content_types as $group_content_type_id => $group_content_type) {
      $access = $access_control_handler->createAccess($group_content_type_id, NULL, ['group' => $group], TRUE);
      if (!$access->isAllowed()) {
        unset($group_content_types[$group_content_type_id]);
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Redirect if there's only one bundle available.
    if (count($group_content_types) == 1) {
      $route_params = [
        'group' => $group->id(),
        'plugin_id' => reset($group_content_types)->getPluginId(),
      ];
      $url = Url::fromRoute($form_route, $route_params, ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    // Set the info for all of the remaining bundles.
    foreach ($group_content_types as $group_content_type_id => $group_content_type) {
      $ui_text_provider = $this->groupRelationTypeManager->getUiTextProvider($group_content_type->getPluginId());

      $label = $ui_text_provider->getAddPageLabel($create_mode);
      $build['#bundles'][$group_content_type_id] = [
        'label' => $label,
        'description' => $ui_text_provider->getAddPageDescription($create_mode),
        'add_link' => Link::createFromRoute($label, $form_route, [
          'group' => $group->id(),
          'plugin_id' => $group_content_type->getPluginId(),
        ]),
      ];
    }

    // Add the list cache tags for the GroupContentType entity type.
    $bundle_entity_type = $this->entityTypeManager->getDefinition('group_content_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    return $build;
  }

  /**
   * Retrieves a list of available group content types for the add page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   * @param string|null $base_plugin_id
   *   (optional) A base plugin ID to filter the bundles on. This can be useful
   *   when you want to show the add page for just a single plugin that has
   *   derivatives for the target entity type's bundles.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content types, keyed by their ID.
   *
   * @see ::addPage()
   */
  protected function addPageBundles(GroupInterface $group, $create_mode, $base_plugin_id) {
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);

    $group_content_types = $storage->loadByGroupType($group->getGroupType());
    foreach ($group_content_types as $group_content_type_id => $group_content_type) {
      $relation = $group_content_type->getPlugin();

      // Check the base plugin ID if a plugin filter was specified.
      if ($base_plugin_id && $relation->getBaseId() === $base_plugin_id) {
        unset($group_content_types[$group_content_type_id]);
      }
      // Skip the bundle if we are listing bundles that allow you to create an
      // entity in the group and the bundle's plugin does not support that.
      elseif ($create_mode && !$relation->getRelationType()->definesEntityAccess()) {
        unset($group_content_types[$group_content_type_id]);
      }
    }

    return $group_content_types;
  }

  /**
   * Returns the 'add_bundle_message' string for the add page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return string|false
   *   The translated string or FALSE if no string should be set.
   *
   * @see ::addPage()
   */
  protected function addPageBundleMessage(GroupInterface $group, $create_mode) {
    // We do not set the 'add_bundle_message' variable because we deny access to
    // the page if no bundle is available. This method exists so that modules
    // that extend this controller may specify a message should they decide to
    // allow access to their page even if it has no bundles.
    return FALSE;
  }

  /**
   * Returns the route name of the form the add page should link to.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return string
   *   The route name.
   *
   * @see ::addPage()
   */
  protected function addPageFormRoute(GroupInterface $group, $create_mode) {
    return $create_mode
      ? 'entity.group_content.create_form'
      : 'entity.group_content.add_form';
  }

  /**
   * Provides the group content submission form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return array
   *   A group submission form.
   */
  public function addForm(GroupInterface $group, $plugin_id) {
    $storage = $this->entityTypeManager()->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);

    $values = [
      'type' => $storage->getGroupContentTypeId($group->bundle(), $plugin_id),
      'gid' => $group->id(),
    ];
    $group_content = $this->entityTypeManager()->getStorage('group_content')->create($values);

    return $this->entityFormBuilder->getForm($group_content, 'add');
  }

  /**
   * The _title_callback for the entity.group_content.add_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(GroupInterface $group, $plugin_id) {
    return $this->groupRelationTypeManager->getUiTextProvider($plugin_id)->getAddFormTitle(FALSE);
  }

  /**
   * The _title_callback for the entity.group_content.edit_form route.
   *
   * Overrides the Drupal\Core\Entity\Controller\EntityController::editTitle().
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity edit page, if an entity was found.
   */
  public function editFormTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $route_match->getParameter('group_content')) {
      return $this->t('Edit %label', ['%label' => $entity->label()]);
    }
  }

  /**
   * The _title_callback for the entity.group_content.collection route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   *
   * @return string
   *   The page title.
   */
  public function collectionTitle(GroupInterface $group) {
    return $this->t('All entity relations for @group', ['@group' => $group->label()]);
  }

  /**
   * Provides the group content creation form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group content to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return array
   *   A group content creation form.
   */
  public function createForm(GroupInterface $group, $plugin_id) {
    $group_relation = $group->getGroupType()->getPlugin($plugin_id);
    $group_relation_type = $group_relation->getRelationType();

    $wizard_id = 'group_entity';
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $plugin_id . ':' . $group->id();

    // See if the plugin uses a wizard for creating new entities. Also pass this
    // info to the form state.
    $config = $group_relation->getConfiguration();
    $extra['group_wizard'] = $config['use_creation_wizard'];
    $extra['group_wizard_id'] = $wizard_id;

    // Pass the group, plugin ID and store ID to the form state as well.
    $extra['group'] = $group;
    $extra['group_relation'] = $plugin_id;
    $extra['store_id'] = $store_id;

    // See if we are on the second step of the form.
    $step2 = $extra['group_wizard'] && $store->get("$store_id:step") === 2;

    // Content entity form, potentially as wizard step 1.
    if (!$step2) {
      // Figure out what entity type the plugin is serving.
      $entity_type_id = $group_relation_type->getEntityTypeId();
      $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
      $storage = $this->entityTypeManager()->getStorage($entity_type_id);

      // Only create a new entity if we have nothing stored.
      if (!$entity = $store->get("$store_id:entity")) {
        $values = [];
        if (($key = $entity_type->getKey('bundle')) && ($bundle = $group_relation_type->getEntityBundle())) {
          $values[$key] = $bundle;
        }
        $entity = $storage->create($values);
      }

      // Use the add form handler if available.
      $operation = 'default';
      if ($entity_type->getFormClass('add')) {
        $operation = 'add';
      }
    }
    // Wizard step 2: Group content form.
    else {
      $gct_storage = $this->entityTypeManager()->getStorage('group_content_type');
      assert($gct_storage instanceof GroupContentTypeStorageInterface);

      // Create an empty group content entity.
      $values = [
        'type' => $gct_storage->getGroupContentTypeId($group->bundle(), $plugin_id),
        'gid' => $group->id(),
      ];
      $entity = $this->entityTypeManager()->getStorage('group_content')->create($values);

      // Group content entities have an add form handler.
      $operation = 'add';
    }

    // Return the entity form with the configuration gathered above.
    return $this->entityFormBuilder()->getForm($entity, $operation, $extra);
  }

  /**
   * The _title_callback for the entity.group_content.create_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create the group content in.
   * @param string $plugin_id
   *   The group relation to create content with.
   *
   * @return string
   *   The page title.
   */
  public function createFormTitle(GroupInterface $group, $plugin_id) {
    return $this->groupRelationTypeManager->getUiTextProvider($plugin_id)->getAddFormTitle(TRUE);
  }

}
