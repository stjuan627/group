<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\RoleInterface;

/**
 * Base class for testing query alters.
 */
abstract class QueryAlterTestBase extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The entity type ID to use in testing.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Whether the entity type supports publishing.
   *
   * @var bool
   */
  protected $isPublishable = FALSE;

  /**
   * Tests query access in various scenarios.
   *
   * @param string $operation
   *   The operation to test access for.
   * @param bool $has_content
   *   Whether relationships exist for the entity type.
   * @param bool $has_access
   *   Whether the user should gain access.
   * @param bool $is_admin
   *   Whether the user is a group admin.
   * @param string[] $synchronized_permissions
   *   The user's group permissions in a synchronized scope.
   * @param string[] $individual_permissions
   *   The user's group permissions in the individual scope.
   * @param bool $checks_data_table
   *   Whether the query is expected to join the data table.
   * @param bool $checks_status
   *   Whether the query is expected to check the entity status.
   * @param bool $checks_owner
   *   Whether the query is expected to check the entity owner.
   * @param int $status
   *   (optional) The status value to check for. Defaults to 1.
   *
   * @covers ::getConditions
   * @dataProvider queryAccessProvider
   */
  public function testQueryAccess(
    $operation,
    $has_content,
    $has_access,
    $is_admin,
    array $synchronized_permissions,
    array $individual_permissions,
    $checks_data_table,
    $checks_status,
    $checks_owner,
    $status = 1
  ) {
    if ($checks_status || $checks_owner) {
      $this->assertTrue($checks_data_table, 'Data table should be checked for status or owner.');
    }

    $definition = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $data_table = $definition->getDataTable() ?: $definition->getBaseTable();
    $group_type = $this->createGroupType();

    if ($synchronized_permissions) {
      $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::OUTSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $is_admin ? [] : $synchronized_permissions,
        'admin' => $is_admin,
      ]);
    }

    if ($individual_permissions) {
      $group_role = $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
        'permissions' => $is_admin ? [] : $individual_permissions,
        'admin' => $is_admin,
      ]);
      $group_type->set('creator_roles', [$group_role->id()]);
      $group_type->save();
    }

    if ($has_content) {
      $group = $this->setUpContent($group_type);
    }

    $query = $this->createAlterableQuery($operation);
    $control = $this->createAlterableQuery($operation);

    $this->alterQuery($query);
    if ($has_content) {
      $this->joinExtraTables($control);

      if (!$has_access) {
        $this->addNoAccessConditions($control);
      }
      else {
        if ($definition->getDataTable() && $checks_data_table) {
          $this->joinTargetEntityDataTable($control);
        }
        $scope_conditions = $this->addWrapperConditionGroup($control);

        if ($checks_status) {
          $status_key = $definition->getKey('published');
          $scope_conditions->condition($status_group = $control->andConditionGroup());
          $status_group->condition("$data_table.$status_key", $status);
          $status_group->condition($scope_conditions = $control->orConditionGroup());
        }

        if ($checks_owner) {
          $owner_key = $definition->getKey('owner');
          $scope_conditions->condition($owner_conditions = $control->andConditionGroup());
          $owner_conditions->condition("$data_table.$owner_key", $this->getCurrentUser()->id());
          $owner_conditions->condition($scope_conditions = $control->orConditionGroup());
        }

        if ($synchronized_permissions) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->joinMemberships($control);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions);
        }

        if ($individual_permissions) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addIndividualConditions([$group->id()], $scope_conditions);
        }
      }
    }

    $this->assertEqualsCanonicalizing($control->getTables(), $query->getTables());
    $this->assertEqualsCanonicalizing($control->conditions(), $query->conditions());
  }

  /**
   * Data provider for testQueryAccess().
   *
   * @return array
   *   A list of testQueryAccess method arguments.
   */
  public function queryAccessProvider() {
    // @todo Test mixed (own and any combined) operations.
    foreach (['view', 'update', 'delete'] as $operation) {
      // Case when there is no relationship for the entity type.
      $cases['no-content-' . $operation] = [
        'operation' => $operation,
        'has_content' => FALSE,
        'has_access' => FALSE,
        'is_admin' => FALSE,
        'synchronized_perm' => [],
        'individual_perm' => [],
        'checks_data_table' => FALSE,
        'checks_status' => FALSE,
        'checks_owner' => FALSE,
      ];

      // Case when nothing grants access.
      $cases['no-access-' . $operation] = [
        'operation' => $operation,
        'has_content' => TRUE,
        'has_access' => FALSE,
        'is_admin' => FALSE,
        'synchronized_perm' => [],
        'individual_perm' => [],
        'checks_data_table' => FALSE,
        'checks_status' => FALSE,
        'checks_owner' => FALSE,
      ];

      // Admin case to demonstrate fewer checks are run.
      $cases['admin-synchronized-' . $operation] = [
        'operation' => $operation,
        'has_content' => TRUE,
        'has_access' => TRUE,
        'is_admin' => FALSE,
        // Add the any/own permissions to prove they are never checked.
        'synchronized_perm' => [
          $this->getAdminPermission(),
          $this->getPermission($operation, 'any'),
          $this->getPermission($operation, 'own'),
        ],
        'individual_perm' => [],
        'checks_data_table' => FALSE,
        'checks_status' => FALSE,
        'checks_owner' => FALSE,
      ];

      // Copy synchronized admin case to individual.
      $copy = $cases['admin-synchronized-' . $operation];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $copy['synchronized_perm'] = [];
      $cases['admin-individual-' . $operation] = $copy;

      // Copy synchronized 'any' admin case to combined.
      $copy = $cases['admin-synchronized-' . $operation];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $cases['admin-combined-' . $operation] = $copy;

      // Case with synchronized permissions only.
      $checks_status = $this->isPublishable && $operation === 'view';
      $cases['synchronized-any-' . $operation] = [
        'operation' => $operation,
        'has_content' => TRUE,
        'has_access' => TRUE,
        'is_admin' => FALSE,
        // Add the own permission to prove it's never checked.
        'synchronized_perm' => [
          $this->getPermission($operation, 'any'),
          $this->getPermission($operation, 'own'),
        ],
        'individual_perm' => [],
        // View operations check for published status.
        'checks_data_table' => $checks_status,
        'checks_status' => $checks_status,
        'checks_owner' => FALSE,
      ];

      // Copy synchronized 'any' case to 'own'.
      $copy = $cases['synchronized-any-' . $operation];
      $copy['synchronized_perm'] = [$this->getPermission($operation, 'own')];
      $copy['checks_data_table'] = TRUE;
      $copy['checks_owner'] = TRUE;
      $cases['synchronized-own-' . $operation] = $copy;

      // Copy synchronized 'any' case to individual.
      $copy = $cases['synchronized-any-' . $operation];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $copy['synchronized_perm'] = [];
      $cases['individual-any-' . $operation] = $copy;

      // Copy individual 'any' case to 'own'.
      $copy['individual_perm'] = [$this->getPermission($operation, 'own')];
      $copy['checks_data_table'] = TRUE;
      $copy['checks_owner'] = TRUE;
      $cases['individual-own-' . $operation] = $copy;

      // Copy synchronized 'any' case to combined.
      $copy = $cases['synchronized-any-' . $operation];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $cases['combined-any-' . $operation] = $copy;

      // Copy synchronized 'own' case to combined.
      $copy = $cases['synchronized-own-' . $operation];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $cases['combined-own-' . $operation] = $copy;
    }

    if ($this->isPublishable) {
      // Test the view unpublished permissions.
      $copy = $cases['synchronized-any-view'];
      $copy['status'] = 0;
      $copy['synchronized_perm'] = [
        // Add the own permission to prove it's never checked.
        $this->getPermission('view', 'any', TRUE),
        $this->getPermission('view', 'own', TRUE),
      ];
      $cases['synchronized-any-view-unpublished'] = $copy;

      // Copy synchronized 'any' case to 'own'.
      $copy['synchronized_perm'] = [$this->getPermission('view', 'own', TRUE)];
      $copy['checks_owner'] = TRUE;
      $cases['synchronized-own-view-unpublished'] = $copy;

      // Copy synchronized 'any' case to individual.
      $copy = $cases['synchronized-any-view-unpublished'];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $copy['synchronized_perm'] = [];
      $cases['individual-any-view-unpublished'] = $copy;

      // Copy individual 'any' case to 'own'.
      $copy['individual_perm'] = [$this->getPermission('view', 'own', TRUE)];
      $copy['checks_owner'] = TRUE;
      $cases['individual-own-view-unpublished'] = $copy;

      // Copy synchronized 'any' case to combined.
      $copy = $cases['synchronized-any-view-unpublished'];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $cases['combined-any-view-unpublished'] = $copy;

      // Copy synchronized 'own' case to combined.
      $copy = $cases['synchronized-own-view-unpublished'];
      $copy['individual_perm'] = $copy['synchronized_perm'];
      $cases['combined-own-view-unpublished'] = $copy;
    }

    return $cases;
  }

  /**
   * Gets the permission name for the given operation and scope.
   *
   * @param string $operation
   *   The operation.
   * @param string $scope
   *   The operation scope (any or own).
   * @param bool $unpublished
   *   Whether to check for the unpublished permission. Defaults to FALSE.
   *
   * @return string
   *   The permission name.
   */
  abstract protected function getPermission($operation, $scope, $unpublished = FALSE);

  /**
   * Gets the admin permission name.
   *
   * @return string
   *   The admin permission name.
   */
  abstract protected function getAdminPermission();

  /**
   * Builds and returns a query that will be altered.
   *
   * @param string $operation
   *   The operation for the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The alterable query.
   */
  protected function createAlterableQuery($operation) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $query = \Drupal::database()->select($entity_type->getBaseTable());
    $query->addMetaData('op', $operation);
    $query->addMetaData('entity_type', $this->entityTypeId);
    return $query;
  }

  /**
   * Alters the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to alter.
   */
  protected function alterQuery(SelectInterface $query) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    \Drupal::service('class_resolver')
      ->getInstanceFromDefinition($this->getAlterClass())
      ->alter($query, $entity_type);
  }

  /**
   * Retrieves the namespaced alter class name.
   *
   * @return string
   *   The namespaced alter class name.
   */
  abstract protected function getAlterClass();

  /**
   * Makes sure a condition group has the OR conjunction.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *   The conditions to ensure the conjunction for.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The potentially modified condition group.
   */
  protected function ensureOrConjunction(ConditionInterface $conditions) {
    $conditions_array = $conditions->conditions();
    if ($conditions_array['#conjunction'] === 'OR') {
      return $conditions;
    }

    $conditions->condition($scope_conditions = $conditions->orConditionGroup());
    return $scope_conditions;
  }

  /**
   * Joins any extra tables required for access checks.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join(s) to.
   */
  protected function joinExtraTables(SelectInterface $query) {}

  /**
   * Joins the target entity data table.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join to.
   */
  protected function joinTargetEntityDataTable(SelectInterface $query) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $base_table = $entity_type->getBaseTable();
    $data_table = $entity_type->getDataTable();
    $id_key = $entity_type->getKey('id');
    $query->join(
      $data_table,
      $data_table,
      "$base_table.$id_key=$data_table.$id_key",
    );
  }

  /**
   * Joins the relationship field data table for memberships.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join to.
   */
  protected function joinMemberships(SelectInterface $query) {
    $table = $this->getMembershipJoinTable();
    $l_field = $this->getMembershipJoinLeftField();
    $r_field = $this->getMembershipJoinRightField();

    $query->leftJoin(
      'group_content_field_data',
      'gcfd',
      "$table.$l_field=%alias.$r_field AND %alias.plugin_id='group_membership' AND %alias.entity_id=:account_id",
      [':account_id' => $this->getCurrentUser()->id()]
    );
  }

  /**
   * Retrieves the name of the table to join the memberships against.
   *
   * @return string
   *   The table name.
   */
  abstract protected function getMembershipJoinTable();

  /**
   * Retrieves the name of the field to join the memberships against.
   *
   * @return string
   *   The field name.
   */
  abstract protected function getMembershipJoinLeftField();

  /**
   * Retrieves the name of the field to join the memberships with.
   *
   * @return string
   *   The field name.
   */
  abstract protected function getMembershipJoinRightField();

  /**
   * Sets up the content for testing.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create a group with content for.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group containing the content.
   */
  abstract protected function setUpContent(GroupTypeInterface $group_type);

  /**
   * Adds a no access conditions to the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *  The query to add the access check to.
   */
  abstract protected function addNoAccessConditions(SelectInterface $query);

  /**
   * Adds and returns a wrapper condition group if necessary.
   *
   * This method allows subclasses to make more complex groups at the top level
   * of the query conditions.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *  The query to add the condition group to.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The query or wrapper condition group.
   */
  protected function addWrapperConditionGroup(SelectInterface $query) {
    return $query;
  }

  /**
   * Adds conditions for the synchronized outsider scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *  The condition group to add the access checks to.
   */
  abstract protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $conditions);

  /**
   * Adds conditions for the individual scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *  The condition group to add the access checks to.
   */
  abstract protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions);

}
