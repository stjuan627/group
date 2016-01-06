<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group routes.
 */
class GroupController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a GroupController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatter $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add content links for available group types.
   *
   * Redirects to group/add/[type] if only one group type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the group types that can be added; however,
   *   if there is only one group type defined for the site, the function
   *   will return a RedirectResponse to the group add page for that one group
   *   type.
   */
  public function addPage() {
    $group_types = array();

    // Only use group types the user has access to.
    foreach ($this->entityManager()->getStorage('group_type')->loadMultiple() as $type) {
      if ($this->entityManager()->getAccessControlHandler('group')->createAccess($type->id())) {
        $group_types[$type->id()] = $type;
      }
    }

    // Bypass the group/add listing if only one content type is available.
    if (count($group_types) == 1) {
      $type = array_shift($group_types);
      return $this->redirect('entity.group.add_form', array('group_type' => $type->id()));
    }

    return array(
      '#theme' => 'group_add_list',
      '#group_types' => $group_types,
    );
  }

  /**
   * Provides the group submission form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type entity for the group.
   *
   * @return array
   *   A group submission form.
   */
  public function add(GroupTypeInterface $group_type) {
    $group = $this->entityManager()->getStorage('group')->create(array(
      'type' => $group_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($group, 'add');

    return $form;
  }

  /**
   * The _title_callback for the entity.group.add_form route.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The current group.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(GroupTypeInterface $group_type) {
    return $this->t('Create @name', array('@name' => $group_type->label()));
  }

}
