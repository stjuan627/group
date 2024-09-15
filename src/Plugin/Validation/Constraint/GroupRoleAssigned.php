<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the previous assignments of a group role.
 *
 * @Constraint(
 *   id = "GroupRoleAssigned",
 *   label = @Translation("Group role previous assignments check", context = "Validation"),
 *   type = "entity:group_role"
 * )
 */
class GroupRoleAssigned extends Constraint {

  /**
   * When a group role is already assigned and in insider/outsider scope.
   *
   * @var string
   */
  public $alreadyAssignedMessage = 'Cannot set this group role to the %scope as it has already been assigned to individual members.';

}
