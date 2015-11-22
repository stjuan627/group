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
    /* @var $entity \Drupal\group\Entity\GroupTypeInterface */
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
    // Place the edit and edit permissions operation after the operations added
    // by field_ui.module which have the weights 15, 20, 25.
    if ($entity->hasLinkTemplate('permissions-form')) {
      $operations['permissions'] = array(
        'title' => t('Edit permissions'),
        'weight' => 30,
        'url' => $entity->urlInfo('permissions-form'),
      );
    }

    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 35;
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
