<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupListBuilder.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for group entity.
 *
 * @ingroup group
 */
class GroupListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['gid'] = $this->t('Group ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['uid'] = $this->t('Owner');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\group\Entity\Group */
    $row['id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['type'] = $entity->type->entity->label();
    $row['uid'] = $entity->uid->entity->label();
    return $row + parent::buildRow($entity);
  }

}
