<?php

namespace Drupal\group\Access;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides dynamic permissions for groups of different types.
 */
class GroupPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of group type permissions.
   *
   * @return array
   *   The group type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function groupTypePermissions() {
    $perms = [];

    // Generate group permissions for all group types.
    foreach (GroupType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of group permissions for a given group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $type
   *   The group type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(GroupTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id group" => [
        'title' => $this->t('%type_name: Create new group', $type_params),
      ],
    ];
  }

}
