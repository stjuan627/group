<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupType.
 */

namespace Drupal\group\Entity;

use Drupal\group\Plugin\GroupContentEnablerHelper;
use Drupal\group\Plugin\GroupContentEnablerCollection;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_type",
 *   label = @Translation("Group type"),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\GroupTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupTypeListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "type",
 *   bundle_of = "group",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/group/types",
 *     "edit-form" = "/admin/group/types/manage/{group_type}",
 *     "delete-form" = "/admin/group/types/manage/{group_type}/delete",
 *     "content-plugins" = "/admin/group/types/manage/{group_type}/content",
 *     "permissions-form" = "/admin/group/types/manage/{group_type}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "roles",
 *     "content"
 *   }
 * )
 */
class GroupType extends ConfigEntityBundleBase implements GroupTypeInterface {

  /**
   * The machine name of the group type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group type.
   *
   * @var string
   */
  protected $description;

  /**
   * A list of group roles the group type uses.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface[]
   */
  protected $roles = [];

  /**
   * The content enabler plugin configuration for the group type.
   *
   * @todo Auto-enable fixed plugins so they can be configured.
   *
   * @var string[]
   */
  protected $content = [];

  /**
   * Holds the collection of content enabler plugins the group type uses.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerCollection
   */
  protected $contentCollection;

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
  public function getRoles() {
    return GroupRole::loadMultiple($this->roles);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleIds() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    foreach ($this->getRoles() as $group_role) {
      $this->addDependency('config', $group_role->getConfigDependencyName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Store the id in a short variable for readability.
    $id = $this->id();

    // Create three internal group roles for the group type.
    if ($this->isNew()) {
      $replace = ['%group_type' => $this->label()];
      GroupRole::create([
        'id' => "a_$id",
        'label' => t('Anonymous (%group_type)', $replace),
        'internal' => TRUE,
        'weight' => -102
      ])->save();
      GroupRole::create([
        'id' => "o_$id",
        'label' => t('Outsider (%group_type)', $replace),
        'internal' => TRUE,
        'weight' => -101,
      ])->save();
      GroupRole::create([
        'id' => "m_$id",
        'label' => t('Member (%group_type)', $replace),
        'internal' => TRUE,
        'weight' => -100,
      ])->save();
    }

    // Assign the three internal roles to the group type.
    array_push($this->roles, "a_$id", "o_$id", "m_$id");
    $this->roles = array_unique($this->roles);

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Enable enforced content plugins for new group types.
    if (!$update) {
      GroupContentEnablerHelper::installEnforcedPlugins($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the internal group roles along with the group type.
    foreach ($entities as $entity) {
      $id = $entity->id();
      entity_delete_multiple('group_role', ["a_$id", "o_$id", "m_$id"]);
    }
  }

  /**
   * Returns the content enabler plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The group content plugin manager.
   */
  protected function getContentEnablerManager() {
    return \Drupal::service('plugin.manager.group_content_enabler');
  }

  /**
   * {@inheritdoc}
   */
  public function enabledContent() {
    if (!$this->contentCollection) {
      $this->contentCollection = new GroupContentEnablerCollection($this->getContentEnablerManager(), $this->content);
      $this->contentCollection->sort();
    }
    return $this->contentCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return array('content' => $this->enabledContent());
  }

  /**
   * {@inheritdoc}
   */
  public function enableContent($plugin_id, array $configuration = []) {
    // Save the plugin to the group type.
    $configuration['id'] = $plugin_id;
    $this->enabledContent()->addInstanceId($plugin_id, $configuration);
    $this->save();

    // Save the group content type config entity.
    $plugin = $this->enabledContent()->get($plugin_id);
    $values = [
      'id' => $plugin->getContentTypeConfigId($this),
      'label' => $plugin->getContentTypeLabel($this),
      'description' => $plugin->getContentTypeDescription($this),
      'group_type' => $this->id(),
      'content_plugin' => $plugin_id,
    ];
    GroupContentType::create($values)->save();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disableContent($plugin_id) {
    // Get the content type ID from the plugin instance before we delete it.
    $plugin = $this->enabledContent()->get($plugin_id);
    $content_type_id = $plugin->getContentTypeConfigId($this);

    // Remove the plugin from the group type.
    $this->enabledContent()->removeInstanceId($plugin_id);
    $this->save();

    // Delete the group content type config entity.
    GroupContentType::load($content_type_id)->delete();

    return $this;
  }

}
