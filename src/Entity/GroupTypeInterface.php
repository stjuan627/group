<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a group type entity.
 */
interface GroupTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface, RevisionableEntityBundleInterface {

  /**
   * The maximum length of the ID, in characters.
   *
   * This is shorter than the default limit of 32 to allow group roles to have
   * an ID which can be appended to the group type's ID without exceeding the
   * default limit there. We leave of 10 characters to account for '-anonymous'.
   */
  const ID_MAX_LENGTH = 22;

  /**
   * Gets the group roles.
   *
   * @param bool $include_internal
   *   (optional) Whether to include internal roles in the result. Defaults to
   *   TRUE.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles this group type uses.
   */
  public function getRoles($include_internal = TRUE);

  /**
   * Gets the role IDs.
   *
   * @param bool $include_internal
   *   (optional) Whether to include internal roles in the result. Defaults to
   *   TRUE.
   *
   * @return string[]
   *   The ids of the group roles this group type uses.
   */
  public function getRoleIds($include_internal = TRUE);

  /**
   * Gets the generic anonymous group role for this group type.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The anonymous group role this group type uses.
   */
  public function getAnonymousRole();

  /**
   * Gets the generic anonymous role ID.
   *
   * @return string
   *   The ID of the anonymous group role this group type uses.
   */
  public function getAnonymousRoleId();

  /**
   * Gets the generic outsider group role for this group type.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The outsider group role this group type uses.
   */
  public function getOutsiderRole();

  /**
   * Gets the generic outsider role ID.
   *
   * @return string
   *   The ID of the outsider group role this group type uses.
   */
  public function getOutsiderRoleId();

  /**
   * Gets the generic member group role for this group type.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The generic member group role this group type uses.
   */
  public function getMemberRole();

  /**
   * Gets the generic member role ID.
   *
   * @return string
   *   The ID of the generic member group role this group type uses.
   */
  public function getMemberRoleId();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Returns whether the group creator automatically receives a membership.
   *
   * @return bool
   *   Whether the group creator automatically receives a membership.
   */
  public function creatorGetsMembership();

  /**
   * Returns whether the group creator must complete their membership.
   *
   * @return bool
   *   Whether the group creator must complete their membership.
   */
  public function creatorMustCompleteMembership();

  /**
   * Returns whether the group roles should match with Drupal roles.
   *
   * @return bool
   *   Whether the group creator role must be set from drupal roles.
   */
  public function creatorRoleMembership(): bool;

  /**
   * Returns group roles for creators based on their site-wide roles.
   *
   * @return array
   *   An associate array where primary keys are site-wide role names and values
   *   are group roles that should be assigned to users with a given site-wide
   *   role.
   */
  public function creatorRoleMembershipRoles(): array;

  /**
   * Gets the IDs of the group roles a group creator should receive.
   *
   * @return string
   *   The IDs of the group role the group creator should receive.
   */
  public function getCreatorRoleIds();

  /**
   * Returns the installed content enabler plugins for this group type.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   The group content plugin collection.
   */
  public function getInstalledContentPlugins();

  /**
   * Checks whether a content enabler plugin is installed for this group type.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin to check for.
   *
   * @return bool
   *   Whether the content enabler plugin is installed.
   */
  public function hasContentPlugin($plugin_id);

  /**
   * Gets an installed content enabler plugin for this group type.
   *
   * Warning: In places where the plugin may not be installed on the group type,
   * you should always run ::hasContentPlugin() first or you may risk ending up
   * with crashes or unreliable data.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The installed content enabler plugin for the group type.
   */
  public function getContentPlugin($plugin_id);

  /**
   * Returns group roles that should be assigned to the current user.
   *
   * This is defined based on the user's current roles.
   *
   * @return array
   *   An array of group roles.
   */
  public function getRoleMembershipRoles(): array;

}
