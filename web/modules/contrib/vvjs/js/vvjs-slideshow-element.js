/**
 * @file
 * VvjsSlideshowElement — `<vvjs-slideshow>` custom element.
 *
 * Consolidates v1's seven modular JS files (slideshow-core /
 * -transitions / -navigation / -accessibility / -progress /
 * -visibility / -events) into a single class on the
 * `Drupal.Vvj.ElementBase` lifecycle. The base class is pulled from
 * the `Drupal.Vvj` namespace — Drupal core's cross-file sharing
 * pattern — with load order guaranteed by the `vvjs` library's
 * `dependencies:`. All v1 behavior preserved verbatim:
 *
 *   - Regular + hero slideshow modes
 *   - 4 transition types (instant + 3 crossfade variants)
 *   - 7 animation directions (matches vvj_core AnimationDirection)
 *   - Autoplay with pause-on-hover, pause-when-tab-hidden,
 *     pause-when-out-of-viewport (IntersectionObserver), reduced-motion
 *   - Arrows + dots/numbers nav with scrollable variant
 *   - Play/pause button + slide counter + RAF-quality progress bar
 *   - Pointer Events (with Touch fallback) for swipe — non-passive
 *     pointermove allows vertical scroll preservation
 *   - Keyboard navigation: Arrow keys, Space, Home, End
 *   - Screen-reader announcer
 *   - Deep linking via URL hash (#{identifier}-{n})
 *   - Looping toggle, configurable start index
 *
 * Public methods (used by the Drupal.vvjs.* compat shim):
 *   - goToSlide(slideIndex: number) → boolean (1-based)
 *   - getCurrentSlide() → number
 *   - getTotalSlides() → number
 *   - nextSlide() → boolean
 *   - prevSlide() → boolean
 *   - pause() → boolean
 *   - resume() → boolean
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal) => {
  'use strict';

  const { ElementBase } = Drupal.Vvj;

  const PLAY_SVG = `<svg class="svg-play" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor"><path d="m380-300 280-180-280-180v360ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>`;
  const PAUSE_SVG = `<svg class="svg-pause" xmlns="http://www.w3.org/2000/svg" viewBox="80 -880 800 800" fill="currentColor"><path d="M360-320h80v-320h-80v320Zm160 0h80v-320h-80v320ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>`;

  const SWIPE_THRESHOLD = 40;

  function debounce(fn, delay) {
    let timer;
    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => fn(...args), delay);
    };
  }

  class VvjsSlideshowElement extends ElementBase {

    static get patternSlug() {
 return 'vvjs'; }

    constructor() {
      super();
      this._inner = null;
      this._slideshow = null;
      this._slides = [];
      this._totalSlides = 0;
      this._slideIndex = 1;
      this._currentSlideIndex = 1;
      this._isPaused = false;
      this._isVisible = true;
      this._autoSlideId = null;
      this._progressId = null;
      this._progressStart = 0;
      this._activeTransition = null;
      this._intersectionObserver = null;
      this._touchStartX = null;
      this._touchStartY = null;
      this._touchEndX = null;
      this._touchEndY = null;
      this._isMouseOver = false;

      // Config (read from data attributes).
      this._slideTime = 5000;
      this._loopingEnabled = true;
      this._transitionType = 'instant';
      this._transitionDuration = 600;
      this._showProgress = false;
      this._pauseOnHover = true;
      this._swipeEnabled = true;
      this._keyboardEnabled = true;
      this._deeplinkId = '';

      // DOM refs.
      this._announcer = null;
      this._playPauseButton = null;
      this._nextButton = null;
      this._prevButton = null;
      this._dots = [];
      this._dotsWrapper = null;
      this._currentSlideEl = null;
      this._progressBar = null;
      this._scrollableDotsWidth = 0;
    }

    // -----------------------------------------------------------------
    // Lifecycle.
    // -----------------------------------------------------------------

    onHydrate() {
      this._inner = this.querySelector('.vvjs-inner');
      if (!this._inner) { return;
      }

      this._slideshow = this._inner.querySelector('.vvjs-items');
      if (!this._slideshow) { return;
      }

      this._slides = this._slideshow.querySelectorAll('.vvjs-item');
      this._totalSlides = this._slides.length;
      if (this._totalSlides === 0) { return;
      }

      this._readConfig();
      this._lookupRefs();
      this._setupSlides();
      this._setupCore();
      this._bindNavigation();
      this._bindKeyboard();
      this._bindPointerEvents();
      this._bindMouseHover();
      this._bindVisibility();

      this._applyReducedMotion();
      this._announceSlide(this._slideIndex, this._totalSlides);
      this._initDeepLinking();

      // Start autoplay if conditions allow.
      this._startAutoSlide();

      this._inner.classList.add('vvjs-initialized');
    }

    onDisconnect() {
      this._stopAutoSlide();
      this._immediateStopProgress();
      this._cleanupActiveTransition();
      this._intersectionObserver?.disconnect();
      this._intersectionObserver = null;
    }

    // -----------------------------------------------------------------
    // Config + DOM lookup.
    // -----------------------------------------------------------------

    _readConfig() {
      const ds = this._inner.dataset;
      this._slideTime = parseInt(ds.time, 10) || 5000;
      this._loopingEnabled = ds.enableLooping !== 'false';
      this._transitionType = ds.transition || 'instant';
      this._transitionDuration = parseInt(ds.transitionDuration, 10) || 600;
      this._showProgress = ds.showSlideProgress === 'true';
      this._pauseOnHover = ds.pauseOnHover !== 'false';
      this._swipeEnabled = ds.enableSwipe !== 'false';
      this._keyboardEnabled = ds.enableKeyboard !== 'false';
      this._isPaused = ds.static === 'true';
      this._deeplinkId = ds.deeplinkId || '';

      const startIndex = parseInt(ds.startIndex, 10) || 1;
      this._slideIndex = Math.max(1, Math.min(startIndex, this._totalSlides));
      this._currentSlideIndex = this._slideIndex;
    }

    _lookupRefs() {
      this._announcer = this._inner.querySelector('.announcer');
      this._playPauseButton = this._inner.querySelector('.play-pause-button');
      this._nextButton = this.querySelector('.next-arrow');
      this._prevButton = this.querySelector('.prev-arrow');
      this._dotsWrapper = this._inner.querySelector('.dots-numbers-button-wrapper');
      this._dots = this._dotsWrapper?.querySelectorAll('.dots-numbers-button') ?? [];
      this._currentSlideEl = this._inner.querySelector('.current-slide');
      this._progressBar = this._inner.querySelector('.progressbar');
      this._scrollableDotsWidth = parseInt(this._dotsWrapper?.dataset.scrollableDotsWidth ?? '0', 10) || 0;
    }

    // -----------------------------------------------------------------
    // Initial setup (slide visibility + heights + paused class).
    // -----------------------------------------------------------------

    _setupSlides() {
      if (this._transitionType.startsWith('crossfade')) {
        this._slides.forEach((slide, index) => {
          const isActive = index + 1 === this._slideIndex;
          slide.style.opacity = isActive ? '1' : '0';
          slide.style.zIndex = isActive ? '2' : '1';
          slide.classList.toggle('vvjs-active', isActive);
          slide.classList.toggle('vvjs-previous', !isActive);
        });
      }
      this._updateAccessibility();
      this._adjustHeight();
    }

    _setupCore() {
      this._inner.classList.toggle('vvjs-is-paused', this._isPaused);
      if (this._showProgress && this._progressBar) {
        this._progressBar.setAttribute('role', 'progressbar');
        this._progressBar.setAttribute('aria-valuenow', '0');
        this._progressBar.setAttribute('aria-valuemin', '0');
        this._progressBar.setAttribute('aria-valuemax', '100');
        this._progressBar.setAttribute('aria-label', 'Slide progress');
      }
      if (this._scrollableDotsWidth > 0 && this._dotsWrapper) {
        this._dotsWrapper.style.maxWidth = `${this._scrollableDotsWidth}px`;
        this._dotsWrapper.style.justifyContent = 'flex-start';
        requestAnimationFrame(() => this._centerActiveDot());
      }
    }

    // -----------------------------------------------------------------
    // Navigation + listeners.
    // -----------------------------------------------------------------

    _bindNavigation() {
      this._playPauseButton?.addEventListener('click', () => {
        // Stop progress before toggling, matches v1 behavior.
        if (!this._isPaused) { this._immediateStopProgress();
        }
        this._togglePause();
      }, { signal: this.signal });

      this._nextButton?.addEventListener('click', () => {
        this._goToNextSlide();
        this._startAutoSlide();
      }, { signal: this.signal });

      this._prevButton?.addEventListener('click', () => {
        this._goToPrevSlide();
        this._startAutoSlide();
      }, { signal: this.signal });

      this._dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          this._goToSlide(index + 1);
          this._startAutoSlide();
        }, { signal: this.signal });
      });
    }

    _bindKeyboard() {
      if (!this._keyboardEnabled) { return;
      }
      document.addEventListener('keydown', (e) => {
        if (e.target.closest('input, textarea, [contenteditable="true"]')) { return;
        }
        if (!this._isFocusedOrVisible()) { return;
        }
        switch (e.key) {
          case 'ArrowRight':
            e.preventDefault();
            this._goToNextSlide();
            this._startAutoSlide();
            break;

          case 'ArrowLeft':
            e.preventDefault();
            this._goToPrevSlide();
            this._startAutoSlide();
            break;

          case ' ':
          case 'Spacebar':
            e.preventDefault();
            this._togglePause();
            break;

          case 'Home':
            e.preventDefault();
            this._goToSlide(1);
            this._startAutoSlide();
            break;

          case 'End':
            e.preventDefault();
            this._goToSlide(this._totalSlides);
            this._startAutoSlide();
            break;

          default:
            break;
        }
      }, { signal: this.signal });
    }

    _isFocusedOrVisible() {
      if (this.contains(document.activeElement)) { return true;
      }
      const rect = this.getBoundingClientRect();
      return rect.top < window.innerHeight && rect.bottom > 0;
    }

    _bindPointerEvents() {
      if (!this._swipeEnabled || !this._slideshow) { return;
      }
      if ('PointerEvent' in window) {
        this._slideshow.addEventListener('pointerdown', (e) => this._handlePointerDown(e), { signal: this.signal });
        this._slideshow.addEventListener('pointermove', (e) => this._handlePointerMove(e), { passive: false, signal: this.signal });
        this._slideshow.addEventListener('pointerup', (e) => this._handlePointerUp(e), { signal: this.signal });
        this._slideshow.addEventListener('pointercancel', () => this._resetTouchState(), { signal: this.signal });
      }
      else {
        this._slideshow.addEventListener('touchstart', (e) => this._handleTouchStart(e), { passive: true, signal: this.signal });
        this._slideshow.addEventListener('touchmove', (e) => this._handleTouchMove(e), { passive: false, signal: this.signal });
        this._slideshow.addEventListener('touchend', (e) => this._handleTouchEnd(e), { passive: true, signal: this.signal });
        this._slideshow.addEventListener('touchcancel', () => this._resetTouchState(), { signal: this.signal });
      }
    }

    _bindMouseHover() {
      if (!this._slideshow) { return;
      }
      this._slideshow.addEventListener('mouseenter', () => {
        if (!this._pauseOnHover) { return;
        }
        this._isMouseOver = true;
        this._immediateStopProgress();
        this._stopAutoSlide();
      }, { signal: this.signal });
      this._slideshow.addEventListener('mouseleave', () => {
        if (!this._pauseOnHover) { return;
        }
        this._isMouseOver = false;
        this._startAutoSlide();
      }, { signal: this.signal });
    }

    _bindVisibility() {
      if ('IntersectionObserver' in window) {
        this._intersectionObserver = new IntersectionObserver((entries) => {
          const isVisible = entries[0].isIntersecting;
          this._isVisible = isVisible;
          if (isVisible && !this._isPaused) { this._startAutoSlide();
          } else { this._stopAutoSlide();
          }
        }, { threshold: 0.5, rootMargin: '50px' });
        this._intersectionObserver.observe(this);
      }

      document.addEventListener('visibilitychange', () => {
        if (document.hidden) { this._stopAutoSlide();
        } else if (this._isVisible && !this._isPaused) { this._startAutoSlide();
        }
      }, { signal: this.signal });

      const onResize = debounce(() => this._adjustHeight(), 200);
      window.addEventListener('resize', onResize, { signal: this.signal });
    }

    // -----------------------------------------------------------------
    // Pointer/Touch handlers.
    // -----------------------------------------------------------------

    _handlePointerDown(e) {
      if (e.pointerType === 'mouse') { return;
      }
      if (this._slideshow.setPointerCapture) {
        try { this._slideshow.setPointerCapture(e.pointerId); }
        catch { /* ignore */ }
      }
      this._touchStartX = e.clientX;
      this._touchStartY = e.clientY;
    }

    _handlePointerMove(e) {
      if (e.pointerType === 'mouse') { return;
      }
      if (this._touchStartX === null || this._touchStartY === null) { return;
      }
      const deltaX = Math.abs(e.clientX - this._touchStartX);
      const deltaY = Math.abs(e.clientY - this._touchStartY);
      if (deltaX > deltaY && deltaX > 10) {
        e.preventDefault();
      }
    }

    _handlePointerUp(e) {
      if (e.pointerType === 'mouse') { return;
      }
      if (this._slideshow.releasePointerCapture) {
        try { this._slideshow.releasePointerCapture(e.pointerId); }
        catch { /* ignore */ }
      }
      this._touchEndX = e.clientX;
      this._touchEndY = e.clientY;
      this._processSwipe();
      this._resetTouchState();
    }

    _handleTouchStart(e) {
      const t = e.touches[0];
      this._touchStartX = t.clientX;
      this._touchStartY = t.clientY;
    }

    _handleTouchMove(e) {
      const t = e.touches[0];
      const deltaX = Math.abs(t.clientX - this._touchStartX);
      const deltaY = Math.abs(t.clientY - this._touchStartY);
      if (deltaX > deltaY && deltaX > 10) { e.preventDefault();
      }
    }

    _handleTouchEnd(e) {
      const t = e.changedTouches[0];
      this._touchEndX = t.clientX;
      this._touchEndY = t.clientY;
      this._processSwipe();
      this._resetTouchState();
    }

    _processSwipe() {
      if (this._touchStartX === null || this._touchEndX === null) { return;
      }
      const deltaX = this._touchEndX - this._touchStartX;
      const deltaY = Math.abs(this._touchEndY - this._touchStartY);
      if (Math.abs(deltaX) > SWIPE_THRESHOLD && deltaY < SWIPE_THRESHOLD * 1.5) {
        if (deltaX > 0) { this._goToPrevSlide();
        } else { this._goToNextSlide();
        }
        this._startAutoSlide();
      }
    }

    _resetTouchState() {
      this._touchStartX = null;
      this._touchStartY = null;
      this._touchEndX = null;
      this._touchEndY = null;
    }

    // -----------------------------------------------------------------
    // Slide navigation primitives.
    // -----------------------------------------------------------------

    _goToNextSlide() {
      if (this._loopingEnabled) {
        this._slideIndex = (this._slideIndex % this._totalSlides) + 1;
      }
      else if (this._slideIndex < this._totalSlides) {
        this._slideIndex++;
      }
      else {
        this._stopAutoSlide();
        return;
      }
      this._updateSlideVisibility();
      this._adjustHeight();
    }

    _goToPrevSlide() {
      if (this._loopingEnabled) {
        this._slideIndex = (this._slideIndex === 1) ? this._totalSlides : this._slideIndex - 1;
      }
      else if (this._slideIndex > 1) {
        this._slideIndex--;
      }
      else {
        return;
      }
      this._updateSlideVisibility();
      this._adjustHeight();
    }

    _goToSlide(index) {
      if (index < 1 || index > this._totalSlides) { return;
      }
      this._slideIndex = index;
      this._updateSlideVisibility();
      this._adjustHeight();
    }

    _updateSlideVisibility() {
      const previousIndex = this._currentSlideIndex;
      const newIndex = this._slideIndex;
      this._currentSlideIndex = newIndex;
      this._performTransition(previousIndex, newIndex);

      // For instant transitions, accessibility updates immediately.
      if (this._transitionType === 'instant') {
        this._updateAccessibility();
      }

      // Update navigation visuals.
      this._updateDots();
      this._updateSlideCounter();
      this._announceSlide(newIndex, this._totalSlides);
      this._restartProgress();

      // Update deeplink hash.
      this._updateDeepLinkHash();

      // Notify external consumers of the slide change. Restores the
      // cross-component observability v1 offered via the internal
      // `vvjs:slideChanged` event — now a `vvj:slideChanged` CustomEvent on
      // `document`. See docs/planning/VVJ-V2-DROPPED-APIS.md.
      this.emit('slideChanged', {
        slide: newIndex,
        previousSlide: previousIndex,
        total: this._totalSlides,
      });
    }

    _updateAccessibility() {
      this._slides.forEach((slide, index) => {
        const isActive = index + 1 === this._slideIndex;
        if (this._transitionType === 'instant') {
          slide.style.display = isActive ? 'block' : 'none';
        }
        slide.setAttribute('aria-hidden', String(!isActive));
        slide.toggleAttribute('inert', !isActive);
        slide.classList.toggle('active', isActive);
        slide.querySelectorAll('a, button, input').forEach((el) => {
          el.setAttribute('tabindex', isActive ? '0' : '-1');
        });
      });
    }

    _adjustHeight() {
      const currentSlide = this._slides[this._slideIndex - 1];
      if (!currentSlide || !this._slideshow) { return;
      }

      let contentHeight;
      if (this._transitionType.startsWith('crossfade')) {
        const prevOpacity = currentSlide.style.opacity;
        const prevZ = currentSlide.style.zIndex;
        currentSlide.style.opacity = '1';
        currentSlide.style.zIndex = '9999';
        contentHeight = currentSlide.getBoundingClientRect().height;
        currentSlide.style.opacity = prevOpacity;
        currentSlide.style.zIndex = prevZ;
      }
      else {
        contentHeight = currentSlide.getBoundingClientRect().height;
      }

      const cs = window.getComputedStyle(this._slideshow);
      const padTop = parseFloat(cs.paddingTop) || 0;
      const padBottom = parseFloat(cs.paddingBottom) || 0;
      const borderTop = parseFloat(cs.borderTopWidth) || 0;
      const borderBottom = parseFloat(cs.borderBottomWidth) || 0;
      this._slideshow.style.height = `${contentHeight + padTop + padBottom + borderTop + borderBottom}px`;
    }

    // -----------------------------------------------------------------
    // Transitions.
    // -----------------------------------------------------------------

    _performTransition(fromIndex, toIndex) {
      this._cleanupActiveTransition();
      const outgoing = fromIndex ? this._slides[fromIndex - 1] : null;
      const incoming = this._slides[toIndex - 1];
      if (!incoming) { return;
      }
      if (this._transitionType === 'instant') { return;
      }
      if (this._transitionType.startsWith('crossfade')) {
        this._applyCrossfade(outgoing, incoming);
      }
    }

    _applyCrossfade(outgoing, incoming) {
      if (outgoing) {
        outgoing.style.zIndex = '1';
        outgoing.classList.remove('vvjs-active');
        outgoing.classList.add('vvjs-previous');
        outgoing.style.opacity = '0';
      }
      incoming.style.zIndex = '2';
      incoming.classList.remove('vvjs-previous');
      incoming.classList.add('vvjs-active');
      incoming.style.opacity = '1';
      this._setupTransitionCompletion(incoming);
    }

    _setupTransitionCompletion(element) {
      let ended = false;
      const cleanup = () => {
        if (ended) { return;
        }
        ended = true;
        if (this._activeTransition?.listener) {
          element.removeEventListener('transitionend', this._activeTransition.listener);
        }
        this._cleanupActiveTransition();
        this._updateAccessibility();
        this._adjustHeight();
      };
      let totalDuration = this._transitionDuration;
      if (this._transitionType === 'crossfade-staged') { totalDuration = Math.round(totalDuration * 1.3);
      }
      const listener = (e) => {
        if (e.propertyName === 'opacity' && e.target === element) { cleanup();
        }
      };
      this._activeTransition = {
        element,
        listener,
        timeout: setTimeout(cleanup, totalDuration + 100),
      };
      element.addEventListener('transitionend', listener);
    }

    _cleanupActiveTransition() {
      if (this._activeTransition) {
        clearTimeout(this._activeTransition.timeout);
        this._activeTransition = null;
      }
    }

    // -----------------------------------------------------------------
    // Autoplay + pause + progress.
    // -----------------------------------------------------------------

    _startAutoSlide() {
      this._stopAutoSlide();
      if (this._slideTime > 0 && !this._isPaused && this._isVisible && !this._isMouseOver) {
        this._autoSlideId = setInterval(() => this._goToNextSlide(), this._slideTime);
        if (this._showProgress) { this._startProgress();
        }
      }
    }

    _stopAutoSlide() {
      if (this._autoSlideId) {
        clearInterval(this._autoSlideId);
        this._autoSlideId = null;
      }
      this._immediateStopProgress();
    }

    _togglePause() {
      this._isPaused = !this._isPaused;
      this._inner.classList.toggle('vvjs-is-paused', this._isPaused);
      this._updatePlayPauseButton();
      if (this._isPaused) { this._stopAutoSlide();
      } else { this._startAutoSlide();
      }
    }

    _startProgress() {
      if (!this._showProgress || !this._progressBar || this._slideTime <= 0) { return;
      }
      this._immediateStopProgress();
      this._progressStart = Date.now();
      this._progressId = setInterval(() => this._tickProgress(), 50);
    }

    _restartProgress() {
      if (!this._showProgress || this._isPaused) { return;
      }
      this._immediateStopProgress();
      this._startProgress();
    }

    _tickProgress() {
      if (!this._progressBar || this._isPaused) {
        this._immediateStopProgress();
        return;
      }
      const elapsed = Date.now() - this._progressStart;
      const progress = Math.min(100, (elapsed / this._slideTime) * 100);
      this._progressBar.style.setProperty('--progress', `${progress}%`);
      this._progressBar.setAttribute('aria-valuenow', String(Math.round(progress)));
      if (progress >= 100) { this._immediateStopProgress();
      }
    }

    _immediateStopProgress() {
      if (this._progressId) {
        clearInterval(this._progressId);
        this._progressId = null;
      }
    }

    // -----------------------------------------------------------------
    // Navigation visuals.
    // -----------------------------------------------------------------

    _updateDots() {
      this._dots.forEach((dot, index) => {
        const isActive = index + 1 === this._slideIndex;
        dot.classList.toggle('active', isActive);
        dot.setAttribute('aria-selected', String(isActive));
        dot.setAttribute('tabindex', isActive ? '0' : '-1');
      });
      if (this._scrollableDotsWidth > 0 && this._dotsWrapper) { this._centerActiveDot();
      }
    }

    _centerActiveDot() {
      if (!this._dotsWrapper || this._scrollableDotsWidth <= 0) { return;
      }
      const activeDot = this._dotsWrapper.querySelector('.dots-numbers-button.active');
      if (!activeDot) { return;
      }
      const containerWidth = this._dotsWrapper.offsetWidth;
      const target = activeDot.offsetLeft - (containerWidth / 2) + (activeDot.offsetWidth / 2);
      const maxScroll = this._dotsWrapper.scrollWidth - containerWidth;
      const clamped = Math.max(0, Math.min(target, maxScroll));
      this._dotsWrapper.scrollTo({ left: clamped, behavior: 'smooth' });
    }

    _updateSlideCounter() {
      if (this._currentSlideEl) { this._currentSlideEl.textContent = String(this._slideIndex);
      }
    }

    _updatePlayPauseButton() {
      if (!this._playPauseButton) { return;
      }
      this._playPauseButton.innerHTML = this._isPaused ? PLAY_SVG : PAUSE_SVG;
      this._playPauseButton.setAttribute('aria-label', this._isPaused ? 'Play slideshow' : 'Pause slideshow');
    }

    // -----------------------------------------------------------------
    // Accessibility / reduced motion / announcer.
    // -----------------------------------------------------------------

    _applyReducedMotion() {
      if (!this.reducedMotion) { return;
      }
      this._isPaused = true;
      this._stopAutoSlide();
      this._inner.classList.add('reduced-motion');
      this._updatePlayPauseButton();
      this._announceMessage('Slideshow paused due to reduced motion preference');
      // Force transition type to instant (matches v1 behavior).
      this._transitionType = 'instant';
      this._inner.dataset.transition = 'instant';
    }

    _announceSlide(index, total) {
      if (this._announcer) {
        this._announcer.textContent = `Slide ${index} of ${total}`;
      }
    }

    _announceMessage(message) {
      if (!this._announcer) { return;
      }
      const original = this._announcer.textContent;
      this._announcer.textContent = message;
      setTimeout(() => { this._announcer.textContent = original; }, 1000);
    }

    // -----------------------------------------------------------------
    // Deep linking.
    // -----------------------------------------------------------------

    _initDeepLinking() {
      if (!this._deeplinkId) { return;
      }
      const apply = (hash) => {
        if (!hash || !hash.startsWith(`#${this._deeplinkId}-`)) { return;
        }
        const slideNumber = parseInt(hash.split('-').pop(), 10);
        if (!Number.isFinite(slideNumber) || slideNumber < 1 || slideNumber > this._totalSlides) { return;
        }
        this._goToSlide(slideNumber);
      };
      apply(window.location.hash);
      window.addEventListener('hashchange', () => apply(window.location.hash), { signal: this.signal });
    }

    _updateDeepLinkHash() {
      if (!this._deeplinkId) { return;
      }
      const newHash = `#${this._deeplinkId}-${this._slideIndex}`;
      if (window.location.hash !== newHash && window.history?.replaceState) {
        window.history.replaceState(null, '', newHash);
      }
    }

    // -----------------------------------------------------------------
    // Public API.
    // -----------------------------------------------------------------

    goToSlide(slideIndex) {
      if (slideIndex < 1 || slideIndex > this._totalSlides) {
        console.warn(`VVJS: Invalid slide ${slideIndex}. Must be between 1 and ${this._totalSlides}`);
        return false;
      }
      this._goToSlide(slideIndex);
      this._startAutoSlide();
      return true;
    }

    getCurrentSlide() {
      return this._slideIndex;
    }

    getTotalSlides() {
      return this._totalSlides;
    }

    nextSlide() {
      this._goToNextSlide();
      this._startAutoSlide();
      return true;
    }

    prevSlide() {
      this._goToPrevSlide();
      this._startAutoSlide();
      return true;
    }

    pause() {
      if (this._isPaused) { return false;
      }
      this._togglePause();
      return true;
    }

    resume() {
      if (!this._isPaused) { return false;
      }
      this._togglePause();
      return true;
    }

    /**
     * Whether autoplay is currently paused.
     *
     * Restores the pause-state query v1 exposed via
     * `getInstance(el).getState().isPaused` — see
     * docs/planning/VVJ-V2-DROPPED-APIS.md.
     *
     * @return {boolean}
     *   TRUE when paused (or static), FALSE when autoplaying.
     */
    isPaused() {
      return this._isPaused === true;
    }

    /**
     * Whether the element has finished hydrating.
     *
     * Restores v1's `getInstance(el).isInitialized()`. Because v2 hydrates
     * lazily via IntersectionObserver, this is FALSE until the element
     * scrolls into view; add the `data-eager` attribute to hydrate eagerly.
     *
     * @return {boolean}
     */
    isInitialized() {
      return this._isHydrated === true;
    }

  }

  if (!customElements.get('vvjs-slideshow')) {
    customElements.define('vvjs-slideshow', VvjsSlideshowElement);
  }

})(Drupal);
