# Views Vanilla JavaScript Slideshow (VVJS)

A vanilla-JavaScript slideshow **Views format** for Drupal 11.3+ / 12. The most
fully-featured of the VVJ pattern modules: regular and hero slideshow modes,
four transition types (instant + three crossfade variants), seven animations,
autoplay with full pause-control, arrows, dots, numbers, play/pause, progress
bar, slide counter, deep linking, swipe + keyboard + screen-reader, and AAA-
conformant accessibility.

v2.0 builds on the **`drupal/vvj_core` foundation** (composer auto-installs it).
Behavior runs in a `<vvjs-slideshow>` custom element extending `VvjElementBase`
— v1's seven separate JS files (`slideshow-core`, `-transitions`, `-navigation`,
`-accessibility`, `-progress`, `-visibility`, `-events`, plus the `vvjs-main`
orchestrator) are consolidated into a single class on the lifecycle.

## Features

### Modes
- **Regular slideshow** — standard slide-by-slide content rotation
- **Hero slideshow** — first field as hero background image, remaining fields as
  overlay content; 12 overlay positions; configurable rgba overlay color from
  hex+opacity

### Transitions + animations
- 4 transition types: `instant` / `crossfade-classic` / `crossfade-staged` /
  `crossfade-dynamic`
- Configurable transition duration (200–2000 ms)
- 7 animation directions (None / Fade / Zoom / four slide directions)

### Autoplay + controls
- Configurable interval (0 to disable; otherwise 2000–15000 ms)
- Pause on hover, pause when tab hidden, pause when scrolled out of viewport
  (IntersectionObserver), pause on `prefers-reduced-motion`
- Play/pause button with SVG swap + ARIA label sync
- RAF-quality progress bar (`role="progressbar"`, `--progress` CSS var)
- Slide counter ("X of Y")

### Navigation
- Arrows in 4 positions (Sides / Sides Big / Top / Top Big) or none
- Bottom navigation: dots / numbers / none
- Scrollable dots/numbers variant for large slide counts
- Configurable looping toggle
- Configurable start slide index

### Accessibility
- Pointer Events API with Touch fallback for swipe (RTL-aware)
- Keyboard nav: ArrowLeft/Right, Space (play/pause), Home, End
- Screen-reader announcer via ARIA live region
- ARIA `role="tabpanel"` / `inert` / `aria-hidden` per slide
- `aria-selected` on dots/numbers, focus management on slide change

### Architecture
- 5 responsive breakpoints (576 / 768 / 992 / 1200 / 1400 px), separate hero
  overrides per breakpoint
- Deep linking via URL hash (`#identifier-{n}`)
- Lazy IntersectionObserver hydration; AbortController-tracked listeners
- **Views tokens** — `[vvjs:field]` / `[vvjs:field:plain]` in Views text areas
  with *Use replacement tokens from the first row*
- **Drupal admin help** — `/admin/help/vvjs`

## Token Support in Views Text Areas

In Views headers, footers, or empty text with *Use replacement tokens from the
first row*, default Twig tokens (`{{ title }}`) do not work. Use VVJS tokens
instead:

- `{{ title }}` → `[vvjs:title]`
- `{{ field_image }}` → `[vvjs:field_image]`
- Append `:plain` for plain text: `[vvjs:title:plain]`

v2 resolves tokens through the shared `vvj_core.token_resolver` service; syntax
is unchanged from v1. Tokens read from the **first row** of rendered View
fields. Complex field rewrites are not supported.

## Installation

```bash
composer require drupal/vvjs:^2.0
drush en vvjs
```

## Usage

1. Create or edit a View.
2. Set **Format** to *Views Vanilla JavaScript Slideshow*.
3. Set **Show** to *Fields*.
4. For hero mode, the first field is treated as the hero background; place a
   `<div class="vvjs-separator"></div>` field between background and overlay
   content.
5. Configure timing / navigation / animation / transition / display / behavior
   in the format settings.
6. Save.

**Sample view:** Optional config `views.view.vvjs_example` (`config/optional/`)
installs when there is no ID conflict.

## Public API

External code can drive the slideshow via `Drupal.vvjs.*`. Pass either the
deeplink identifier, a CSS selector, or an `Element` reference:

```js
Drupal.vvjs.goToSlide('gallery', 3);          // jump to slide 3 (1-based)
Drupal.vvjs.nextSlide('gallery');             // advance one slide
Drupal.vvjs.prevSlide('gallery');             // back one slide
Drupal.vvjs.pause('gallery');                 // pause autoplay
Drupal.vvjs.resume('gallery');                // resume autoplay
Drupal.vvjs.isPaused('gallery');              // → boolean|null
Drupal.vvjs.isInitialized('gallery');         // → boolean|null
Drupal.vvjs.pauseAll();                       // pause every slideshow on page
Drupal.vvjs.resumeAll();                      // resume every slideshow on page
Drupal.vvjs.getCurrentSlide('gallery');       // → number (1-based)
Drupal.vvjs.getTotalSlides('gallery');        // → number
Drupal.vvjs.getInstance('#vvjs-12345');       // → <vvjs-slideshow> element
Drupal.vvjs.getAllInstances();                // → array of <vvjs-slideshow>
elements
```

## Drop-in upgrade from 1.x

`composer update drupal/vvjs && drush updb && drush cr` is sufficient. v2
preserves:

- Plugin ID `views_vvjs`, theme hook `views_view_vvjs`
- Twig template names
- All 28 option keys
- Library names (`vvjs`, `vvjs-style`, `vvjs-hero`, `vvjs-transitions`, `vvjs-
  opacity`, `vvjs-admin`, plus 5 + 5 breakpoint variants)
- JS behavior key `Drupal.behaviors.VVJSlideshow`
- Public API surface `Drupal.vvjs.{getInstance, getAllInstances, goToSlide,
  getCurrentSlide, getTotalSlides, nextSlide, prevSlide, pause, resume,
  pauseAll, resumeAll, isPaused, isInitialized}`
- All CSS class names (`.vvjs`, `.vvjs-inner`, `.vvjs-items`, `.vvjs-item`,
  `.vvjs-active`, `.vvjs-previous`, `.vvjs-hero-image`, `.vvjs-hero-content`,
  `.dots-numbers-button-wrapper`, `.dots-numbers-button`, `.play-pause-button`,
  `.next-arrow`, `.prev-arrow`, `.progressbar`, `.announcer`)

Outer rendered tag changed from `<div>` to `<vvjs-slideshow>`. Theme selectors
targeting `.vvjs` keep matching.

## License

GPL-2.0-or-later.
