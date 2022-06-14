<?php

namespace Drupal\group\Plugin\DevelGenerate;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\user\EntityOwnerInterface;
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
        '#weight' => -1,
      ];
    }
    else {
      $create_url = $this->urlGenerator->generateFromRoute('entity.group_type.add_form');
      $this->setMessage($this->t(
        'You do not have any group content types because you do not have any group types. <a href=":create-type">Go create a new group type</a>',
        [':create-type' => $create_url]
      ), 'error');
      return;
    }

    $form['group_content_types'] = [
      '#title' => $this->t('Group content types'),
      '#description' => $this->t('Check none to include all'),
      '#type' => 'checkboxes',
      '#options' => [],
      '#weight' => 1,
    ];

    // Get the number of existing items for each plugin, and disable the
    // checkboxes with none.
    $summary = [];
    foreach (GroupContentType::loadMultiple() as $id => $groupContentType) {
      $form['group_content_types']['#options'][$id] = $groupContentType->label();
      list($plugin, $entity_type_id, $entity_type, $bundle) = $this->parsePlugin($groupContentType);
      $quant = count($types_count[$id]);
      if ($bundle) {
        $bundle_label = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id)[$bundle]['label'];
        $summary[$bundle] = $bundle_label . ':' . $quant;
      }
      else {
        $summary[$entity_type_id] = $entity_type->getLabel() . ':' . $quant;
      }
      $form['group_content_types'][$id]['#disabled'] = empty($types_count[$id]);
    }

    $form['group_content_types']['#description'] = implode('; ', $summary);

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Before generating, first delete all group content of these types (does not affect actual content, only relations to groups).'),
      '#default_value' => $this->getSetting('kill'),
      '#weight' => 2,
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * Helper function to count the items of each type of group content.
   *
   * @return array
   *   Array keyed by group content IDs. Values are arrays of content IDs.
   *
   * @todo Either rename this method or make it do what its name promises.
   */
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

  /**
   * Helper function to retrieve key information about group content plugins.
   *
   * @param \Drupal\group\Entity\GroupContentTypeInterface $groupContentType
   *   The group content type to get the information for.
   *
   * @return array
   *   Array of information.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function parsePlugin(GroupContentTypeInterface $groupContentType) {
    $plugin = $groupContentType->getContentPlugin();
    $entity_type_id = $plugin->getEntitytypeId();
    return [
      $plugin,
      $entity_type_id,
      \Drupal::entityTypeManager()->getDefinition($entity_type_id),
      $plugin->getEntityBundle(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    // Always do this in a batch. The number of operations can quickly baloon
    // with a lot of content and a lot of groups, even with a single type of
    // relation.
    $batch = [
      'title' => $this->t('Generating group content'),
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];

    // Add the kill operation.
    if ($values['kill']) {
      $batch['operations'][] = [
        'devel_generate_operation',
        [$this, 'batchGroupContentKill', $values],
      ];
    }

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

  /**
   * Delete existing groupContent of the given types.
   *
   * @param array $values
   *   The input values from the settings form.
   * @param array $context
   *   An array of contextual key/value information for rebuild batch process.
   */
  public function batchGroupContentKill(array $values, array &$context) {
    foreach (GroupContentType::loadMultiple($values['group_content_types']) as $contentType) {
      $content_items = GroupContent::loadByContentPluginId($contentType->id());
    }
    foreach ($values['group_content_types'] as $contentTypeId) {
      $vars = ['group_content_types' => [$contentTypeId]];
      $this->groupContentKill($vars);
    }
  }

  /**
   * Delete existing groupContent of the given type.
   *
   * @param array $values
   *   The input values from the settings form or batch.
   */
  public function groupContentKill(array $values) {
    $contentTypeId = reset($values['group_content_types']);
    foreach (GroupContent::loadByContentPluginId($contentTypeId) as $item) {
      $item->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []) {
    // Drush doesn't provide the option to choose types but assumes all.
    $values['group_content_types'] = array_keys(GroupContentType::loadMultiple());
    if (isset($options['kill'])) {
      $values['kill'] = $options['kill'];
    }
    return $values;
  }

  /**
   * Put all content of one type into as many groups as it will support.
   *
   * We use the finished return value in the context to indicate if we have
   * finished.
   *
   * @param array $values
   *   The input values from the settings form with some additional data needed
   *   for the generation. Should at least contain value 'content_type'.
   * @param array $context
   *   The batch context.
   */
  public function batchAddGroupContent(array $values, array &$context) {
    if (empty($context['results'])) {
      $context['results'] = $values;
      $context['results']['num'] = 0;
    }

    // The GroupContentType plugin tells us which groupType(s) we need.
    $groupContentType = GroupContentType::load($values['content_type']);
    $entityTypeId = $groupContentType->getContentPlugin()->getEntityTypeId();
    $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);
    $groupStorage = $this->entityTypeManager->getStorage('group');

    // If we do not have a working set of IDs yet, build it using an entity
    // query.
    $sandbox = &$context['sandbox'];
    if (empty($sandbox)) {
      if (!$this->setUpBatchSandbox($groupContentType, $sandbox)) {
        return;
      }

      // Say that we haven't even started yet. This setup will have already
      // taken some time, so better safe than sorry and start the actual
      // processing on the next iteration.
      $context['finished'] = 0;
      return;
    }

    // Load our entity.
    $entity = $entityStorage->load($sandbox['content_ids'][$sandbox['current']]);

    // Find out how many groups we may add the content to.
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $groupContentType->getContentPlugin();
    $groupCardinality = $plugin->getGroupCardinality();

    // If the cardinality is unlimited (0) or larger than 50, limit to 50.
    if ($groupCardinality == 0 || $groupCardinality > 50) {
      $groupCardinality = 50;
    }

    // Keep track of the groups we assigned the entity to.
    $groupsAssigned = [];

    if ($entity) {
      for ($i = 0; $i < $groupCardinality; $i++) {
        // Pick a random index for the group.
        $groupIndex = random_int(0, count($sandbox['group_ids']) - 1);

        if (in_array($groupIndex, $groupsAssigned)) {
          // Do not attempt to add the content to the same group twice.
          continue;
        }

        $groupsAssigned[] = $groupIndex;

        $groupId = $sandbox['group_ids'][$groupIndex];
        $group = $groupStorage->load($groupId);

        if (!$group) {
          continue;
        }

        $group->addContent(
          $entity,
          $plugin->getPluginId(),
          ['uid' => $entity instanceof EntityOwnerInterface ? $entity->getOwnerId() : 1]
        );

        // If we are adding users as members then add one random role to the
        // newly created membership as well.
        if ($sandbox['roles']) {
          $roleIndex = random_int(0, count($sandbox['roles']) - 1);
          $this->membershipLoader
            ->load($group, $entity)
            ->getGroupContent()
            ->set('group_roles', [$sandbox['roles'][$roleIndex]])
            ->save();
        }
      }
    }

    $sandbox['current']++;

    $current = $sandbox['current'];
    $total = $sandbox['total_content_items'];
    $finished = $current >= $total ? 1 : ($current / $total);
    $context['finished'] = $finished;
    $context['results']['num']++;
  }

  /**
   * Helper function to set up the sandbox for a single group content type.
   *
   * @param \Drupal\group\Entity\GroupContentType $groupContentType
   *   The group content type to set up for.
   * @param array $sandbox
   *   The batch sandbox.
   *
   * @return bool
   *   True when succesful, false when there was a problem. Calling method
   *   should return on false.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function setUpBatchSandbox(GroupContentType $groupContentType, array &$sandbox) {
    $entityTypeId = $groupContentType->getContentPlugin()->getEntityTypeId();
    $groupTypeId = $groupContentType->getGroupTypeId();
    $groupContentTypeId = $groupContentType->id();

    // Load all the groups of this type.
    $groupIds = $this->entityTypeManager->getStorage('group')
      ->getQuery()->condition('type', $groupTypeId)
      ->execute();
    if (empty($groupIds)) {
      $message = $this->t(
        'No %group_type_id groups to which to add GroupContent %type.',
        [
          '%group_type_id' => $groupTypeId,
          '%type' => $groupContentTypeId,
        ]
      );
      $this->setMessage($message, MessengerInterface::TYPE_WARNING);
      return FALSE;
    }
    $sandbox['group_ids'] = array_values($groupIds);

    $contentPlugin = $groupContentType->getContentPlugin();

    if ($contentPlugin->getPluginId() == 'group_membership') {
      // Get the roles for this group-type.
      $roles = \Drupal::entityQuery('group_role')
        ->condition('group_type', $groupTypeId, '=')
        ->condition('internal', 0, '=')
        ->execute();
      $sandbox['roles'] = $roles;
    }

    // Load all relevant content entities.
    $query = $this->entityTypeManager->getStorage($entityTypeId)->getQuery();

    $bundleId = $contentPlugin->getEntityBundle();
    if ($bundleId) {
      $entityType = $this->entityTypeManager->getDefinition($entityTypeId);
      $bundleField = $entityType->getKey('bundle');
      $query->condition($bundleField, $bundleId);
    }

    $contentIds = $query->execute();

    if (empty($contentIds)) {
      $message = $this->t(
        'No %type content to add to %group_type_id groups.',
        [
          '%group_type_id' => $groupTypeId,
          '%type' => $groupContentTypeId,
        ]
      );
      $this->setMessage($message, MessengerInterface::TYPE_WARNING);
      return FALSE;
    }

    $sandbox['content_ids'] = array_values($contentIds);
    $sandbox['total_content_items'] = count($contentIds);
    $sandbox['current'] = 0;

    return TRUE;
  }

}
