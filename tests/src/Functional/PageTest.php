<?php

declare(strict_types = 1);

namespace Drupal\Tests\localgov_moderngov\Functional;

use Drupal\user\Entity\Role;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Modern.Gov template page examination.
 */
class PageTest extends BrowserTestBase {

  use PathAliasTestTrait;

  /**
   * Validates a Modern.Gov template page.
   *
   * Things we are validating:
   * - Presence of tokens within the Modern.Gov template page.
   * - Absolute asset URLs.
   */
  public function testModernGovPage() {

    $this->drupalGet("moderngov-template");
    $this->assertSession()->statusCodeEquals(200);

    // Validate presence of ModernGov tokens.
    $this->assertSession()->pageTextContains('{pagetitle}');
    $this->assertSession()->pageTextContains('{content}');
    $this->assertSession()->pageTextContains('{breadcrumb}');
    $this->assertSession()->pageTextContains('{sidenav}');

    // Validate absolute asset URLs.  We are sampling the Favicon URL only.
    $favicon_link_element_list = $this->cssSelect('link[rel="icon"]');
    $favicon_link_element = current($favicon_link_element_list);

    $favicon_url = $favicon_link_element->getAttribute('href');
    $this->assertNotNull($favicon_url);

    $favicon_url_parts = parse_url($favicon_url);
    $favicon_url_has_hostname = array_key_exists('host', $favicon_url_parts);
    $favicon_url_is_absolute  = $favicon_url_has_hostname;
    $this->assertTrue($favicon_url_is_absolute);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $anonymous_role = Role::load('anonymous');
    $anonymous_role->grantPermission('access content');
    $anonymous_role->save();
  }

  /**
   * Theme used during testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'localgov_moderngov',
  ];

}
