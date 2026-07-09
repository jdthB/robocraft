<?php

declare(strict_types=1);

namespace Drupal\vvjs\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\vvj_core\Constants\AnimationDirection;
use Drupal\vvj_core\Plugin\views\style\VvjStylePluginBase;
use Drupal\vvjs\VvjsConstants;

/**
 * Renders Views rows as a vanilla-JavaScript slideshow.
 *
 * Supports two modes (regular slideshow + hero slideshow), four
 * transition types (instant + 3 crossfade variants), arrows, dots,
 * numbers, play/pause, progress bar, slide counter, deep linking,
 * keyboard, swipe, pause-on-hover.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: 'views_vvjs',
  title: new TranslatableMarkup('Views Vanilla JavaScript Slideshow'),
  help: new TranslatableMarkup('Render items in a Slideshow using vanilla JavaScript.'),
  theme: 'views_view_vvjs',
  display_types: ['normal'],
)]
class Slideshow extends VvjStylePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getModuleSlug(): string {
    return 'vvjs';
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomElementTag(): string {
    return 'vvjs-slideshow';
  }

  /**
   * Restores v1's slideshow-specific "Enable CSS Library" wording.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getEnableCssDescription(): TranslatableMarkup {
    return $this->t(
      'Include the default CSS library for slideshow styling. Disable if you want to provide custom styles.',
    );
  }

  /**
   * Restores v1's slideshow-specific "Enable Deep Linking" wording.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getDeeplinkEnableDescription(): TranslatableMarkup {
    return $this->t(
      'Enable deep linking to create shareable URLs for specific slides. <strong>Note: This feature requires navigation (dots or numbers) to be enabled.</strong>',
    );
  }

  /**
   * Restores v1's slideshow-specific "URL Identifier" wording.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getDeeplinkIdentifierDescription(): TranslatableMarkup {
    return $this->t(
      'Short identifier used in slide links. Example: "gallery" creates links like #gallery-3. Will be automatically cleaned: converted to lowercase, spaces become hyphens, special characters removed.',
    );
  }

  /**
   * {@inheritdoc}
   *
   * Vvjs deeplink fragments use `#{identifier}-{n}` (no module prefix).
   *
   * @return list<string>
   *   Reserved words that conflict with vvjs's deeplink URL shape.
   */
  protected function getDeeplinkReservedWords(): array {
    return VvjsConstants::DEEPLINK_RESERVED_WORDS;
  }

  /**
   * {@inheritdoc}
   *
   * Vvjs offers the standard 7 animations from vvj_core (None / Fade /
   * Zoom / four slide directions).
   */
  protected function getAnimationPresets(): array {
    return [
      AnimationDirection::NONE => $this->t('None'),
      AnimationDirection::ZOOM => $this->t('Zoom'),
      AnimationDirection::FADE => $this->t('Fade'),
      AnimationDirection::TOP => $this->t('Slide from Top'),
      AnimationDirection::BOTTOM => $this->t('Slide from Bottom'),
      AnimationDirection::LEFT => $this->t('Slide from Left'),
      AnimationDirection::RIGHT => $this->t('Slide from Right'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function definePatternOptions(array $options): array {
    $options['time_in_seconds'] = ['default' => VvjsConstants::TIMING_DEFAULT];
    $options['navigation'] = ['default' => VvjsConstants::NAV_DOTS];
    $options['animation'] = ['default' => VvjsConstants::DEFAULT_ANIMATION];
    $options['transition_type'] = ['default' => VvjsConstants::TRANSITION_INSTANT];
    $options['transition_duration'] = ['default' => VvjsConstants::TRANSITION_DURATION_DEFAULT];
    $options['arrows'] = ['default' => VvjsConstants::ARROWS_TOP];
    $options['hero_slideshow'] = ['default' => FALSE];
    $options['overlay_bg_color'] = ['default' => '#000000'];
    $options['overlay_bg_opacity'] = ['default' => 0.3];
    $options['available_breakpoints'] = ['default' => VvjsConstants::DEFAULT_BREAKPOINT];
    $options['min_height'] = ['default' => VvjsConstants::DEFAULT_MIN_HEIGHT];
    $options['max_content_width'] = ['default' => VvjsConstants::DEFAULT_CONTENT_WIDTH];
    $options['max_width'] = ['default' => VvjsConstants::DEFAULT_MAX_WIDTH];
    $options['overlay_position'] = ['default' => VvjsConstants::OVERLAY_MIDDLE];
    $options['show_total_slides'] = ['default' => FALSE];
    $options['show_slide_progress'] = ['default' => FALSE];
    $options['show_play_pause'] = ['default' => TRUE];
    $options['pause_on_hover'] = ['default' => TRUE];
    $options['enable_swipe'] = ['default' => TRUE];
    $options['enable_keyboard'] = ['default' => TRUE];
    $options['enable_looping'] = ['default' => TRUE];
    $options['start_index'] = ['default' => 1];
    $options['scrollable_dots_width'] = ['default' => VvjsConstants::DEFAULT_SCROLLABLE_DOTS_WIDTH];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPatternSections(array &$form): void {
    $this->buildHeroSection($form);
    $this->buildResponsiveSection($form);
    $this->buildTimingSection($form);
    $this->buildNavigationSection($form);
    $this->buildAnimationSection($form);
    $this->buildDisplaySection($form);
    $this->buildBehaviorSection($form);
  }

  /**
   * Hero slideshow section — toggle + layout.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildHeroSection(array &$form): void {
    $form['hero_slideshow_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Hero Slideshow Settings'),
      '#open' => FALSE,
      '#weight' => -45,
    ];

    $form['hero_slideshow_section']['hero_slideshow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hero Slideshow'),
      '#default_value' => $this->options['hero_slideshow'] ?? FALSE,
      '#description' => $this->t('Enable this option to create a Hero Slideshow. A Hero Slideshow is a prominent, full-width slideshow often used at the top of a webpage to showcase key content or visuals. It typically features large images with overlaying text or buttons. Note: This requires the row style to be set and the first field in the row to be an Image or Media field. Additional configuration options will be available once this option is enabled.'),
    ];

    // Hero layout options apply only in hero mode — keep them hidden until
    // the toggle is checked (conditional field visibility, not CSS).
    $hero_visible = [
      'visible' => [
        ':input[name="style_options[hero_slideshow_section][hero_slideshow]"]' => ['checked' => TRUE],
      ],
    ];

    $form['hero_slideshow_section']['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Width (px)'),
      '#default_value' => $this->options['max_width'] ?? VvjsConstants::DEFAULT_MAX_WIDTH,
      '#min' => VvjsConstants::VIEWS_MIN_WIDTH,
      '#max' => VvjsConstants::VIEWS_MAX_WIDTH,
      '#description' => $this->t('Defines the maximum width for the main container of the hero content, typically set in pixels.'),
      '#states' => $hero_visible,
    ];

    $form['hero_slideshow_section']['min_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Height (vw)'),
      '#default_value' => $this->options['min_height'] ?? VvjsConstants::DEFAULT_MIN_HEIGHT,
      '#min' => VvjsConstants::VIEWS_MIN_HEIGHT,
      '#max' => VvjsConstants::VIEWS_MAX_HEIGHT,
      '#description' => $this->t('Specifies the minimum height for the entire hero container, set in viewport width units (vw).'),
      '#states' => $hero_visible,
    ];

    $form['hero_slideshow_section']['max_content_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Content Width (%)'),
      '#default_value' => $this->options['max_content_width'] ?? VvjsConstants::DEFAULT_CONTENT_WIDTH,
      '#min' => VvjsConstants::VIEWS_MIN_CONTENT_WIDTH,
      '#max' => VvjsConstants::VIEWS_MAX_CONTENT_WIDTH,
      '#description' => $this->t('Determines the width for the remaining fields within the hero section.'),
      '#states' => $hero_visible,
    ];

    $form['hero_slideshow_section']['overlay_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Position'),
      '#default_value' => $this->options['overlay_position'] ?? VvjsConstants::OVERLAY_MIDDLE,
      '#options' => [
        VvjsConstants::OVERLAY_FULL => $this->t('Full'),
        VvjsConstants::OVERLAY_MIDDLE => $this->t('Middle'),
        VvjsConstants::OVERLAY_LEFT => $this->t('Left'),
        VvjsConstants::OVERLAY_RIGHT => $this->t('Right'),
        VvjsConstants::OVERLAY_TOP => $this->t('Top'),
        VvjsConstants::OVERLAY_BOTTOM => $this->t('Bottom'),
        VvjsConstants::OVERLAY_TOP_LEFT => $this->t('Top Left'),
        VvjsConstants::OVERLAY_TOP_RIGHT => $this->t('Top Right'),
        VvjsConstants::OVERLAY_BOTTOM_LEFT => $this->t('Bottom Left'),
        VvjsConstants::OVERLAY_BOTTOM_RIGHT => $this->t('Bottom Right'),
        VvjsConstants::OVERLAY_TOP_MIDDLE => $this->t('Top Middle'),
        VvjsConstants::OVERLAY_BOTTOM_MIDDLE => $this->t('Bottom Middle'),
      ],
      '#description' => $this->t('Select the position where the content overlay will appear within the hero section.'),
      '#states' => $hero_visible,
    ];

    $form['hero_slideshow_section']['overlay_bg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Overlay Background Color'),
      '#default_value' => $this->options['overlay_bg_color'] ?? '#000000',
      '#description' => $this->t('Choose the background color for the overlay that appears behind the content within the hero section. This helps improve the readability of the overlay content.'),
      '#states' => $hero_visible,
    ];

    $rawOpacity = $this->options['overlay_bg_opacity'] ?? 0.3;
    $opacityDisplay = is_scalar($rawOpacity) ? (string) $rawOpacity : '0.3';
    $form['hero_slideshow_section']['overlay_bg_opacity'] = [
      '#type' => 'range',
      '#title' => $this->t('Overlay Background Opacity'),
      '#default_value' => $this->options['overlay_bg_opacity'] ?? 0.3,
      '#description' => $this->t('Adjust the opacity of the overlay background color for the hero section content. A lower value makes the background more transparent, while a higher value makes it more opaque.'),
      '#min' => VvjsConstants::MIN_OPACITY,
      '#max' => VvjsConstants::MAX_OPACITY,
      '#step' => VvjsConstants::OPACITY_STEP,
      // Use #field_suffix (rendered INSIDE the form-item wrapper) not #suffix
      // (rendered outside it) so the readout hides with the field's #states.
      '#field_suffix' => Markup::create('<span id="background-opacity-value" class="opacity-value">' . $opacityDisplay . '</span>'),
      '#attributes' => [
        'oninput' => 'document.getElementById("background-opacity-value").innerText = this.value;',
      ],
      '#states' => $hero_visible,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Regular slideshows render any row plugin; only hero mode — where the
   * first field is the background image and the rest are overlay content —
   * requires the Fields row style. Without this override the shared base
   * would force Fields (and reject non-Fields Views) in every mode, which
   * v1 never did.
   */
  protected function requiresFieldsRow(): bool {
    return !empty($this->options['hero_slideshow']);
  }

  /**
   * {@inheritdoc}
   *
   * @return array<int, string|\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Validation error messages.
   */
  public function validate(): array {
    $errors = parent::validate();

    // Hero mode additionally needs at least one configured field; the base
    // already flags a missing Fields row via requiresFieldsRow().
    if (!empty($this->options['hero_slideshow']) && $this->usesFields()) {
      $fields = $this->view->display_handler->getHandlers('field');
      if (empty($fields)) {
        $errors[] = $this->t(
          'Hero Slideshow requires at least one field to be configured.',
        );
      }
    }

    return $errors;
  }

  /**
   * Responsive section — breakpoint.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildResponsiveSection(array &$form): void {
    $form['responsive_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsive Settings'),
      '#open' => FALSE,
      '#weight' => -40,
    ];

    $form['responsive_section']['available_breakpoints'] = [
      '#type' => 'select',
      '#title' => $this->t('Responsive breakpoint'),
      '#options' => [
        '576' => $this->t('576 px / 36 rem'),
        '768' => $this->t('768 px / 48 rem'),
        '992' => $this->t('992 px / 62 rem'),
        '1200' => $this->t('1200 px / 75 rem'),
        '1400' => $this->t('1400 px / 87.5 rem'),
      ],
      '#default_value' => $this->options['available_breakpoints'] ?? VvjsConstants::DEFAULT_BREAKPOINT,
      '#description' => $this->t('Select the viewport width at which the slideshow switches to its compact responsive layout.'),
    ];
  }

  /**
   * Timing section — auto-rotate interval.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildTimingSection(array &$form): void {
    $form['timing_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Timing Settings'),
      '#open' => TRUE,
      '#weight' => -30,
    ];

    $form['timing_section']['time_in_seconds'] = [
      '#type' => 'number',
      '#title' => $this->t('Auto-rotate Interval (ms)'),
      '#default_value' => $this->options['time_in_seconds'] ?? VvjsConstants::TIMING_DEFAULT,
      '#min' => VvjsConstants::TIMING_DISABLED,
      '#max' => VvjsConstants::TIMING_MAX,
      '#step' => 100,
      '#description' => $this->t('Time per slide in milliseconds. 0 disables autoplay; otherwise minimum @min ms.', [
        '@min' => VvjsConstants::TIMING_MIN_ACTIVE,
      ]),
    ];
  }

  /**
   * Navigation section — arrows, dots/numbers, scrollable-dots width.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildNavigationSection(array &$form): void {
    $form['navigation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation Settings'),
      '#open' => TRUE,
      '#weight' => -25,
    ];

    $form['navigation_section']['arrows'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Navigation Arrows'),
      '#default_value' => $this->options['arrows'] ?? VvjsConstants::ARROWS_TOP,
      '#options' => [
        VvjsConstants::ARROWS_NONE => $this->t('None'),
        VvjsConstants::ARROWS_SIDES => $this->t('Sides'),
        VvjsConstants::ARROWS_SIDES_BIG => $this->t('Sides (Big)'),
        VvjsConstants::ARROWS_TOP => $this->t('Top'),
        VvjsConstants::ARROWS_TOP_BIG => $this->t('Top (Big)'),
      ],
      '#description' => $this->t('Side arrows appear beside the slide. Top arrows appear above the slide with low opacity (0.3) and become fully visible on hover. Options marked "big screen only" will only display on screens wider than the selected breakpoint.'),
    ];

    $form['navigation_section']['navigation'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Indicators (Bottom Navigation Dots/Numbers)'),
      '#default_value' => $this->options['navigation'] ?? VvjsConstants::NAV_DOTS,
      '#options' => [
        VvjsConstants::NAV_NONE => $this->t('None'),
        VvjsConstants::NAV_DOTS => $this->t('Dots'),
        VvjsConstants::NAV_NUMBERS => $this->t('Numbers'),
      ],
      '#description' => $this->t('Show the bottom slide navigation dots/numbers. <strong>Note: This feature is required by Deep Linking.</strong>'),
    ];

    $form['navigation_section']['scrollable_dots_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Scrollable navigation width (px)'),
      '#default_value' => $this->options['scrollable_dots_width'] ?? VvjsConstants::DEFAULT_SCROLLABLE_DOTS_WIDTH,
      '#min' => 0,
      '#max' => VvjsConstants::MAX_SCROLLABLE_DOTS_WIDTH,
      '#step' => 1,
      '#field_suffix' => 'px',
      '#description' => $this->t('Set a maximum width for the navigation to make it scrollable. Set to 0 to disable. Range: @min-@max px.', [
        '@min' => VvjsConstants::MIN_SCROLLABLE_DOTS_WIDTH,
        '@max' => VvjsConstants::MAX_SCROLLABLE_DOTS_WIDTH,
      ]),
      // Only meaningful for dots/numbers nav — hidden otherwise (v1 parity).
      '#states' => [
        'visible' => [
          [':input[name="style_options[navigation_section][navigation]"]' => ['value' => VvjsConstants::NAV_DOTS]],
          [':input[name="style_options[navigation_section][navigation]"]' => ['value' => VvjsConstants::NAV_NUMBERS]],
        ],
      ],
    ];
  }

  /**
   * Animation section — animation + transition.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildAnimationSection(array &$form): void {
    $form['animation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation Settings'),
      '#open' => TRUE,
      '#weight' => -20,
    ];

    $animation_select = $this->buildAnimationSelect('animation');
    $animation_select['#title'] = $this->t('Slide Animation Type');
    $animation_select['#description'] = $this->t('Choose the animation type for the slides. When set to "None", transition options become available. Reduced-motion users see no animation regardless.');
    $form['animation_section']['animation'] = $animation_select;

    // Transitions apply only when there is no slide-in animation; the
    // duration + help further require a crossfade transition (v1 parity).
    $transition_active_state = [
      'visible' => [
        ':input[name="style_options[animation_section][animation]"]' => ['value' => AnimationDirection::NONE],
        ':input[name="style_options[animation_section][transition_type]"]' => [
          ['value' => VvjsConstants::TRANSITION_CROSSFADE_CLASSIC],
          ['value' => VvjsConstants::TRANSITION_CROSSFADE_STAGED],
          ['value' => VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC],
        ],
      ],
    ];

    $form['animation_section']['transition_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Type'),
      '#default_value' => $this->options['transition_type'] ?? VvjsConstants::TRANSITION_INSTANT,
      '#options' => [
        VvjsConstants::TRANSITION_INSTANT => $this->t('Instant'),
        VvjsConstants::TRANSITION_CROSSFADE_CLASSIC => $this->t('Crossfade (Classic)'),
        VvjsConstants::TRANSITION_CROSSFADE_STAGED => $this->t('Crossfade (Staged)'),
        VvjsConstants::TRANSITION_CROSSFADE_DYNAMIC => $this->t('Crossfade (Dynamic)'),
      ],
      '#description' => $this->t('Select the transition effect between slides. Available only when Slide Animation Type is set to "None". How the slide swap is rendered. Instant uses display:none toggling; crossfade variants use opacity layering.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[animation_section][animation]"]' => ['value' => AnimationDirection::NONE],
        ],
      ],
    ];

    $form['animation_section']['transition_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Transition Duration'),
      '#default_value' => $this->options['transition_duration'] ?? VvjsConstants::TRANSITION_DURATION_DEFAULT,
      '#min' => VvjsConstants::TRANSITION_DURATION_MIN,
      '#max' => VvjsConstants::TRANSITION_DURATION_MAX,
      '#description' => $this->t('Duration of the crossfade transition in milliseconds. Recommended: 400-800ms. Ignored when transition is Instant.'),
      '#states' => $transition_active_state,
    ];

    $form['animation_section']['transition_help'] = [
      '#type' => 'item',
      '#markup' => $this->t('<div class="vvjs-transitions-help"><strong>Transition Types Explained:</strong><ul>
        <li><strong>Instant:</strong> No transition effect (default, backward compatible)</li>
        <li><strong>Crossfade - Classic:</strong> Both slides fade at the same speed simultaneously (most common)</li>
        <li><strong>Crossfade - Staged:</strong> Outgoing fades quickly, incoming fades slowly with overlap (elegant, smooth)</li>
        <li><strong>Crossfade - Dynamic:</strong> Fast fade-out, slow fade-in (energetic, attention-grabbing)</li>
      </ul>
      <p><strong>Performance Note:</strong> All crossfade effects use GPU-accelerated CSS transitions. Users with "prefers-reduced-motion" enabled will automatically see instant transitions.</p>
      </div>'),
      '#states' => $transition_active_state,
    ];
  }

  /**
   * Display options section — counter, progress bar, play/pause.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildDisplaySection(array &$form): void {
    $form['display_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => TRUE,
      '#weight' => -15,
    ];

    $form['display_section']['show_total_slides'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Total Slide Number'),
      '#default_value' => $this->options['show_total_slides'] ?? FALSE,
      '#description' => $this->t('Enable this option to display the total number of slides in the slideshow. For example, "Slide 1 of 5".'),
    ];

    // Progress bar + play/pause only make sense with auto-advance on
    // (v1 disabled them when timing is 0).
    $timing_enabled_state = [
      'enabled' => [
        ':input[name="style_options[timing_section][time_in_seconds]"]' => ['!value' => '0'],
      ],
    ];

    $form['display_section']['show_slide_progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Animation Progress'),
      '#default_value' => $this->options['show_slide_progress'] ?? FALSE,
      '#description' => $this->t('Enable this option to display a circular animation indicator that updates with each slide change. The animation duration matches the slide transition time. (Time In Seconds >= 2 s)'),
      '#states' => $timing_enabled_state,
    ];

    $form['display_section']['show_play_pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Play/Pause Button'),
      '#default_value' => $this->options['show_play_pause'] ?? TRUE,
      '#description' => $this->t('Enable this option to show a play/pause button at the bottom of the slideshow. (Time In Seconds >= 2 s)'),
      '#states' => $timing_enabled_state,
    ];
  }

  /**
   * Behavior section — hover/swipe/keyboard/looping/start.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildBehaviorSection(array &$form): void {
    $form['behavior_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Behavior Settings'),
      '#open' => TRUE,
      '#weight' => -10,
    ];

    // Pause-on-hover only applies while auto-advancing (v1 disabled it
    // when timing is 0).
    $timing_enabled_state = [
      'enabled' => [
        ':input[name="style_options[timing_section][time_in_seconds]"]' => ['!value' => '0'],
      ],
    ];

    $form['behavior_section']['pause_on_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on Hover'),
      '#default_value' => $this->options['pause_on_hover'] ?? TRUE,
      '#description' => $this->t('Pause the slideshow when the mouse hovers over it. Uncheck to keep the slideshow running on hover.'),
      '#states' => $timing_enabled_state,
    ];

    $form['behavior_section']['enable_swipe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Touch/Swipe Gestures'),
      '#default_value' => $this->options['enable_swipe'] ?? TRUE,
      '#description' => $this->t('Allow users to navigate slides using touch swipe gestures on mobile devices.'),
    ];

    $form['behavior_section']['enable_keyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Keyboard Navigation'),
      '#default_value' => $this->options['enable_keyboard'] ?? TRUE,
      '#description' => $this->t('Allow users to navigate slides using keyboard arrow keys, Space to pause/play, Home/End to jump to first/last slide.'),
    ];

    $form['behavior_section']['enable_looping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Looping'),
      '#default_value' => $this->options['enable_looping'] ?? TRUE,
      '#description' => $this->t('When enabled, the slideshow will loop back to the first slide after the last slide. When disabled, it will stop at the last slide.'),
    ];

    $form['behavior_section']['start_index'] = [
      '#type' => 'number',
      '#title' => $this->t('Start Index'),
      '#default_value' => $this->options['start_index'] ?? 1,
      '#min' => 1,
      '#description' => $this->t('Choose which slide the slideshow should display first when it loads. For example, enter 1 to start with the first slide, 2 for the second, etc. This is useful when you have multiple slideshows side by side and want each to start at a different position for a staggered effect. If the number exceeds the total slides, the slideshow will automatically start from the last slide.'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Add the breakpoint-specific responsive library on top of the
   * shared library list. Hero mode adds the hero variant and its
   * breakpoint-specific overrides.
   *
   * @return list<string>
   *   Library identifiers.
   */
  protected function buildLibraryList(): array {
    $libraries = parent::buildLibraryList();
    $rawBp = $this->options['available_breakpoints'] ?? VvjsConstants::DEFAULT_BREAKPOINT;
    $bp = is_scalar($rawBp) ? (string) $rawBp : VvjsConstants::DEFAULT_BREAKPOINT;
    $libraries[] = 'vvjs/vvjs__' . $bp;

    if (!empty($this->options['hero_slideshow'])) {
      $libraries[] = 'vvjs/vvjs-hero';
      $libraries[] = 'vvjs/vvjs-hero__' . $bp;
    }

    if (
      ($this->options['transition_type'] ?? VvjsConstants::TRANSITION_INSTANT)
      !== VvjsConstants::TRANSITION_INSTANT
    ) {
      $libraries[] = 'vvjs/vvjs-transitions';
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);

    $values = $form_state->getValue('style_options');
    if (!is_array($values)) {
      return;
    }

    // time_in_seconds: 0 disables, otherwise minimum 2000ms.
    $timing = $values['timing_section'] ?? NULL;
    $rawTime = is_array($timing)
      ? ($timing['time_in_seconds'] ?? VvjsConstants::TIMING_DEFAULT)
      : VvjsConstants::TIMING_DEFAULT;
    $time = is_numeric($rawTime) ? (int) $rawTime : VvjsConstants::TIMING_DEFAULT;
    if ($time !== VvjsConstants::TIMING_DISABLED && $time < VvjsConstants::TIMING_MIN_ACTIVE) {
      $timing_form = $form['timing_section'] ?? NULL;
      $time_el = is_array($timing_form) ? ($timing_form['time_in_seconds'] ?? NULL) : NULL;
      if (is_array($time_el)) {
        $form_state->setError(
          $time_el,
          $this->t('Auto-rotate must be 0 (disabled) or at least @min ms.', [
            '@min' => VvjsConstants::TIMING_MIN_ACTIVE,
          ]),
        );
      }
    }

    // Cross-field rule: deep linking requires dots or numbers nav.
    $deeplink_values = $form_state->getValue(['style_options', 'deeplink_section']);
    $nav_values = $form_state->getValue(['style_options', 'navigation_section']);
    if (is_array($deeplink_values) && !empty($deeplink_values['enable_deeplink'])) {
      $nav = is_array($nav_values)
        ? ($nav_values['navigation'] ?? VvjsConstants::NAV_DOTS)
        : VvjsConstants::NAV_DOTS;
      if ($nav === VvjsConstants::NAV_NONE) {
        $deeplink_form = $form['deeplink_section'] ?? NULL;
        $deeplink_el = is_array($deeplink_form) ? ($deeplink_form['enable_deeplink'] ?? NULL) : NULL;
        if (is_array($deeplink_el)) {
          $form_state->setError(
            $deeplink_el,
            $this->t('Deep linking requires Bottom Navigation to be Dots or Numbers.'),
          );
        }
      }
    }

    // v1: progress bar / play-pause require auto-advance timing enabled.
    $display_values = $values['display_section'] ?? NULL;
    if ($time === VvjsConstants::TIMING_DISABLED && is_array($display_values)) {
      $display_form = $form['display_section'] ?? NULL;
      if (!empty($display_values['show_slide_progress']) && is_array($display_form)) {
        $el = $display_form['show_slide_progress'] ?? NULL;
        if (is_array($el)) {
          $form_state->setError($el, $this->t('Slide progress requires auto-advance timing to be enabled.'));
        }
      }
      if (!empty($display_values['show_play_pause']) && is_array($display_form)) {
        $el = $display_form['show_play_pause'] ?? NULL;
        if (is_array($el)) {
          $form_state->setError($el, $this->t('Play/pause button requires auto-advance timing to be enabled.'));
        }
      }
    }

    // v1: scrollable dots width must be 0 (disabled) or within [MIN, MAX].
    $width_section = $values['navigation_section'] ?? NULL;
    $raw_width = is_array($width_section) ? ($width_section['scrollable_dots_width'] ?? 0) : 0;
    $width = is_numeric($raw_width) ? (int) $raw_width : 0;
    if ($width > 0 && ($width < VvjsConstants::MIN_SCROLLABLE_DOTS_WIDTH || $width > VvjsConstants::MAX_SCROLLABLE_DOTS_WIDTH)) {
      $nav_form = $form['navigation_section'] ?? NULL;
      $width_el = is_array($nav_form) ? ($nav_form['scrollable_dots_width'] ?? NULL) : NULL;
      if (is_array($width_el)) {
        $form_state->setError($width_el, $this->t('Scrollable navigation width must be 0 (disabled) or between @min and @max pixels.', [
          '@min' => VvjsConstants::MIN_SCROLLABLE_DOTS_WIDTH,
          '@max' => VvjsConstants::MAX_SCROLLABLE_DOTS_WIDTH,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('style_options');
    if (!is_array($values)) {
      $values = [];
    }
    $form_state->setValue('style_options', $this->flattenFormValues($values));
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Flatten the sectioned form values back into the persisted shape.
   *
   * @param array<int|string, mixed> $values
   *   Submitted form values keyed by section then by option name.
   *
   * @return array<string, mixed>
   *   Flattened option array.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    foreach (
      [
        'hero_slideshow_section',
        'responsive_section',
        'timing_section',
        'navigation_section',
        'animation_section',
        'display_section',
        'behavior_section',
      ] as $section
    ) {
      if (!isset($values[$section]) || !is_array($values[$section])) {
        continue;
      }
      foreach ($values[$section] as $key => $value) {
        $flattened[(string) $key] = $value;
      }
    }

    // v1 parity: transition values persist only when animation is "none";
    // otherwise force Instant so the template never renders a crossfade
    // data-transition (and vvjs-transitions is never attached) while a slide
    // animation is active.
    $animation_values = $values['animation_section'] ?? NULL;
    if (is_array($animation_values)) {
      $animation = $animation_values['animation'] ?? AnimationDirection::NONE;
      if ($animation !== AnimationDirection::NONE) {
        $flattened['transition_type'] = VvjsConstants::TRANSITION_INSTANT;
        $flattened['transition_duration'] = VvjsConstants::TRANSITION_DURATION_DEFAULT;
      }
    }

    $deeplink = $values['deeplink_section'] ?? NULL;
    if (is_array($deeplink)) {
      $flattened['enable_deeplink'] = $deeplink['enable_deeplink'] ?? FALSE;
      $flattened['deeplink_identifier'] = $deeplink['deeplink_identifier'] ?? '';
    }

    $advanced = $values['advanced_section'] ?? NULL;
    if (is_array($advanced)) {
      $flattened['enable_css'] = $advanced['enable_css'] ?? TRUE;
    }

    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

}
