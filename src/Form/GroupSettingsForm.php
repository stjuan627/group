<?php

namespace Drupal\group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration form for group settings.
 */
class GroupSettingsForm extends ConfigFormBase {

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a new GroupSettingsForm.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(RouteBuilderInterface $route_builder) {
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['group.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('group.settings');
    $form['use_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use admin theme'),
      '#description' => $this->t("Enables the administration theme for editing groups, members, etc."),
      '#default_value' => $config->get('use_admin_theme'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('group.settings');
    $conf_admin_theme = $config->get('use_admin_theme');
    $form_admin_theme = $form_state->getValue('use_admin_theme');

    // Only rebuild the routes if the admin theme switch has changed.
    if ($conf_admin_theme != $form_admin_theme) {
      $config->set('use_admin_theme', $form_admin_theme)->save();
      $this->routeBuilder->setRebuildNeeded();
    }

    parent::submitForm($form, $form_state);
  }

}
