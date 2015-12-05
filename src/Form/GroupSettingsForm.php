<?php
/**
 * @file
 * Contains Drupal\group\Form\GroupSettingsForm.
 */

namespace Drupal\group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupSettingsForm.
 * @package Drupal\group\Form
 * @ingroup group
 */
class GroupSettingsForm extends FormBase {

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupSettingsForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.group_content'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'group_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Define the form used for Group settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo Split this table off into a page and use hook_help, @see field_ui
    $form['plugins'] = [
      '#type' => 'table',
      '#caption' => $this->t('Entities that can be added to groups.'),
      '#header' => [
        'label' => $this->t('Label'),
        'description' => $this->t('Description'),
        'provider' => $this->t('Provided by'),
        'entity_type_id' => $this->t('Applies to'),
      ],
    ];

    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $plugin_id => $plugin_info) {
      $form['plugins'][$plugin_id] = [
        'label' => ['#markup' => $plugin_info['label']],
        'description' => ['#markup' => $plugin_info['description']],
        'provider' => ['#markup' => $this->moduleHandler->getName($plugin_info['provider'])],
        'entity_type_id' => ['#markup' => $this->entityTypeManager->getDefinition($plugin_info['entity_type_id'])->getLabel()],
      ];
    }

    return $form;
  }
}
