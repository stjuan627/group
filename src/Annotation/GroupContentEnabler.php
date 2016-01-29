<?php

/**
 * @file
 * Contains \Drupal\group\Annotation\GroupContentEnabler.
 */

namespace Drupal\group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GroupContentEnabler annotation object.
 *
 * Plugin Namespace: Plugin\GroupContentEnabler
 *
 * For a working example, see
 * \Drupal\group\Plugin\GroupContentEnabler\GroupMembership
 *
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see \Drupal\group\Plugin\GroupContentEnablerManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupContentEnabler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the GroupContentEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the GroupContentEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The ID of the entity type you want to enable as group content.
   *
   * @var string
   */
  public $entity_type_id;

  /**
   * (optional) An associative array of paths to generate routes for.
   *
   * Each entry is a path containing at least a {group} parameter and optionally
   * a {group_content} parameter if it needs one. Keys that are supported by the
   * base plugin: collection, canonical, add-form, edit-form and delete-form.
   *
   * Feel free to add your own keys to them and implement a route for it in
   * your GroupContentEnablerInterface::getRoutes() implementation.
   *
   * Refer to GroupMembership for an example.
   *
   * @var string
   *
   * @see \Drupal\group\Plugin\GroupContentEnabler\GroupMembership
   */
  public $paths = [];

  /**
   * (optional) The amount of times the same content may be added to a group.
   *
   * Defaults to 0, which means unlimited.
   *
   * @var int
   */
  public $cardinality = 0;

  /**
   * (optional) Whether this plugin is always on.
   *
   * @var bool
   */
  public $enforced = FALSE;

}
