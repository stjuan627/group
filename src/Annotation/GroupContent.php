<?php

/**
 * @file
 * Contains \Drupal\group\Annotation\GroupContent.
 */

namespace Drupal\group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GroupContent annotation object.
 *
 * Plugin Namespace: Plugin\GroupContent
 *
 * For a working example, see \Drupal\group\Plugin\GroupContent\GroupMembership
 *
 * @see \Drupal\group\Plugin\GroupContentInterface
 * @see \Drupal\group\Plugin\GroupContentManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupContent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the GroupContent plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the GroupContent plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The ID of the entity type you want to use as group content.
   *
   * @var string
   */
  public $entity_type_id;

}
