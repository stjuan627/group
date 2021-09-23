<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines a common interface for all group relations.
 *
 * @see \Drupal\group\Annotation\GroupRelationType
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationBase
 * @see plugin_api
 */
interface GroupRelationInterface extends DerivativeInspectionInterface, ConfigurableInterface, DependentPluginInterface, PluginFormInterface {

  /**
   * Gets the ID of the type of the relation.
   *
   * @return string
   *   The relation type ID.
   */
  public function getRelationTypeId();

  /**
   * Gets the relation type definition.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface
   *   The relation type definition.
   */
  public function getRelationType();

  /**
   * Returns the amount of groups the same content can be added to.
   *
   * @return int
   *   The group content's group cardinality.
   */
  public function getGroupCardinality();

  /**
   * Returns the amount of times the same content can be added to a group.
   *
   * @return int
   *   The group content's entity cardinality.
   */
  public function getEntityCardinality();

  /**
   * Returns the group type the plugin was instantiated for.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface|null
   *   The group type, if set in the plugin configuration.
   */
  public function getGroupType();

  /**
   * Returns the ID of the group type the plugin was instantiated for.
   *
   * @return string|null
   *   The group type ID, if set in the plugin configuration.
   */
  public function getGroupTypeId();

  /**
   * Retrieves the label for a piece of group content.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity to retrieve the label for.
   *
   * @return string
   *   The label as expected by \Drupal\Core\Entity\EntityInterface::label().
   */
  public function getContentLabel(GroupContentInterface $group_content);

  /**
   * Returns a safe, unique configuration ID for a group content type.
   *
   * By default we use GROUP_TYPE_ID-PLUGIN_ID-DERIVATIVE_ID, but feel free to
   * use any other means of identifying group content types.
   *
   * Please do not return any invalid characters in the ID as it will crash the
   * website. Refer to ConfigBase::validateName() for valid characters.
   *
   * @return string
   *   The safe ID to use as the configuration name.
   *
   * @see \Drupal\Core\Config\ConfigBase::validateName()
   */
  public function getContentTypeConfigId();

  /**
   * Returns the administrative label for a group content type.
   *
   * @return string
   *   The group content type label.
   */
  public function getContentTypeLabel();

  /**
   * Returns the administrative description for a group content type.
   *
   * @return string
   *   The group content type description.
   */
  public function getContentTypeDescription();

  /**
   * Returns a list of entity reference field settings.
   *
   * This allows you to provide some handler settings for the entity reference
   * field pointing to the entity that is to become group content. You could
   * even change the handler being used, all without having to alter the bundle
   * field settings yourself through an alter hook.
   *
   * @return array
   *   An associative array where the keys are valid entity reference field
   *   setting names and the values are the corresponding setting for each key.
   *   Often used keys are 'target_type', 'handler' and 'handler_settings'.
   */
  public function getEntityReferenceSettings();

}
