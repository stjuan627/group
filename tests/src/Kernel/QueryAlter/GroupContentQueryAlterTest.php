<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\Storage\GroupContentTypeStorageInterface;
use Drupal\group\QueryAccess\GroupContentQueryAlter;

/**
 * Tests the behavior of relationship query alter.
 *
 * @coversDefaultClass \Drupal\group\QueryAccess\GroupContentQueryAlter
 * @group group
 */
class GroupContentQueryAlterTest extends QueryAlterTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'group_content';

  /**
   * The plugin ID to use in testing.
   *
   * @var string
   */
  protected $pluginId = 'user_as_content';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * {@inheritdoc}
   */
  public function queryAccessProvider() {
    $cases = parent::queryAccessProvider();

    // There is no difference between no content and content.
    foreach (['update', 'delete', 'view'] as $operation) {
      unset($cases["no-content-$operation"]);
    }

    // Only view own is supported.
    foreach (['synchronized', 'individual', 'combined'] as $scope) {
      unset($cases["$scope-own-view"]);
    }

    // All cases with access check the plugin ID.
    foreach ($cases as $key => $case) {
      if ($case['has_access']) {
        $cases[$key]['checks_data_table'] = TRUE;
      }
    }

    return $cases;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlterClass() {
    return GroupContentQueryAlter::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPermission($operation, $scope, $unpublished = FALSE) {
    if ($operation === 'view') {
      return "$operation $this->pluginId relation";
    }
    return "$operation $scope $this->pluginId relation";
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermission() {
    return "administer $this->pluginId";
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpContent(GroupTypeInterface $group_type) {
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($storage instanceof GroupContentTypeStorageInterface);
    $storage->save($storage->createFromPlugin($group_type, $this->pluginId));
    return $this->createGroup(['type' => $group_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return 'group_content';
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
    return 'id';
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
    $sub_condition = $conditions->andConditionGroup();
    $sub_condition->condition('group_content_field_data.group_type', $allowed_ids, 'IN');
    $sub_condition->condition('group_content_field_data.plugin_id', $this->pluginId);
    $sub_condition->isNull('gcfd.entity_id');
    $conditions->condition($sub_condition);
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions) {
    $sub_condition = $conditions->andConditionGroup();
    $sub_condition->condition('group_content_field_data.gid', $allowed_ids, 'IN');
    $sub_condition->condition('group_content_field_data.plugin_id', $this->pluginId);
    $conditions->condition($sub_condition);
  }

}
