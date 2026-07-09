# Changelog

## [2.0.0] — UNRELEASED

**Drop-in upgrade for 1.x sites.** v2 modernizes internals — extends `vvj_core`
foundation, ships `<vvjs-slideshow>` custom element extending `VvjElementBase`,
AbortController-tracked listeners, IntersectionObserver-driven pause-when-out-
of-viewport, full Pointer Events API for swipe — while preserving every CSS
rule, library name (incl. all 5 + 5 breakpoint variants and
admin/opacity/hero/transitions companions), option key, plugin ID, theme hook,
JS behavior key, and the full Drupal.vvjs.* public API surface from 1.x.

### Added

- `drupal/vvj_core` dependency (auto-installed).
- `vvjs.install` with `vvjs_update_10001()` to auto-enable vvj_core during
  `drush updb`.
- `<vvjs-slideshow>` custom element. Lazy IntersectionObserver hydration;
  AbortController-tracked listeners; consolidated single class replacing v1's
  seven-module file split.

### Changed

- Plugin extends `VvjStylePluginBase`. Implements four small subclass hooks;
  standard sections come from the base. 7 form sections (Hero, Responsive,
  Timing, Navigation, Animation, Display, Behavior) — same controls as v1.
- `getDeeplinkReservedWords()` overridden to include vvjs's
  `slideshow`/`slide`/`vvjs`/`vvj` slugs.
- Token resolution delegates to `vvj_core.token_resolver`.
- Hooks moved to OOP `#[Hook]` classes.
- Seven JS modules consolidated into one custom-element class — same behavior,
  single load, no inter-module event-bus indirection. Cross-module communication
  that v1 did via
  `vvjs:slideChanging`/`vvjs:slideChanged`/`vvjs:transitionComplete` etc.
  CustomEvents is now method calls on `this`.
- `Drupal.vvjs.{getInstance,getAllInstances,goToSlide,getCurrentSlide,getTotalSlides,nextSlide,prevSlide,pause,resume,isPaused,pauseAll,resumeAll}`
  now delegate to methods on the custom element instance.
- `getInstance()` returns the `<vvjs-slideshow>` element itself (v1 returned a
  `Slideshow` orchestrator object).

### Fixed

- Regular (non-hero) slideshows again work with **any** row plugin (e.g. an
  `entity:node` teaser), matching v1. The shared base was requiring — and force-
  switching the view to — the Fields row style in every mode; it now only does
  so in hero mode. Fixes the false "vvjs requires Fields as row style" error on
  the shipped `vvjs_example` view, and stops the view editor from overwriting a
  non-Fields row.
- Hero layout fields (max width/height, overlay position/colour/opacity) are now
  hidden via `#states` unless **Enable Hero Slideshow Mode** is checked.
- Restored conditional field visibility (`#states`) the v2 port had dropped:
  Transition Type/Duration show only when Animation is "None" (Duration
  additionally requires a crossfade); Scrollable Dots Width shows only for
  Dots/Numbers nav; Progress Bar, Play/Pause, and Pause-on-Hover disable when
  auto-advance timing is 0.
- Restored the "Transition Types Explained" help block (with the reduced-
  motion/GPU note).
- Restored server-side validation: Progress Bar / Play-Pause are rejected when
  auto-advance timing is 0; Scrollable Dots Width must be 0 or within [MIN,
  MAX].
- Restored the flatten reset that forces Transition Type back to Instant when a
  slide animation is active — so a crossfade `data-transition` (and the `vvjs-
  transitions` library) is no longer emitted alongside an animation, matching
  v1.
- Animation dropdown order back to v1 (None, **Zoom, Fade**, …).

### Removed

The seven-module → single-class consolidation dropped v1's orchestrator-only
surfaces. Full migration record + replacements: [docs/planning/VVJ-V2-DROPPED-
APIS.md](../../../docs/planning/VVJ-V2-DROPPED-APIS.md).

- Instance methods `getModule()`, `getAllModules()`, `getState()`,
  `reinitialize()`, `isInitialized()`, `destroy()`, `updateConfig()` — no
  modules exist; use the element's direct methods, the automatic
  hydration/disconnect lifecycle, and re-render the View to change config.
- `vvjs:*` CustomEvents on the container (internal inter-module signals) — the
  element emits `vvj:ready` on `document` via `VvjElementBase` instead.
- **Restored**: `isPaused()` and `isInitialized()` on the element +
  `Drupal.vvjs.isPaused()` / `Drupal.vvjs.isInitialized()` — the pause-state and
  init-state queries v1 exposed via `getState().isPaused` /
  `getInstance(el).isInitialized()`. Plus a `vvj:slideChanged` CustomEvent on
  `document` (detail: `slide`, `previousSlide`, `total`) restoring v1's per-
  slide observability.

### Drupal core / PHP

- Requires Drupal `^11.3 || ^12`, PHP `>=8.3`.
