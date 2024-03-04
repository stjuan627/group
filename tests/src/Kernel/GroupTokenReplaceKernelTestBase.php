<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Base class for group token replacement tests.
 */
abstract class GroupTokenReplaceKernelTestBase extends GroupKernelTestBase {

  /**
   * The interface language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $interfaceLanguage;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Sets up the test environment before each test method is executed.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->interfaceLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();
  }

}
