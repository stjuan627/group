<?php

namespace Drupal\group\Entity;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Group content entity.
 *
 * @ingroup group
 */
interface GroupContentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the group content type entity the group content uses.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface
   *   The group content type entity the group content uses.
   */
  public function getGroupContentType();

  /**
   * Returns the group the group content belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group the group content belongs to.
   */
  public function getGroup();

  /**
   * Returns the group ID the group content belongs to.
   *
   * @return string
   *   The group ID the group content belongs to.
   */
  public function getGroupId();

  /**
   * Returns the group type the group content belongs to.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type the group content belongs to.
   */
  public function getGroupType();

  /**
   * Returns the group type ID the group content belongs to.
   *
   * @return string
   *   The group type ID the group content belongs to.
   */
  public function getGroupTypeId();

  /**
   * Returns the entity that was added as group content.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *  The entity that was added as group content.
   */
  public function getEntity();

  /**
   * Returns the group relation that handles the group content.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The group relation that handles the group content.
   */
  public function getRelationPlugin();

  /**
   * Gets the group relation type ID the group content uses.
   *
   * @return string
   *   The group relation type ID the group content uses.
   */
  public function getRelationPluginId();

  /**
   * Loads group content entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The group relation type ID.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   An array of group content entities indexed by their IDs.
   */
  public static function loadByPluginId($plugin_id);

  /**
   * Loads group content entities which reference a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   An array of group content entities which reference the given entity.
   */
  public static function loadByEntity(ContentEntityInterface $entity);

}
