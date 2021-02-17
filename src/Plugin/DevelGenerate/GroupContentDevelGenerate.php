<?php

namespace Drupal\group\Plugin\DevelGenerate;

use Drupal\group\Entity\GroupContentType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a GroupDevelGenerate plugin.
 * @todo support subgroups i.e. Content goes in a subgroup only if the owner is
 * a member of the parent.
 *
 * @DevelGenerate(
 *   id = "group_content",
 *   label = @Translation("group content"),
 *   description = @Translation("Put existing content into groups"),
 *   url = "group_content",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "kill" = FALSE
 *   }
 * )
 */
class GroupContentDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The url generator
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The Group Membership Loader service
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager service
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The Group Membership Loader service
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $url_generator, $membership_loader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $url_generator;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('url_generator'),
      $container->get('group.membership_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $types_count = $this->countContentOfTypes();
    if ($types_count) {
      $sentences[] = $this->t('This generator takes existing ContentEntities and puts them in existing groups.');
      $sentences[] = $this->t("It creates a new 'membership' entity for each item in each group.");
      $sentences[] = $this->t("If content has an owner, it will only be placed in groups of which the owner is a member.");
      $form['intro'] = [
        '#markup' => implode(' ', $sentences),
        '#weight' => -1
      ];
    }
    else {
      $create_url = $this->urlGenerator->generateFromRoute('entity.group_type.add_form');
      $this->setMessage($this->t(
        'You do not have any group content types because you do not have any group types. <a href=":create-type">Go create a new group type</a>',
        [':create-type' => $create_url]
      ), 'error', FALSE);
      return;
    }

    $options = [];

    $form['group_content_types'] = [
      '#title' => $this->t('Group content types'),
      '#description' => $this->t('Check none to include all'),
      '#type' => 'checkboxes',
      '#options' => [],
      '#weight' => 1
    ];
    // Get the number of existing items for each plugin, and disable the
    // checkboxes with none
    foreach (GroupContentType::loadMultiple() as $id => $groupContentType) {
      $form['group_content_types']['#options'][$id] = $groupContentType->label();
      list($plugin, $entity_type_id, $entity_type, $bundle) = $this->parsePlugin($groupContentType);
      $quant = count($types_count[$id]);
      if ($bundle) {
        $bundle_label = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id)[$bundle]['label'];
        $summary[$bundle] = $bundle_label .':'.$quant;
      }
      else {
        $summary[$entity_type_id] = $entity_type->getLabel() .':'.$quant;
      }
      //$form['group_content_types']['#options'][$id] .= ' ('.count($content_ids).')';
      $form['group_content_types'][$id]['#disabled'] = empty($types_count[$id]);
    }

    $form['group_content_types']['#description'] = implode('; ', $summary);

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Before generating, first delete all group content of these types.'),
      '#default_value' => $this->getSetting('kill'),
      '#weight' => 2
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  private function countContentOfTypes() {
    foreach (GroupContentType::loadMultiple() as $id => $groupContentType) {
      // Check if any of this content actually exists.
      list($plugin, $entity_type_id, $entity_type, $bundle) = $this->parsePlugin($groupContentType);
      $query = \Drupal::entityQuery($entity_type_id);
      if ($bundle) {
        $query->condition($entity_type->getKey('bundle'), $bundle);
      }
      $content_ids[$id] = $query->execute();
    }
    return $content_ids;
  }

  private function parsePlugin($groupContentType) {
    $plugin = $groupContentType->getContentPlugin();
    $entity_type_id = $plugin->getEntitytypeId();
    return [
      $plugin,
      $entity_type_id,
      \Drupal::entityTypeManager()->getDefinition($entity_type_id),
      $plugin->getEntityBundle()
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $groupContentTypes = array_filter($values['group_content_types']) ?: array_keys($this->countContentOfTypes());
    if (count($groupContentTypes) == 1) {
      if ($values['kill']) {

      }
      $this->addGroupContent($values + $values + ['content_type' => reset($values['group_content_types'])]);
    }
    else {
      // Start the batch.
      $batch = [
        'title' => $this->t('Generating group content'),
        'finished' => 'devel_generate_batch_finished',
        'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
      ];

      // Add the kill operation.
      if ($values['kill']) {
        $batch['operations'][] = [
          'devel_generate_operation',
          [$this, 'batchGroupsKill', $values],
        ];
      }
      $batch['operations'][] = [
        'devel_generate_operation',
        [$this, 'batchPreGroup', $values],
      ];

      $groupContentTypes = array_filter($values['group_content_types']) ?: array_keys($this->countContentOfTypes());

      // Add the operations to create the groups.
      foreach (array_filter($groupContentTypes) as $type) {
        $batch['operations'][] = [
          'devel_generate_operation',
          [$this, 'batchAddGroupContent', $values + ['content_type' => $type]],
        ];
      }

      // Should we make a batchPostGroup?
      batch_set($batch);
    }
  }

  /**
   * The method responsible for creating groups.
   *
   * @param array $values
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchPreGroup($values, &$context) {
    $context['results'] = $values;
    $context['results']['num'] = 0;
  }

  /**
   * Delete existing groupContent of the given types.
   *
   * @param array $values
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchGroupContentKill($values, &$context) {
    foreach (GroupContentType::loadMultiple($vars['group_content_types']) as $contentType) {
      $content_items = GroupContent::loadByContentPluginId($contentType->id());
    }
    foreach ($vars['group_content_types'] as $contentTypeId) {
      $vars = ['group_content_types' => [$contentTypeId]];
      $this->groupContentKill($vars);
    }
  }

  /**
   * Delete existing groupContent of the given type.
   *
   * @param array $values
   *   The input values from the settings form or batch
   */
  public function GroupContentKill($values) {
    $contentTypeId = reset($values['group_content_types']);
    foreach (GroupContent::loadByContentPluginId($contentTypeId) as $item) {
      $item->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    // Drush doesn't provide the option to choose types but assumes all.
    $values['group_content_types'] = array_keys(GroupContentType::loadMultiple());
    $values['kill'] = drush_get_option('kill');
    return $values;
  }

  public function batchAddGroupContent($values, &$context) {
    $this->addGroupContent($values);
    $context['results']['num']++;
  }

  /**
   * Put all content of one type into all groups that support it.
   *
   * @param array $values
   *   The input values from the settings form with some additional data needed
   *   for the generation.
   * @param string $groupContentTypeId
   *   the id of the groupContentType e.g. clubs-events
   */
  public function addGroupContent($values) {
    // The GroupContentType plugin tells us which groupType(s) we need.
    $groupContentType = GroupContentType::load($values['content_type']);
    $entity_type_id = $groupContentType->getContentPlugin()->getEntityTypeId();
    $group_type_id = $groupContentType->getGroupTypeId();
    // Load all the groups of this type.
    $groups = $this->entityTypeManager->getStorage('group')
      ->loadByProperties(['type' => $group_type_id]);
    if (empty($groups)) {
      \Drupal::logger('groupcontent')->warning(
        'No %group_type_id groups to which to addGroupContent %type',
        ['%group_type_id' => $group_type_id, '%type' => $values['content_type']]
      );
      return;
    }
    // Load all the entities of this type
    $content = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple();
    \Drupal::logger('groupcontent')->notice(
      "Adding %count groupContent using plugin: %plugin",
      ['%count' => count($content), '%plugin' => $values['content_type']]
    );
    $groups = array_values($groups);
    $plugin_id = $groupContentType->getContentPlugin()->getPluginId();

    if($plugin_id == 'group_membership') {
      // Get the roles for this group-type
      $roles = \Drupal::entityQuery('group_role')
        ->condition('group_type', $group_type_id, '=')
        ->condition('internal', 0, '=')
        ->execute();
    }

    // Loop around the groups adding one entity at a time until all entities are
    // added
    $i = 0;
    while ($entity = array_pop($content)) {
      $group = $groups[$i % count($groups)];
      $group->addContent(
        $entity,
        $plugin_id,
        ['uid' => $entity instanceof \Drupal\user\EntityOwnerInterface ? $entity->getOwnerId() : 1]
      );
      // If we are adding users as members then add one random role to the newly
      // created membership as well;
      if ($roles) {
        $this->membershipLoader
          ->load($group, $entity)
          ->getGroupContent()
          ->set('group_roles', (array)$roles[$i % count($roles)])
          ->save();
      }
      $i++;
    }


  }

}
