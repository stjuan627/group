<?php

/**
 * @file
 * Contains \Drupal\group\GroupTypeListBuilder.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of group type entities.
 *
 * @see \Drupal\group\Entity\GroupType
 */
class GroupTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['description'] = array(
      'data' => t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $entity */
    $row['label'] = array(
      'data' => $entity->label(),
      'class' => array('menu-label'),
    );
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the group type specific operations after the operations added by
    // field_ui.module which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }

    if ($entity->hasLinkTemplate('permissions-form')) {
      $operations['permissions'] = array(
        'title' => t('Edit permissions'),
        'weight' => 35,
        'url' => $entity->toUrl('permissions-form'),
      );
    }

    if ($entity->hasLinkTemplate('content-plugins')) {
      $operations['content'] = array(
        'title' => t('Set available content'),
        'weight' => 40,
        'url' => $entity->toUrl('content-plugins'),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No group types available. <a href="@link">Add group type</a>.', [
      '@link' => Url::fromRoute('group.type_add')->toString()
    ]);
    return $build;
  }

}
