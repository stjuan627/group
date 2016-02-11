<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Validation\Constraint\GroupContentCardinalityValidator.
 */

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if content has reached the maximum amount of times it can be added.
 *
 * You should probably only use this constraint in your FieldType plugin through
 * TypedDataInterface::getConstraints() or set it on a base field definition
 * using BaseFieldDefinition->addConstraint('GroupContentCardinality').
 *
 * The reason is that we expect $value to be a FieldItemListInterface and
 * setting the constraint in your FieldType annotation will hand us a single
 * FieldItemInterface object instead. On the other hand, setting it on target_id
 * through BaseFieldDefinition::addPropertyConstraint() will only pass us the
 * integer value (the ID).
 */
class GroupContentCardinalityValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    if (!isset($value)) {
      return;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $value->getEntity();
    $cardinality = $group_content->getContentPlugin()->getCardinality();

    // Exit early if the cardinality is set to unlimited.
    if ($cardinality <= 0) {
      return;
    }

    if (!empty($value->target_id)) {
      // Get the current instances of this content entity in the group.
      $group = $group_content->getGroup();
      $plugin_id = $group_content->getContentPlugin()->getPluginId();
      $instances = $group->getContentByEntityId($plugin_id, $value->target_id);

      // Raise a violation if the content has reached the cardinality limit.
      if (count($instances) >= $cardinality) {
        /** @var \Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality $constraint */
        $this->context->buildViolation($constraint->message)
          ->setParameter('@field', $group_content->getFieldDefinition('entity_id')->getLabel())
          ->setParameter('%content', $group_content->getEntity()->label())
          ->setParameter('%group', $group->label())
          // We need to manually set the path to the first element because we
          // expect this contraint to be set on the EntityReferenceItem level
          // and therefore receive a FieldItemListInterface as the value.
          ->atPath('0')
          ->addViolation();
      }
    }
  }

}
