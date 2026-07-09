<?php

declare(strict_types=1);

namespace Drupal\vvjs;

/**
 * Vvjs-specific constants — slideshow pattern-only values.
 *
 * Cross-cutting constants live in vvj_core's `ValidationBounds` and
 * `AnimationDirection`.
 *
 * @internal Final, non-instantiable.
 */
final class VvjsConstants {

  /**
   * Auto-rotate interval bounds (ms).
   */
  public const int TIMING_DISABLED = 0;
  public const int TIMING_MIN_ACTIVE = 2000;
  public const int TIMING_DEFAULT = 5000;
  public const int TIMING_MAX = 15000;

  /**
   * Animation type values.
   */
  public const string ANIMATION_NONE = 'none';
  public const string ANIMATION_ZOOM = 'a-zoom';
  public const string ANIMATION_FADE = 'a-fade';
  public const string ANIMATION_TOP = 'a-top';
  public const string ANIMATION_BOTTOM = 'a-bottom';
  public const string ANIMATION_LEFT = 'a-left';
  public const string ANIMATION_RIGHT = 'a-right';
  public const string DEFAULT_ANIMATION = self::ANIMATION_BOTTOM;

  /**
   * Transition type values (between-slide visual effect).
   */
  public const string TRANSITION_INSTANT = 'instant';
  public const string TRANSITION_CROSSFADE_CLASSIC = 'crossfade-classic';
  public const string TRANSITION_CROSSFADE_STAGED = 'crossfade-staged';
  public const string TRANSITION_CROSSFADE_DYNAMIC = 'crossfade-dynamic';
  public const int TRANSITION_DURATION_MIN = 200;
  public const int TRANSITION_DURATION_MAX = 2000;
  public const int TRANSITION_DURATION_DEFAULT = 600;

  /**
   * Arrow position values.
   */
  public const string ARROWS_NONE = 'none';
  public const string ARROWS_SIDES = 'arrows-sides';
  public const string ARROWS_SIDES_BIG = 'arrows-sides-big';
  public const string ARROWS_TOP = 'arrows-top';
  public const string ARROWS_TOP_BIG = 'arrows-top-big';

  /**
   * Navigation type values (bottom dots/numbers/none).
   */
  public const string NAV_NONE = 'none';
  public const string NAV_DOTS = 'dots';
  public const string NAV_NUMBERS = 'numbers';

  /**
   * Hero overlay position values.
   */
  public const string OVERLAY_FULL = 'd-full';
  public const string OVERLAY_MIDDLE = 'd-middle';
  public const string OVERLAY_LEFT = 'd-left';
  public const string OVERLAY_RIGHT = 'd-right';
  public const string OVERLAY_TOP = 'd-top';
  public const string OVERLAY_BOTTOM = 'd-bottom';
  public const string OVERLAY_TOP_LEFT = 'd-top-left';
  public const string OVERLAY_TOP_RIGHT = 'd-top-right';
  public const string OVERLAY_BOTTOM_LEFT = 'd-bottom-left';
  public const string OVERLAY_BOTTOM_RIGHT = 'd-bottom-right';
  public const string OVERLAY_TOP_MIDDLE = 'd-top-middle';
  public const string OVERLAY_BOTTOM_MIDDLE = 'd-bottom-middle';

  /**
   * Layout defaults.
   */
  public const int DEFAULT_MAX_WIDTH = 1200;
  public const int DEFAULT_MIN_HEIGHT = 40;
  public const int DEFAULT_CONTENT_WIDTH = 60;

  /**
   * Bounds.
   */
  public const int VIEWS_MIN_HEIGHT = 1;
  public const int VIEWS_MAX_HEIGHT = 200;
  public const int VIEWS_MIN_WIDTH = 1;
  public const int VIEWS_MAX_WIDTH = 9999;
  public const int VIEWS_MIN_CONTENT_WIDTH = 1;
  public const int VIEWS_MAX_CONTENT_WIDTH = 100;
  public const float MIN_OPACITY = 0.0;
  public const float MAX_OPACITY = 1.0;
  public const float OPACITY_STEP = 0.1;

  /**
   * Scrollable-dots bounds.
   */
  public const int DEFAULT_SCROLLABLE_DOTS_WIDTH = 0;
  public const int MIN_SCROLLABLE_DOTS_WIDTH = 120;
  public const int MAX_SCROLLABLE_DOTS_WIDTH = 700;

  /**
   * Reserved deeplink-identifier words specific to vvjs.
   *
   * Conflict with vvjs's URL fragment prefix `#{identifier}-{n}`.
   *
   * @var list<string>
   */
  public const array DEEPLINK_RESERVED_WORDS = ['slideshow', 'slide', 'vvjs', 'vvj'];

  /**
   * Default breakpoint (legacy v1 default = 576).
   */
  public const string DEFAULT_BREAKPOINT = '576';

  /**
   * Prevent instantiation.
   */
  private function __construct() {
  }

}
