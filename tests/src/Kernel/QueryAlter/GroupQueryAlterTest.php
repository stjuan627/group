<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\QueryAccess\GroupQueryAlter;

/**
 * Tests the behavior of group query alter.
 *
 * @coversDefaultClass \Drupal\group\QueryAccess\GroupQueryAlter
 * @group group
 */
class GroupQueryAlterTest extends QueryAlterTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'group';

  /**
   * {@inheritdoc}
   */
  protected $isPublishable = TRUE;

  /**
   * Whether the query has joined the data table.
   *
   * @var bool
   */
  protected $joinedFieldData = FALSE;

  /**
   * {@inheritdoc}
   */
  public function queryAccessProvider() {
    $cases = parent::queryAccessProvider();

    // There is no difference between no content and content.
    foreach (['update', 'delete', 'view'] as $operation) {
      unset($cases["no-content-$operation"]);
    }

    foreach (['synchronized', 'individual', 'combined'] as $scope) {
      // View own is only supported for unpublished.
      unset($cases["$scope-own-view"]);

      // We do not care for update/delete own.
      foreach (['update', 'delete'] as $operation) {
        unset($cases["$scope-own-$operation"]);
      }

      // Admin cases need to be full group admins.
      foreach (['update', 'delete', 'view'] as $operation) {
        $cases["admin-$scope-$operation"]['is_admin'] = TRUE;
      }
    }

    return $cases;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlterClass() {
    return GroupQueryAlter::class;
  }

  /**
   * Tests the conditions for mixed (own and any) view unpublished access.
   *
   * @covers ::getConditions
   */
  public function testMixedViewUnpublishedAccess() {
    $group_type_a = $this->createGroupType();
    $group_type_b = $this->createGroupType();
    $group_role = ['scope' => 'outsider', 'global_role' => 'authenticated'];
    $this->createGroupRole([
      'group_type' => $group_type_a->id(),
      'permissions' => ['view any unpublished group'],
    ] + $group_role);
    $this->createGroupRole([
      'group_type' => $group_type_b->id(),
      'permissions' => ['view own unpublished group'],
    ] + $group_role);

    $query = $this->createAlterableQuery('view');
    $this->alterQuery($query);

    $control = $this->createAlterableQuery('view');
    $this->joinTargetEntityDataTable($control);
    $this->joinMemberships($control);
    $this->assertEqualsCanonicalizing($control->getTables(), $query->getTables(), 'The group and memberships table is joined for status checks and membership lookups.');

    $control->condition($status_group = $control->andConditionGroup());
    $status_group->condition('groups_field_data.status', 0);
    $status_group->condition($status_sub_conditions = $control->orConditionGroup());
    $status_sub_conditions->condition($type_a_conditions = $control->andConditionGroup());
    $type_a_conditions->condition('groups_field_data.type', [$group_type_a->id()], 'IN');
    $type_a_conditions->isNull('gcfd.entity_id');
    $status_sub_conditions->condition($owner_conditions = $control->andConditionGroup());
    $owner_conditions->condition('groups_field_data.uid', $this->getCurrentUser()->id());
    $owner_conditions->condition($scope_conditions = $control->orConditionGroup());
    $scope_conditions->condition($type_b_conditions = $control->andConditionGroup());
    $type_b_conditions->condition('groups_field_data.type', [$group_type_b->id()], 'IN');
    $type_b_conditions->isNull('gcfd.entity_id');
    $this->assertEqualsCanonicalizing($control->conditions(), $query->conditions(), 'Status, membership and ownership are checked and synchronized scope is respected.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPermission($operation, $scope, $unpublished = FALSE) {
    switch ($operation) {
      case 'view':
        if ($unpublished) {
          return "$operation $scope unpublished group";
        }
        return 'view group';

      case 'update':
        return 'edit group';

      case 'delete':
        return 'delete group';
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermission() {
    return 'this does nothing';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpContent(GroupTypeInterface $group_type) {
    return $this->createGroup(['type' => $group_type->id()]);
  }

  protected function joinTargetEntityDataTable(SelectInterface $query) {
    parent::joinTargetEntityDataTable($query);
    $this->joinedFieldData = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return 'groups';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinLeftField() {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinRightField() {
    return 'gid';
  }

  /**
   * {@inheritdoc}
   */
  protected function addNoAccessConditions(SelectInterface $query) {
    $query->alwaysFalse();
  }

  /**
   * {@inheritdoc}
   */
  protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $conditions) {
    $type_table = $this->joinedFieldData ? 'groups_field_data' : 'groups';
    $conditions->condition($type_conditions = $conditions->andConditionGroup());
    $type_conditions->condition("$type_table.type", $allowed_ids, 'IN');
    $type_conditions->isNull('gcfd.entity_id');
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions) {
    $conditions->condition('groups.id', $allowed_ids, 'IN');
  }

}
