<?php

declare(strict_types=1);

namespace Drupal\Tests\vvjs\FunctionalJavascript;

use Drupal\Tests\vvj_core\FunctionalJavascript\VvjPatternJavascriptTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Browser smoke tests for the vvjs example view custom element.
 */
#[Group('vvjs')]
#[Group('vvj_javascript')]
final class VvjsJavascriptSmokeTest extends VvjPatternJavascriptTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'node',
    'block',
    'views',
    'field',
    'file',
    'filter',
    'text',
    'vvj_core',
    'vvjs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function vvjExampleViewId(): string {
    return 'vvjs_example';
  }

  /**
   * {@inheritdoc}
   */
  protected function vvjCustomElementTag(): string {
    return 'vvjs-slideshow';
  }

  /**
   * The isPaused() pause-state query is exposed on the element + shim.
   *
   * Guards the capability v1 exposed via `getInstance(el).getState().isPaused`
   * and that the v2 single-class consolidation had otherwise dropped. See
   * docs/planning/VVJ-V2-DROPPED-APIS.md. This checks the API surface (not
   * hydration timing), so it is stable.
   */
  public function testIsPausedApiIsExposed(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);

    // The custom element exposes isPaused().
    $this->assertJsCondition(
      "typeof customElements.get('vvjs-slideshow').prototype.isPaused"
      . " === 'function'",
    );

    // The Drupal.vvjs compat shim exposes isPaused() and returns a boolean
    // for a rendered slideshow.
    $this->assertJsCondition(
      "typeof Drupal.vvjs.isPaused(document.querySelector('vvjs-slideshow'))"
      . " === 'boolean'",
    );
  }

}
