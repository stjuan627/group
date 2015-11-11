<?php

/**
 * @file
 * Contains \Drupal\group\GroupRoleListBuilder.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of group role entities.
 *
 * @todo Global vs Type.
 *
 * @see \Drupal\group\Entity\GroupRole
 */
class GroupRoleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\group\Entity\GroupRole */
    $row['label'] = array(
      'data' => $entity->label(),
      'class' => array('menu-label'),
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    // @todo Exact link.
    $build['table']['#empty'] = $this->t('No group roles available. <a href="@link">Add group role</a>.', [
      '@link' => Url::fromRoute('group.role_add')->toString()
    ]);
    return $build;
  }

}
