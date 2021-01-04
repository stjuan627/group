<?php

namespace Drupal\Tests\group\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the group translate functionality.
 *
 * @group group
 */
class GroupTranslateTest extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'language',
    'content_translation',
  ];

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The group administrator user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * The group member user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $member;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $permissions = $this->getGlobalPermissions();
    $permissions[] = 'administer group';
    $permissions[] = 'administer content translation';

    $this->account = $this->createUser($permissions);
    $this->group = $this->createGroup(['uid' => $this->account->id()]);

    $this->member = $this->createUser([
      'access group overview',
    ]);
    $this->group->addMember($this->member);

    // Add permission to view the group.
    $permissions = [
      'view group',
    ];
    if (!empty($permissions)) {
      $role = $this->group->getGroupType()->getMemberRole();
      $role->grantPermissions($permissions);
      $role->save();
    }

    // Enable additional languages.
    $langcodes = ['es'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Enable translation for default Group and ensure the change is picked up.
    \Drupal::service('content_translation.manager')->setEnabled('group', $this->group->getGroupType()->id(), TRUE);
  }

  /**
   * Tests that a group member has permission translate the group.
   */
  public function testGroupTranslate() {
    // Make sure the Translate page is not available.
    $this->drupalLogin($this->member);
    $this->drupalGet('/group/' . $this->group->id() . '/translations');
    $this->assertSession()->statusCodeEquals(403);

    // Add permission to translate the group.
    $permissions = [
      'view group',
      'translate group',
    ];
    if (!empty($permissions)) {
      $role = $this->group->getGroupType()->getMemberRole();
      $role->grantPermissions($permissions);
      $role->save();
    }

    // Make sure the Translate page is available.
    $this->drupalGet('/group/' . $this->group->id());
    $this->drupalGet('/group/' . $this->group->id() . '/translations');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/group/' . $this->group->id() . '/translations/add/en/es');
    $this->assertSession()->statusCodeEquals(200);

  }

}
