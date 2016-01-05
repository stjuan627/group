<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupContentType.
 *
 * @todo Create these automatically for fixed plugins!
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Group content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_content_type",
 *   label = @Translation("Group content type"),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\GroupContentTypeAccessControlHandler",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "content_type",
 *   bundle_of = "group_content",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "group_type",
 *     "content_plugin",
 *   }
 * )
 */
class GroupContentType extends ConfigEntityBundleBase implements GroupContentTypeInterface {

  /**
   * The machine name of the group content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group content type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group content type.
   *
   * @var string
   */
  protected $description;

  /**
   * The group type ID for the group content type.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The group content enabler plugin ID for the group content type.
   *
   * @var string
   */
  protected $content_plugin;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->group_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin() {
    return $this->getGroupType()->enabledContent()->get($this->content_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // @todo add module defining plugin when fixed, group type when not fixed.
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());
  }

}
