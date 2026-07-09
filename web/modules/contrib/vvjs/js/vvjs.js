/**
 * @file
 * VVJS compat shim. Behavior lives in `<vvjs-slideshow>` custom element.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal, once) => {
  'use strict';

  Drupal.behaviors.VVJSlideshow = {
    attach(context) {
      once('vvjs-slideshow-marker', 'vvjs-slideshow', context);
    },
    detach() {},
  };

  /**
   * Resolve a deeplink-id, CSS selector, or DOM node to the matching
   * `<vvjs-slideshow>` custom element.
   *
   * @param {string|Element} identifier
   *   Either a deeplink-id (e.g. `'gallery'`), a CSS selector, or an
   *   existing element.
   * @returns {Element|null}
   *   The matched `<vvjs-slideshow>` element, or null.
   */
  function getElement(identifier) {
    if (identifier instanceof Element) {
      return identifier.closest('vvjs-slideshow');
    }
    if (typeof identifier !== 'string') { return null;
    }
    let el = document.querySelector(`vvjs-slideshow .vvjs-inner[data-deeplink-id="${identifier}"]`);
    if (el) { return el.closest('vvjs-slideshow');
    }
    el = document.querySelector(identifier);
    if (el) { return el.closest('vvjs-slideshow') || el;
    }
    return null;
  }

  Drupal.vvjs = Drupal.vvjs || {};

  Drupal.vvjs.getInstance = (containerOrSelector) => getElement(containerOrSelector);

  Drupal.vvjs.getAllInstances = () =>
    Array.from(document.querySelectorAll('vvjs-slideshow'));

  Drupal.vvjs.goToSlide = (identifier, slideIndex) => {
    const el = getElement(identifier);
    if (!el || typeof el.goToSlide !== 'function') {
      console.warn(`VVJS: Slideshow "${identifier}" not found or not yet hydrated`);
      return false;
    }
    return el.goToSlide(slideIndex);
  };

  Drupal.vvjs.getCurrentSlide = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.getCurrentSlide !== 'function') { return null;
    }
    return el.getCurrentSlide();
  };

  Drupal.vvjs.getTotalSlides = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.getTotalSlides !== 'function') { return null;
    }
    return el.getTotalSlides();
  };

  Drupal.vvjs.nextSlide = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.nextSlide !== 'function') { return false;
    }
    return el.nextSlide();
  };

  Drupal.vvjs.prevSlide = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.prevSlide !== 'function') { return false;
    }
    return el.prevSlide();
  };

  Drupal.vvjs.pause = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.pause !== 'function') { return false;
    }
    return el.pause();
  };

  Drupal.vvjs.resume = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.resume !== 'function') { return false;
    }
    return el.resume();
  };

  Drupal.vvjs.isPaused = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.isPaused !== 'function') { return null;
    }
    return el.isPaused();
  };

  Drupal.vvjs.isInitialized = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.isInitialized !== 'function') { return null;
    }
    return el.isInitialized();
  };

  Drupal.vvjs.pauseAll = () => {
    document.querySelectorAll('vvjs-slideshow').forEach((el) => {
      if (typeof el.pause === 'function') { el.pause();
      }
    });
  };

  Drupal.vvjs.resumeAll = () => {
    document.querySelectorAll('vvjs-slideshow').forEach((el) => {
      if (typeof el.resume === 'function') { el.resume();
      }
    });
  };

})(Drupal, once);
