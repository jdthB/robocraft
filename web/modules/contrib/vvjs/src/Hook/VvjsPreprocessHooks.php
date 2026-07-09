<?php

declare(strict_types=1);

namespace Drupal\vvjs\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Hook\ViewsThemeHooks;
use Drupal\views\ViewExecutable;
use Drupal\vvjs\Plugin\views\style\Slideshow;
use Drupal\vvjs\VvjsConstants;

/**
 * Preprocess hooks for VVJS slideshow.
 *
 * Derives the rgba hero overlay from hex+opacity, rewrites row theme
 * suggestions, adds the legacy `vvj-slideshow` class on the views_view
 * wrapper for v1 contract preservation.
 */
final class VvjsPreprocessHooks {

  /**
   * Constructor.
   *
   * @param \Drupal\views\Hook\ViewsThemeHooks $viewsThemeHooks
   *   Views row / field preprocess (replaces removed procedural
   *   template_preprocess_* in Drupal 12).
   */
  public function __construct(
    private readonly ViewsThemeHooks $viewsThemeHooks,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for views_view_vvjs.
   *
   * @param array<string, mixed> $variables
   *   Template variables.
   */
  #[Hook('preprocess_views_view_vvjs')]
  public function preprocessViewsViewVvjs(array &$variables): void {
    $view = $variables['view'] ?? NULL;
    if (!($view instanceof ViewExecutable)) {
      return;
    }
    $handler = $view->style_plugin;
    if (!($handler instanceof Slideshow)) {
      return;
    }
    $options = $handler->options;
    $variables['options'] = $options;

    // Derive RGBA hero overlay from hex color + opacity.
    if (!empty($options['hero_slideshow']) && !empty($options['overlay_bg_color'])) {
      $color = $options['overlay_bg_color'];
      $rgb = self::hexToRgb(is_scalar($color) ? (string) $color : '');
      $rawOpacity = $options['overlay_bg_opacity'] ?? VvjsConstants::MAX_OPACITY;
      $opacity = is_numeric($rawOpacity)
        ? (float) $rawOpacity
        : VvjsConstants::MAX_OPACITY;
      $variables['background_rgb'] = sprintf('rgba(%d, %d, %d, %s)', $rgb['r'], $rgb['g'], $rgb['b'], $opacity);
    }
    else {
      $variables['background_rgb'] = NULL;
    }

    // Rewrite row theme suggestions.
    $rows = $variables['rows'] ?? NULL;
    if (is_array($rows)) {
      foreach ($rows as $key => $row) {
        if (!is_array($row) || !isset($row['#theme']) || !is_array($row['#theme'])) {
          continue;
        }
        foreach ($row['#theme'] as $idx => $hook) {
          if (is_string($hook)) {
            $row['#theme'][$idx] = str_replace(
              'views_view_fields',
              'views_view_vvjs_fields',
              $hook,
            );
          }
        }
        $rows[$key] = $row;
      }
      $variables['rows'] = $rows;
    }

    $this->viewsThemeHooks->preprocessViewsViewUnformatted($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for views_view_vvjs_fields.
   *
   * @param array<string, mixed> $variables
   *   Template variables.
   */
  #[Hook('preprocess_views_view_vvjs_fields')]
  public function preprocessViewsViewVvjsFields(array &$variables): void {
    $this->viewsThemeHooks->preprocessViewsViewFields($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for views_view.
   *
   * Adds the legacy `vvj-slideshow` class for v1 contract preservation.
   *
   * @param array<string, mixed> $variables
   *   Template variables.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(array &$variables): void {
    $view = $variables['view'] ?? NULL;
    if (!($view instanceof ViewExecutable)) {
      return;
    }
    if (!($view->style_plugin instanceof Slideshow)) {
      return;
    }
    $attributes = $variables['attributes'] ?? NULL;
    if (is_array($attributes)) {
      $classes = $attributes['class'] ?? [];
      if (!is_array($classes)) {
        $classes = [];
      }
      $classes[] = 'vvj-slideshow';
      $attributes['class'] = $classes;
      $variables['attributes'] = $attributes;
    }
  }

  /**
   * Convert a hex color to an RGB triplet.
   *
   * @param string $hex
   *   Hex color, with or without leading `#`. Accepts 3 or 6 chars.
   *
   * @return array{r: int, g: int, b: int}
   *   Decimal RGB components.
   */
  private static function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
      'r' => (int) hexdec(substr($hex, 0, 2)),
      'g' => (int) hexdec(substr($hex, 2, 2)),
      'b' => (int) hexdec(substr($hex, 4, 2)),
    ];
  }

}
