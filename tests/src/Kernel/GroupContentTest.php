<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests for the GroupContent entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupContent
 */
class GroupContentTest extends GroupKernelTestBase {

  /**
   * Ensure entity url templates are functional.
   *
   * @covers ::urlRouteParameters
   */
  public function testUrlRouteParameters() {
    $group = $this->createGroup();
    $account = $this->createUser();
    $group->addContent($account, 'group_membership');
    $group_content = $group->getContent('group_membership');
    foreach ($group_content as $item) {
      // Canonical.
      $expected = "/group/{$group->id()}/content/{$item->id()}";
      $this->assertEquals($expected, $item->toUrl()->toString());

      // Add form.
      $expected = "/group/{$group->id()}/content/add/group_membership?group_content_type=default-group_membership";
      $this->assertEquals($expected, $item->toUrl('add-form')->toString());

      // Add page.
      $expected = "/group/{$group->id()}/content/add";
      $this->assertEquals($expected, $item->toUrl('add-page')->toString());

      // Collection.
      $expected = "/group/{$group->id()}/content";
      $this->assertEquals($expected, $item->toUrl('collection')->toString());

      // Create form.
      $expected = "/group/{$group->id()}/content/create/group_membership?group_content={$item->id()}";
      $this->assertEquals($expected, $item->toUrl('create-form')->toString());

      // Create page.
      $expected = "/group/{$group->id()}/content/create?group_content={$item->id()}";
      $this->assertEquals($expected, $item->toUrl('create-page')->toString());

      // Delete form.
      $expected = "/group/{$group->id()}/content/{$item->id()}/delete";
      $this->assertEquals($expected, $item->toUrl('delete-form')->toString());

      // Edit form.
      $expected = "/group/{$group->id()}/content/{$item->id()}/edit";
      $this->assertEquals($expected, $item->toUrl('edit-form')->toString());
    }
  }

}
