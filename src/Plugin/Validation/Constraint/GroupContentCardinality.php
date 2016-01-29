<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality.
 */

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks how many times a piece of content can be added to a group.
 *
 * If the responsible content enabler plugin only allows a piece of content to
 * be added to a group a specific amount of times, this constraint will ensure
 * that the entity reference field will not go over said limit.
 *
 * @Constraint(
 *   id = "GroupContentCardinality",
 *   label = @Translation("Group content cardinality check", context = "Validation")
 * )
 */
class GroupContentCardinality extends Constraint {

  /**
   * The message to show when a group entity has reached the cardinality limit.
   *
   * @var string
   */
  public $message = '@field: %content has reached the maximum amount of times it can be added to %group';

}
