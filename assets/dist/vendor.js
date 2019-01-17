"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

// Native Javascript for Bootstrap 4 v2.0.25 | © dnp_theme | MIT-License
(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD support:
    define([], factory);
  } else if ((typeof module === "undefined" ? "undefined" : _typeof(module)) === 'object' && module.exports) {
    // CommonJS-like:
    module.exports = factory();
  } else {
    // Browser globals (root is window)
    var bsn = factory();
    root.Alert = bsn.Alert;
    root.Button = bsn.Button;
    root.Carousel = bsn.Carousel;
    root.Collapse = bsn.Collapse;
    root.Dropdown = bsn.Dropdown;
    root.Modal = bsn.Modal;
    root.Popover = bsn.Popover;
    root.ScrollSpy = bsn.ScrollSpy;
    root.Tab = bsn.Tab;
    root.Tooltip = bsn.Tooltip;
  }
})(void 0, function () {
  /* Native Javascript for Bootstrap 4 | Internal Utility Functions
  ----------------------------------------------------------------*/
  "use strict"; // globals

  var globalObject = typeof global !== 'undefined' ? global : this || window,
      DOC = document,
      HTML = DOC.documentElement,
      body = 'body',
      // allow the library to be used in <head>
  // Native Javascript for Bootstrap Global Object
  BSN = globalObject.BSN = {},
      supports = BSN.supports = [],
      // function toggle attributes
  dataToggle = 'data-toggle',
      dataDismiss = 'data-dismiss',
      dataSpy = 'data-spy',
      dataRide = 'data-ride',
      // components
  stringAlert = 'Alert',
      stringButton = 'Button',
      stringCarousel = 'Carousel',
      stringCollapse = 'Collapse',
      stringDropdown = 'Dropdown',
      stringModal = 'Modal',
      stringPopover = 'Popover',
      stringScrollSpy = 'ScrollSpy',
      stringTab = 'Tab',
      stringTooltip = 'Tooltip',
      // options DATA API
  databackdrop = 'data-backdrop',
      dataKeyboard = 'data-keyboard',
      dataTarget = 'data-target',
      dataInterval = 'data-interval',
      dataHeight = 'data-height',
      dataPause = 'data-pause',
      dataTitle = 'data-title',
      dataOriginalTitle = 'data-original-title',
      dataOriginalText = 'data-original-text',
      dataDismissible = 'data-dismissible',
      dataTrigger = 'data-trigger',
      dataAnimation = 'data-animation',
      dataContainer = 'data-container',
      dataPlacement = 'data-placement',
      dataDelay = 'data-delay',
      dataOffsetTop = 'data-offset-top',
      dataOffsetBottom = 'data-offset-bottom',
      // option keys
  backdrop = 'backdrop',
      keyboard = 'keyboard',
      delay = 'delay',
      content = 'content',
      target = 'target',
      currentTarget = 'currentTarget',
      interval = 'interval',
      pause = 'pause',
      animation = 'animation',
      placement = 'placement',
      container = 'container',
      // box model
  offsetTop = 'offsetTop',
      offsetBottom = 'offsetBottom',
      offsetLeft = 'offsetLeft',
      scrollTop = 'scrollTop',
      scrollLeft = 'scrollLeft',
      clientWidth = 'clientWidth',
      clientHeight = 'clientHeight',
      offsetWidth = 'offsetWidth',
      offsetHeight = 'offsetHeight',
      innerWidth = 'innerWidth',
      innerHeight = 'innerHeight',
      scrollHeight = 'scrollHeight',
      height = 'height',
      // aria
  ariaExpanded = 'aria-expanded',
      ariaHidden = 'aria-hidden',
      ariaSelected = 'aria-selected',
      // event names
  clickEvent = 'click',
      hoverEvent = 'hover',
      keydownEvent = 'keydown',
      keyupEvent = 'keyup',
      resizeEvent = 'resize',
      scrollEvent = 'scroll',
      // originalEvents
  showEvent = 'show',
      shownEvent = 'shown',
      hideEvent = 'hide',
      hiddenEvent = 'hidden',
      closeEvent = 'close',
      closedEvent = 'closed',
      slidEvent = 'slid',
      slideEvent = 'slide',
      changeEvent = 'change',
      // other
  getAttribute = 'getAttribute',
      setAttribute = 'setAttribute',
      hasAttribute = 'hasAttribute',
      createElement = 'createElement',
      appendChild = 'appendChild',
      innerHTML = 'innerHTML',
      getElementsByTagName = 'getElementsByTagName',
      preventDefault = 'preventDefault',
      getBoundingClientRect = 'getBoundingClientRect',
      querySelectorAll = 'querySelectorAll',
      getElementsByCLASSNAME = 'getElementsByClassName',
      getComputedStyle = 'getComputedStyle',
      indexOf = 'indexOf',
      parentNode = 'parentNode',
      length = 'length',
      toLowerCase = 'toLowerCase',
      Transition = 'Transition',
      Duration = 'Duration',
      Webkit = 'Webkit',
      style = 'style',
      push = 'push',
      tabindex = 'tabindex',
      contains = 'contains',
      active = 'active',
      showClass = 'show',
      collapsing = 'collapsing',
      disabled = 'disabled',
      loading = 'loading',
      left = 'left',
      right = 'right',
      top = 'top',
      bottom = 'bottom',
      // tooltip / popover
  mouseHover = 'onmouseleave' in DOC ? ['mouseenter', 'mouseleave'] : ['mouseover', 'mouseout'],
      tipPositions = /\b(top|bottom|left|right)+/,
      // modal
  modalOverlay = 0,
      fixedTop = 'fixed-top',
      fixedBottom = 'fixed-bottom',
      // transitionEnd since 2.0.4
  supportTransitions = Webkit + Transition in HTML[style] || Transition[toLowerCase]() in HTML[style],
      transitionEndEvent = Webkit + Transition in HTML[style] ? Webkit[toLowerCase]() + Transition + 'End' : Transition[toLowerCase]() + 'end',
      transitionDuration = Webkit + Duration in HTML[style] ? Webkit[toLowerCase]() + Transition + Duration : Transition[toLowerCase]() + Duration,
      // set new focus element since 2.0.3
  setFocus = function setFocus(element) {
    element.focus ? element.focus() : element.setActive();
  },
      // class manipulation, since 2.0.0 requires polyfill.js
  addClass = function addClass(element, classNAME) {
    element.classList.add(classNAME);
  },
      removeClass = function removeClass(element, classNAME) {
    element.classList.remove(classNAME);
  },
      hasClass = function hasClass(element, classNAME) {
    // since 2.0.0
    return element.classList[contains](classNAME);
  },
      // selection methods
  getElementsByClassName = function getElementsByClassName(element, classNAME) {
    // returns Array
    return [].slice.call(element[getElementsByCLASSNAME](classNAME));
  },
      queryElement = function queryElement(selector, parent) {
    var lookUp = parent ? parent : DOC;
    return _typeof(selector) === 'object' ? selector : lookUp.querySelector(selector);
  },
      getClosest = function getClosest(element, selector) {
    //element is the element and selector is for the closest parent element to find
    // source http://gomakethings.com/climbing-up-and-down-the-dom-tree-with-vanilla-javascript/
    var firstChar = selector.charAt(0),
        selectorSubstring = selector.substr(1);

    if (firstChar === '.') {
      // If selector is a class
      for (; element && element !== DOC; element = element[parentNode]) {
        // Get closest match
        if (queryElement(selector, element[parentNode]) !== null && hasClass(element, selectorSubstring)) {
          return element;
        }
      }
    } else if (firstChar === '#') {
      // If selector is an ID
      for (; element && element !== DOC; element = element[parentNode]) {
        // Get closest match
        if (element.id === selectorSubstring) {
          return element;
        }
      }
    }

    return false;
  },
      // event attach jQuery style / trigger  since 1.2.0
  on = function on(element, event, handler) {
    element.addEventListener(event, handler, false);
  },
      off = function off(element, event, handler) {
    element.removeEventListener(event, handler, false);
  },
      one = function one(element, event, handler) {
    // one since 2.0.4
    on(element, event, function handlerWrapper(e) {
      handler(e);
      off(element, event, handlerWrapper);
    });
  },
      getTransitionDurationFromElement = function getTransitionDurationFromElement(element) {
    var duration = supportTransitions ? globalObject[getComputedStyle](element)[transitionDuration] : 0;
    duration = parseFloat(duration);
    duration = typeof duration === 'number' && !isNaN(duration) ? duration * 1000 : 0;
    return duration + 50; // we take a short offset to make sure we fire on the next frame after animation
  },
      emulateTransitionEnd = function emulateTransitionEnd(element, handler) {
    // emulateTransitionEnd since 2.0.4
    var called = 0,
        duration = getTransitionDurationFromElement(element);
    supportTransitions && one(element, transitionEndEvent, function (e) {
      handler(e);
      called = 1;
    });
    setTimeout(function () {
      !called && handler();
    }, duration);
  },
      bootstrapCustomEvent = function bootstrapCustomEvent(eventName, componentName, related) {
    var OriginalCustomEvent = new CustomEvent(eventName + '.bs.' + componentName);
    OriginalCustomEvent.relatedTarget = related;
    this.dispatchEvent(OriginalCustomEvent);
  },
      // tooltip / popover stuff
  getScroll = function getScroll() {
    // also Affix and ScrollSpy uses it
    return {
      y: globalObject.pageYOffset || HTML[scrollTop],
      x: globalObject.pageXOffset || HTML[scrollLeft]
    };
  },
      styleTip = function styleTip(link, element, position, parent) {
    // both popovers and tooltips (target,tooltip,placement,elementToAppendTo)
    var elementDimensions = {
      w: element[offsetWidth],
      h: element[offsetHeight]
    },
        windowWidth = HTML[clientWidth] || DOC[body][clientWidth],
        windowHeight = HTML[clientHeight] || DOC[body][clientHeight],
        rect = link[getBoundingClientRect](),
        scroll = parent === DOC[body] ? getScroll() : {
      x: parent[offsetLeft] + parent[scrollLeft],
      y: parent[offsetTop] + parent[scrollTop]
    },
        linkDimensions = {
      w: rect[right] - rect[left],
      h: rect[bottom] - rect[top]
    },
        isPopover = hasClass(element, 'popover'),
        topPosition,
        leftPosition,
        arrow = queryElement('.arrow', element),
        arrowTop,
        arrowLeft,
        arrowWidth,
        arrowHeight,
        halfTopExceed = rect[top] + linkDimensions.h / 2 - elementDimensions.h / 2 < 0,
        halfLeftExceed = rect[left] + linkDimensions.w / 2 - elementDimensions.w / 2 < 0,
        halfRightExceed = rect[left] + elementDimensions.w / 2 + linkDimensions.w / 2 >= windowWidth,
        halfBottomExceed = rect[top] + elementDimensions.h / 2 + linkDimensions.h / 2 >= windowHeight,
        topExceed = rect[top] - elementDimensions.h < 0,
        leftExceed = rect[left] - elementDimensions.w < 0,
        bottomExceed = rect[top] + elementDimensions.h + linkDimensions.h >= windowHeight,
        rightExceed = rect[left] + elementDimensions.w + linkDimensions.w >= windowWidth; // recompute position

    position = (position === left || position === right) && leftExceed && rightExceed ? top : position; // first, when both left and right limits are exceeded, we fall back to top|bottom

    position = position === top && topExceed ? bottom : position;
    position = position === bottom && bottomExceed ? top : position;
    position = position === left && leftExceed ? right : position;
    position = position === right && rightExceed ? left : position; // update tooltip/popover class

    element.className[indexOf](position) === -1 && (element.className = element.className.replace(tipPositions, position)); // we check the computed width & height and update here

    arrowWidth = arrow[offsetWidth];
    arrowHeight = arrow[offsetHeight]; // apply styling to tooltip or popover

    if (position === left || position === right) {
      // secondary|side positions
      if (position === left) {
        // LEFT
        leftPosition = rect[left] + scroll.x - elementDimensions.w - (isPopover ? arrowWidth : 0);
      } else {
        // RIGHT
        leftPosition = rect[left] + scroll.x + linkDimensions.w;
      } // adjust top and arrow


      if (halfTopExceed) {
        topPosition = rect[top] + scroll.y;
        arrowTop = linkDimensions.h / 2 - arrowWidth;
      } else if (halfBottomExceed) {
        topPosition = rect[top] + scroll.y - elementDimensions.h + linkDimensions.h;
        arrowTop = elementDimensions.h - linkDimensions.h / 2 - arrowWidth;
      } else {
        topPosition = rect[top] + scroll.y - elementDimensions.h / 2 + linkDimensions.h / 2;
        arrowTop = elementDimensions.h / 2 - (isPopover ? arrowHeight * 0.9 : arrowHeight / 2);
      }
    } else if (position === top || position === bottom) {
      // primary|vertical positions
      if (position === top) {
        // TOP
        topPosition = rect[top] + scroll.y - elementDimensions.h - (isPopover ? arrowHeight : 0);
      } else {
        // BOTTOM
        topPosition = rect[top] + scroll.y + linkDimensions.h;
      } // adjust left | right and also the arrow


      if (halfLeftExceed) {
        leftPosition = 0;
        arrowLeft = rect[left] + linkDimensions.w / 2 - arrowWidth;
      } else if (halfRightExceed) {
        leftPosition = windowWidth - elementDimensions.w * 1.01;
        arrowLeft = elementDimensions.w - (windowWidth - rect[left]) + linkDimensions.w / 2 - arrowWidth / 2;
      } else {
        leftPosition = rect[left] + scroll.x - elementDimensions.w / 2 + linkDimensions.w / 2;
        arrowLeft = elementDimensions.w / 2 - arrowWidth / 2;
      }
    } // apply style to tooltip/popover and its arrow


    element[style][top] = topPosition + 'px';
    element[style][left] = leftPosition + 'px';
    arrowTop && (arrow[style][top] = arrowTop + 'px');
    arrowLeft && (arrow[style][left] = arrowLeft + 'px');
  };

  BSN.version = '2.0.25';
  /* Native Javascript for Bootstrap 4 | Alert
  -------------------------------------------*/
  // ALERT DEFINITION
  // ================

  var Alert = function Alert(element) {
    // initialization element
    element = queryElement(element); // bind, target alert, duration and stuff

    var self = this,
        component = 'alert',
        alert = getClosest(element, '.' + component),
        triggerHandler = function triggerHandler() {
      hasClass(alert, 'fade') ? emulateTransitionEnd(alert, transitionEndHandler) : transitionEndHandler();
    },
        // handlers
    clickHandler = function clickHandler(e) {
      alert = getClosest(e[target], '.' + component);
      element = queryElement('[' + dataDismiss + '="' + component + '"]', alert);
      element && alert && (element === e[target] || element[contains](e[target])) && self.close();
    },
        transitionEndHandler = function transitionEndHandler() {
      bootstrapCustomEvent.call(alert, closedEvent, component);
      off(element, clickEvent, clickHandler); // detach it's listener

      alert[parentNode].removeChild(alert);
    }; // public method


    this.close = function () {
      if (alert && element && hasClass(alert, showClass)) {
        bootstrapCustomEvent.call(alert, closeEvent, component);
        removeClass(alert, showClass);
        alert && triggerHandler();
      }
    }; // init


    if (!(stringAlert in element)) {
      // prevent adding event handlers twice
      on(element, clickEvent, clickHandler);
    }

    element[stringAlert] = self;
  }; // ALERT DATA API
  // ==============


  supports[push]([stringAlert, Alert, '[' + dataDismiss + '="alert"]']);
  /* Native Javascript for Bootstrap 4 | Button
  ---------------------------------------------*/
  // BUTTON DEFINITION
  // ===================

  var Button = function Button(element) {
    // initialization element
    element = queryElement(element); // constant

    var toggled = false,
        // toggled makes sure to prevent triggering twice the change.bs.button events
    // strings
    component = 'button',
        checked = 'checked',
        reset = 'reset',
        LABEL = 'LABEL',
        INPUT = 'INPUT',
        // private methods
    keyHandler = function keyHandler(e) {
      var key = e.which || e.keyCode;
      key === 32 && e[target] === DOC.activeElement && toggle(e);
    },
        preventScroll = function preventScroll(e) {
      var key = e.which || e.keyCode;
      key === 32 && e[preventDefault]();
    },
        toggle = function toggle(e) {
      var label = e[target].tagName === LABEL ? e[target] : e[target][parentNode].tagName === LABEL ? e[target][parentNode] : null; // the .btn label

      if (!label) return; //react if a label or its immediate child is clicked

      var eventTarget = e[target],
          // the button itself, the target of the handler function
      labels = getElementsByClassName(eventTarget[parentNode], 'btn'),
          // all the button group buttons
      input = label[getElementsByTagName](INPUT)[0];
      if (!input) return; //return if no input found
      // manage the dom manipulation

      if (input.type === 'checkbox') {
        //checkboxes
        if (!input[checked]) {
          addClass(label, active);
          input[getAttribute](checked);
          input[setAttribute](checked, checked);
          input[checked] = true;
        } else {
          removeClass(label, active);
          input[getAttribute](checked);
          input.removeAttribute(checked);
          input[checked] = false;
        }

        if (!toggled) {
          // prevent triggering the event twice
          toggled = true;
          bootstrapCustomEvent.call(input, changeEvent, component); //trigger the change for the input

          bootstrapCustomEvent.call(element, changeEvent, component); //trigger the change for the btn-group
        }
      }

      if (input.type === 'radio' && !toggled) {
        // radio buttons
        if (!input[checked]) {
          // don't trigger if already active
          addClass(label, active);
          input[setAttribute](checked, checked);
          input[checked] = true;
          bootstrapCustomEvent.call(input, changeEvent, component); //trigger the change for the input

          bootstrapCustomEvent.call(element, changeEvent, component); //trigger the change for the btn-group

          toggled = true;

          for (var i = 0, ll = labels[length]; i < ll; i++) {
            var otherLabel = labels[i],
                otherInput = otherLabel[getElementsByTagName](INPUT)[0];

            if (otherLabel !== label && hasClass(otherLabel, active)) {
              removeClass(otherLabel, active);
              otherInput.removeAttribute(checked);
              otherInput[checked] = false;
              bootstrapCustomEvent.call(otherInput, changeEvent, component); // trigger the change
            }
          }
        }
      }

      setTimeout(function () {
        toggled = false;
      }, 50);
    }; // init


    if (!(stringButton in element)) {
      // prevent adding event handlers twice
      on(element, clickEvent, toggle);
      queryElement('[' + tabindex + ']', element) && on(element, keyupEvent, keyHandler), on(element, keydownEvent, preventScroll);
    } // activate items on load


    var labelsToACtivate = getElementsByClassName(element, 'btn'),
        lbll = labelsToACtivate[length];

    for (var i = 0; i < lbll; i++) {
      !hasClass(labelsToACtivate[i], active) && queryElement('input:checked', labelsToACtivate[i]) && addClass(labelsToACtivate[i], active);
    }

    element[stringButton] = this;
  }; // BUTTON DATA API
  // =================


  supports[push]([stringButton, Button, '[' + dataToggle + '="buttons"]']);
  /* Native Javascript for Bootstrap 4 | Carousel
  ----------------------------------------------*/
  // CAROUSEL DEFINITION
  // ===================

  var Carousel = function Carousel(element, options) {
    // initialization element
    element = queryElement(element); // set options

    options = options || {}; // DATA API

    var intervalAttribute = element[getAttribute](dataInterval),
        intervalOption = options[interval],
        intervalData = intervalAttribute === 'false' ? 0 : parseInt(intervalAttribute),
        pauseData = element[getAttribute](dataPause) === hoverEvent || false,
        keyboardData = element[getAttribute](dataKeyboard) === 'true' || false,
        // strings
    component = 'carousel',
        paused = 'paused',
        direction = 'direction',
        carouselItem = 'carousel-item',
        dataSlideTo = 'data-slide-to';
    this[keyboard] = options[keyboard] === true || keyboardData;
    this[pause] = options[pause] === hoverEvent || pauseData ? hoverEvent : false; // false / hover

    this[interval] = typeof intervalOption === 'number' ? intervalOption : intervalOption === false || intervalData === 0 || intervalData === false ? 0 : isNaN(intervalData) ? 5000 // bootstrap carousel default interval
    : intervalData; // bind, event targets

    var self = this,
        index = element.index = 0,
        timer = element.timer = 0,
        isSliding = false,
        // isSliding prevents click event handlers when animation is running
    slides = getElementsByClassName(element, carouselItem),
        total = slides[length],
        slideDirection = this[direction] = left,
        leftArrow = getElementsByClassName(element, component + '-control-prev')[0],
        rightArrow = getElementsByClassName(element, component + '-control-next')[0],
        indicator = queryElement('.' + component + '-indicators', element),
        indicators = indicator && indicator[getElementsByTagName]("LI") || []; // invalidate when not enough items

    if (total < 2) {
      return;
    } // handlers


    var pauseHandler = function pauseHandler() {
      if (self[interval] !== false && !hasClass(element, paused)) {
        addClass(element, paused);
        !isSliding && (clearInterval(timer), timer = null);
      }
    },
        resumeHandler = function resumeHandler() {
      if (self[interval] !== false && hasClass(element, paused)) {
        removeClass(element, paused);
        !isSliding && (clearInterval(timer), timer = null);
        !isSliding && self.cycle();
      }
    },
        indicatorHandler = function indicatorHandler(e) {
      e[preventDefault]();
      if (isSliding) return;
      var eventTarget = e[target]; // event target | the current active item

      if (eventTarget && !hasClass(eventTarget, active) && eventTarget[getAttribute](dataSlideTo)) {
        index = parseInt(eventTarget[getAttribute](dataSlideTo), 10);
      } else {
        return false;
      }

      self.slideTo(index); //Do the slide
    },
        controlsHandler = function controlsHandler(e) {
      e[preventDefault]();
      if (isSliding) return;
      var eventTarget = e.currentTarget || e.srcElement;

      if (eventTarget === rightArrow) {
        index++;
      } else if (eventTarget === leftArrow) {
        index--;
      }

      self.slideTo(index); //Do the slide
    },
        keyHandler = function keyHandler(e) {
      if (isSliding) return;

      switch (e.which) {
        case 39:
          index++;
          break;

        case 37:
          index--;
          break;

        default:
          return;
      }

      self.slideTo(index); //Do the slide
    },
        // private methods
    isElementInScrollRange = function isElementInScrollRange() {
      var rect = element[getBoundingClientRect](),
          viewportHeight = globalObject[innerHeight] || HTML[clientHeight];
      return rect[top] <= viewportHeight && rect[bottom] >= 0; // bottom && top
    },
        setActivePage = function setActivePage(pageIndex) {
      //indicators
      for (var i = 0, icl = indicators[length]; i < icl; i++) {
        removeClass(indicators[i], active);
      }

      if (indicators[pageIndex]) addClass(indicators[pageIndex], active);
    }; // public methods


    this.cycle = function () {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }

      timer = setInterval(function () {
        isElementInScrollRange() && (index++, self.slideTo(index));
      }, this[interval]);
    };

    this.slideTo = function (next) {
      if (isSliding) return; // when controled via methods, make sure to check again      

      var activeItem = this.getActiveIndex(),
          // the current active
      orientation; // first return if we're on the same item #227

      if (activeItem === next) {
        return; // or determine slideDirection
      } else if (activeItem < next || activeItem === 0 && next === total - 1) {
        slideDirection = self[direction] = left; // next
      } else if (activeItem > next || activeItem === total - 1 && next === 0) {
        slideDirection = self[direction] = right; // prev
      } // find the right next index 


      if (next < 0) {
        next = total - 1;
      } else if (next >= total) {
        next = 0;
      } // update index


      index = next;
      orientation = slideDirection === left ? 'next' : 'prev'; //determine type

      bootstrapCustomEvent.call(element, slideEvent, component, slides[next]); // here we go with the slide

      isSliding = true;
      clearInterval(timer);
      timer = null;
      setActivePage(next);

      if (supportTransitions && hasClass(element, 'slide')) {
        addClass(slides[next], carouselItem + '-' + orientation);
        slides[next][offsetWidth];
        addClass(slides[next], carouselItem + '-' + slideDirection);
        addClass(slides[activeItem], carouselItem + '-' + slideDirection);
        one(slides[next], transitionEndEvent, function (e) {
          var timeout = e[target] !== slides[next] ? e.elapsedTime * 1000 + 100 : 20;
          isSliding && setTimeout(function () {
            isSliding = false;
            addClass(slides[next], active);
            removeClass(slides[activeItem], active);
            removeClass(slides[next], carouselItem + '-' + orientation);
            removeClass(slides[next], carouselItem + '-' + slideDirection);
            removeClass(slides[activeItem], carouselItem + '-' + slideDirection);
            bootstrapCustomEvent.call(element, slidEvent, component, slides[next]);

            if (!DOC.hidden && self[interval] && !hasClass(element, paused)) {
              self.cycle();
            }
          }, timeout);
        });
      } else {
        addClass(slides[next], active);
        slides[next][offsetWidth];
        removeClass(slides[activeItem], active);
        setTimeout(function () {
          isSliding = false;

          if (self[interval] && !hasClass(element, paused)) {
            self.cycle();
          }

          bootstrapCustomEvent.call(element, slidEvent, component, slides[next]);
        }, 100);
      }
    };

    this.getActiveIndex = function () {
      return slides[indexOf](getElementsByClassName(element, carouselItem + ' active')[0]) || 0;
    }; // init


    if (!(stringCarousel in element)) {
      // prevent adding event handlers twice
      if (self[pause] && self[interval]) {
        on(element, mouseHover[0], pauseHandler);
        on(element, mouseHover[1], resumeHandler);
        on(element, 'touchstart', pauseHandler);
        on(element, 'touchend', resumeHandler);
      }

      rightArrow && on(rightArrow, clickEvent, controlsHandler);
      leftArrow && on(leftArrow, clickEvent, controlsHandler);
      indicator && on(indicator, clickEvent, indicatorHandler);
      self[keyboard] === true && on(globalObject, keydownEvent, keyHandler);
    }

    if (self.getActiveIndex() < 0) {
      slides[length] && addClass(slides[0], active);
      indicators[length] && setActivePage(0);
    }

    if (self[interval]) {
      self.cycle();
    }

    element[stringCarousel] = self;
  }; // CAROUSEL DATA API
  // =================


  supports[push]([stringCarousel, Carousel, '[' + dataRide + '="carousel"]']);
  /* Native Javascript for Bootstrap 4 | Collapse
  -----------------------------------------------*/
  // COLLAPSE DEFINITION
  // ===================

  var Collapse = function Collapse(element, options) {
    // initialization element
    element = queryElement(element); // set options

    options = options || {}; // event targets and constants

    var accordion = null,
        collapse = null,
        self = this,
        accordionData = element[getAttribute]('data-parent'),
        activeCollapse,
        activeElement,
        // component strings
    component = 'collapse',
        collapsed = 'collapsed',
        isAnimating = 'isAnimating',
        // private methods
    openAction = function openAction(collapseElement, toggle) {
      bootstrapCustomEvent.call(collapseElement, showEvent, component);
      collapseElement[isAnimating] = true;
      addClass(collapseElement, collapsing);
      removeClass(collapseElement, component);
      collapseElement[style][height] = collapseElement[scrollHeight] + 'px';
      emulateTransitionEnd(collapseElement, function () {
        collapseElement[isAnimating] = false;
        collapseElement[setAttribute](ariaExpanded, 'true');
        toggle[setAttribute](ariaExpanded, 'true');
        removeClass(collapseElement, collapsing);
        addClass(collapseElement, component);
        addClass(collapseElement, showClass);
        collapseElement[style][height] = '';
        bootstrapCustomEvent.call(collapseElement, shownEvent, component);
      });
    },
        closeAction = function closeAction(collapseElement, toggle) {
      bootstrapCustomEvent.call(collapseElement, hideEvent, component);
      collapseElement[isAnimating] = true;
      collapseElement[style][height] = collapseElement[scrollHeight] + 'px'; // set height first

      removeClass(collapseElement, component);
      removeClass(collapseElement, showClass);
      addClass(collapseElement, collapsing);
      collapseElement[offsetWidth]; // force reflow to enable transition

      collapseElement[style][height] = '0px';
      emulateTransitionEnd(collapseElement, function () {
        collapseElement[isAnimating] = false;
        collapseElement[setAttribute](ariaExpanded, 'false');
        toggle[setAttribute](ariaExpanded, 'false');
        removeClass(collapseElement, collapsing);
        addClass(collapseElement, component);
        collapseElement[style][height] = '';
        bootstrapCustomEvent.call(collapseElement, hiddenEvent, component);
      });
    },
        getTarget = function getTarget() {
      var href = element.href && element[getAttribute]('href'),
          parent = element[getAttribute](dataTarget),
          id = href || parent && parent.charAt(0) === '#' && parent;
      return id && queryElement(id);
    }; // public methods


    this.toggle = function (e) {
      e[preventDefault]();

      if (!hasClass(collapse, showClass)) {
        self.show();
      } else {
        self.hide();
      }
    };

    this.hide = function () {
      if (collapse[isAnimating]) return;
      closeAction(collapse, element);
      addClass(element, collapsed);
    };

    this.show = function () {
      if (accordion) {
        activeCollapse = queryElement('.' + component + '.' + showClass, accordion);
        activeElement = activeCollapse && (queryElement('[' + dataToggle + '="' + component + '"][' + dataTarget + '="#' + activeCollapse.id + '"]', accordion) || queryElement('[' + dataToggle + '="' + component + '"][href="#' + activeCollapse.id + '"]', accordion));
      }

      if (!collapse[isAnimating] || activeCollapse && !activeCollapse[isAnimating]) {
        if (activeElement && activeCollapse !== collapse) {
          closeAction(activeCollapse, activeElement);
          addClass(activeElement, collapsed);
        }

        openAction(collapse, element);
        removeClass(element, collapsed);
      }
    }; // init


    if (!(stringCollapse in element)) {
      // prevent adding event handlers twice
      on(element, clickEvent, self.toggle);
    }

    collapse = getTarget();
    collapse[isAnimating] = false; // when true it will prevent click handlers  

    accordion = queryElement(options.parent) || accordionData && getClosest(element, accordionData);
    element[stringCollapse] = self;
  }; // COLLAPSE DATA API
  // =================


  supports[push]([stringCollapse, Collapse, '[' + dataToggle + '="collapse"]']);
  /* Native Javascript for Bootstrap 4 | Dropdown
  ----------------------------------------------*/
  // DROPDOWN DEFINITION
  // ===================

  var Dropdown = function Dropdown(element, option) {
    // initialization element
    element = queryElement(element); // set option

    this.persist = option === true || element[getAttribute]('data-persist') === 'true' || false; // constants, event targets, strings

    var self = this,
        children = 'children',
        parent = element[parentNode],
        component = 'dropdown',
        open = 'open',
        relatedTarget = null,
        menu = queryElement('.dropdown-menu', parent),
        menuItems = function () {
      var set = menu[children],
          newSet = [];

      for (var i = 0; i < set[length]; i++) {
        set[i][children][length] && set[i][children][0].tagName === 'A' && newSet[push](set[i][children][0]);
        set[i].tagName === 'A' && newSet[push](set[i]);
      }

      return newSet;
    }(),
        // preventDefault on empty anchor links
    preventEmptyAnchor = function preventEmptyAnchor(anchor) {
      (anchor.href && anchor.href.slice(-1) === '#' || anchor[parentNode] && anchor[parentNode].href && anchor[parentNode].href.slice(-1) === '#') && this[preventDefault]();
    },
        // toggle dismissible events
    toggleDismiss = function toggleDismiss() {
      var type = element[open] ? on : off;
      type(DOC, clickEvent, dismissHandler);
      type(DOC, keydownEvent, preventScroll);
      type(DOC, keyupEvent, keyHandler);
    },
        // handlers
    dismissHandler = function dismissHandler(e) {
      var eventTarget = e[target],
          hasData = eventTarget && (stringDropdown in eventTarget || stringDropdown in eventTarget[parentNode]);

      if ((eventTarget === menu || menu[contains](eventTarget)) && (self.persist || hasData)) {
        return;
      } else {
        relatedTarget = eventTarget === element || element[contains](eventTarget) ? element : null;
        hide();
      }

      preventEmptyAnchor.call(e, eventTarget);
    },
        clickHandler = function clickHandler(e) {
      relatedTarget = element;
      show();
      preventEmptyAnchor.call(e, e[target]);
    },
        preventScroll = function preventScroll(e) {
      var key = e.which || e.keyCode;

      if (key === 38 || key === 40) {
        e[preventDefault]();
      }
    },
        keyHandler = function keyHandler(e) {
      var key = e.which || e.keyCode,
          activeItem = DOC.activeElement,
          idx = menuItems[indexOf](activeItem),
          isSameElement = activeItem === element,
          isInsideMenu = menu[contains](activeItem),
          isMenuItem = activeItem[parentNode] === menu || activeItem[parentNode][parentNode] === menu;

      if (isMenuItem || isSameElement) {
        // navigate up | down
        idx = isSameElement ? 0 : key === 38 ? idx > 1 ? idx - 1 : 0 : key === 40 ? idx < menuItems[length] - 1 ? idx + 1 : idx : idx;
        menuItems[idx] && setFocus(menuItems[idx]);
      }

      if ((menuItems[length] && isMenuItem // menu has items
      || !menuItems[length] && (isInsideMenu || isSameElement) // menu might be a form
      || !isInsideMenu) && // or the focused element is not in the menu at all
      element[open] && key === 27 // menu must be open
      ) {
          self.toggle();
          relatedTarget = null;
        }
    },
        // private methods
    show = function show() {
      bootstrapCustomEvent.call(parent, showEvent, component, relatedTarget);
      addClass(menu, showClass);
      addClass(parent, showClass);
      element[setAttribute](ariaExpanded, true);
      bootstrapCustomEvent.call(parent, shownEvent, component, relatedTarget);
      element[open] = true;
      off(element, clickEvent, clickHandler);
      setTimeout(function () {
        setFocus(menu[getElementsByTagName]('INPUT')[0] || element); // focus the first input item | element

        toggleDismiss();
      }, 1);
    },
        hide = function hide() {
      bootstrapCustomEvent.call(parent, hideEvent, component, relatedTarget);
      removeClass(menu, showClass);
      removeClass(parent, showClass);
      element[setAttribute](ariaExpanded, false);
      bootstrapCustomEvent.call(parent, hiddenEvent, component, relatedTarget);
      element[open] = false;
      toggleDismiss();
      setFocus(element);
      setTimeout(function () {
        on(element, clickEvent, clickHandler);
      }, 1);
    }; // set initial state to closed


    element[open] = false; // public methods

    this.toggle = function () {
      if (hasClass(parent, showClass) && element[open]) {
        hide();
      } else {
        show();
      }
    }; // init


    if (!(stringDropdown in element)) {
      // prevent adding event handlers twice
      !tabindex in menu && menu[setAttribute](tabindex, '0'); // Fix onblur on Chrome | Safari

      on(element, clickEvent, clickHandler);
    }

    element[stringDropdown] = self;
  }; // DROPDOWN DATA API
  // =================


  supports[push]([stringDropdown, Dropdown, '[' + dataToggle + '="dropdown"]']);
  /* Native Javascript for Bootstrap 4 | Modal
  -------------------------------------------*/
  // MODAL DEFINITION
  // ===============

  var Modal = function Modal(element, options) {
    // element can be the modal/triggering button
    // the modal (both JavaScript / DATA API init) / triggering button element (DATA API)
    element = queryElement(element); // determine modal, triggering element

    var btnCheck = element[getAttribute](dataTarget) || element[getAttribute]('href'),
        checkModal = queryElement(btnCheck),
        modal = hasClass(element, 'modal') ? element : checkModal,
        overlayDelay,
        // strings
    component = 'modal',
        staticString = 'static',
        paddingLeft = 'paddingLeft',
        paddingRight = 'paddingRight',
        modalBackdropString = 'modal-backdrop';

    if (hasClass(element, 'modal')) {
      element = null;
    } // modal is now independent of it's triggering element


    if (!modal) {
      return;
    } // invalidate
    // set options


    options = options || {};
    this[keyboard] = options[keyboard] === false || modal[getAttribute](dataKeyboard) === 'false' ? false : true;
    this[backdrop] = options[backdrop] === staticString || modal[getAttribute](databackdrop) === staticString ? staticString : true;
    this[backdrop] = options[backdrop] === false || modal[getAttribute](databackdrop) === 'false' ? false : this[backdrop];
    this[content] = options[content]; // JavaScript only
    // bind, constants, event targets and other vars

    var self = this,
        relatedTarget = null,
        bodyIsOverflowing,
        modalIsOverflowing,
        scrollbarWidth,
        overlay,
        // also find fixed-top / fixed-bottom items
    fixedItems = getElementsByClassName(HTML, fixedTop).concat(getElementsByClassName(HTML, fixedBottom)),
        // private methods
    getWindowWidth = function getWindowWidth() {
      var htmlRect = HTML[getBoundingClientRect]();
      return globalObject[innerWidth] || htmlRect[right] - Math.abs(htmlRect[left]);
    },
        setScrollbar = function setScrollbar() {
      var bodyStyle = globalObject[getComputedStyle](DOC[body]),
          bodyPad = parseInt(bodyStyle[paddingRight], 10),
          itemPad;

      if (bodyIsOverflowing) {
        DOC[body][style][paddingRight] = bodyPad + scrollbarWidth + 'px';

        if (fixedItems[length]) {
          for (var i = 0; i < fixedItems[length]; i++) {
            itemPad = globalObject[getComputedStyle](fixedItems[i])[paddingRight];
            fixedItems[i][style][paddingRight] = parseInt(itemPad) + scrollbarWidth + 'px';
          }
        }
      }
    },
        resetScrollbar = function resetScrollbar() {
      DOC[body][style][paddingRight] = '';

      if (fixedItems[length]) {
        for (var i = 0; i < fixedItems[length]; i++) {
          fixedItems[i][style][paddingRight] = '';
        }
      }
    },
        measureScrollbar = function measureScrollbar() {
      // thx walsh
      var scrollDiv = DOC[createElement]('div'),
          scrollBarWidth;
      scrollDiv.className = component + '-scrollbar-measure'; // this is here to stay

      DOC[body][appendChild](scrollDiv);
      scrollBarWidth = scrollDiv[offsetWidth] - scrollDiv[clientWidth];
      DOC[body].removeChild(scrollDiv);
      return scrollBarWidth;
    },
        checkScrollbar = function checkScrollbar() {
      bodyIsOverflowing = DOC[body][clientWidth] < getWindowWidth();
      modalIsOverflowing = modal[scrollHeight] > HTML[clientHeight];
      scrollbarWidth = measureScrollbar();
    },
        adjustDialog = function adjustDialog() {
      modal[style][paddingLeft] = !bodyIsOverflowing && modalIsOverflowing ? scrollbarWidth + 'px' : '';
      modal[style][paddingRight] = bodyIsOverflowing && !modalIsOverflowing ? scrollbarWidth + 'px' : '';
    },
        resetAdjustments = function resetAdjustments() {
      modal[style][paddingLeft] = '';
      modal[style][paddingRight] = '';
    },
        createOverlay = function createOverlay() {
      modalOverlay = 1;
      var newOverlay = DOC[createElement]('div');
      overlay = queryElement('.' + modalBackdropString);

      if (overlay === null) {
        newOverlay[setAttribute]('class', modalBackdropString + ' fade');
        overlay = newOverlay;
        DOC[body][appendChild](overlay);
      }
    },
        removeOverlay = function removeOverlay() {
      overlay = queryElement('.' + modalBackdropString);

      if (overlay && overlay !== null && _typeof(overlay) === 'object') {
        modalOverlay = 0;
        DOC[body].removeChild(overlay);
        overlay = null;
      }

      bootstrapCustomEvent.call(modal, hiddenEvent, component);
    },
        keydownHandlerToggle = function keydownHandlerToggle() {
      if (hasClass(modal, showClass)) {
        on(DOC, keydownEvent, keyHandler);
      } else {
        off(DOC, keydownEvent, keyHandler);
      }
    },
        resizeHandlerToggle = function resizeHandlerToggle() {
      if (hasClass(modal, showClass)) {
        on(globalObject, resizeEvent, self.update);
      } else {
        off(globalObject, resizeEvent, self.update);
      }
    },
        dismissHandlerToggle = function dismissHandlerToggle() {
      if (hasClass(modal, showClass)) {
        on(modal, clickEvent, dismissHandler);
      } else {
        off(modal, clickEvent, dismissHandler);
      }
    },
        // triggers
    triggerShow = function triggerShow() {
      resizeHandlerToggle();
      dismissHandlerToggle();
      keydownHandlerToggle();
      setFocus(modal);
      bootstrapCustomEvent.call(modal, shownEvent, component, relatedTarget);
    },
        triggerHide = function triggerHide() {
      modal[style].display = '';
      element && setFocus(element);

      (function () {
        if (!getElementsByClassName(DOC, component + ' ' + showClass)[0]) {
          resetAdjustments();
          resetScrollbar();
          removeClass(DOC[body], component + '-open');
          overlay && hasClass(overlay, 'fade') ? (removeClass(overlay, showClass), emulateTransitionEnd(overlay, removeOverlay)) : removeOverlay();
          resizeHandlerToggle();
          dismissHandlerToggle();
          keydownHandlerToggle();
        }
      })();
    },
        // handlers
    clickHandler = function clickHandler(e) {
      var clickTarget = e[target];
      clickTarget = clickTarget[hasAttribute](dataTarget) || clickTarget[hasAttribute]('href') ? clickTarget : clickTarget[parentNode];

      if (clickTarget === element && !hasClass(modal, showClass)) {
        modal.modalTrigger = element;
        relatedTarget = element;
        self.show();
        e[preventDefault]();
      }
    },
        keyHandler = function keyHandler(e) {
      if (self[keyboard] && e.which == 27 && hasClass(modal, showClass)) {
        self.hide();
      }
    },
        dismissHandler = function dismissHandler(e) {
      var clickTarget = e[target];

      if (hasClass(modal, showClass) && (clickTarget[parentNode][getAttribute](dataDismiss) === component || clickTarget[getAttribute](dataDismiss) === component || clickTarget === modal && self[backdrop] !== staticString)) {
        self.hide();
        relatedTarget = null;
        e[preventDefault]();
      }
    }; // public methods


    this.toggle = function () {
      if (hasClass(modal, showClass)) {
        this.hide();
      } else {
        this.show();
      }
    };

    this.show = function () {
      bootstrapCustomEvent.call(modal, showEvent, component, relatedTarget); // we elegantly hide any opened modal

      var currentOpen = getElementsByClassName(DOC, component + ' ' + showClass)[0];
      currentOpen && currentOpen !== modal && currentOpen.modalTrigger[stringModal].hide();

      if (this[backdrop]) {
        !modalOverlay && createOverlay();
      }

      if (overlay && modalOverlay && !hasClass(overlay, showClass)) {
        overlay[offsetWidth]; // force reflow to enable trasition

        overlayDelay = getTransitionDurationFromElement(overlay);
        addClass(overlay, showClass);
      }

      setTimeout(function () {
        modal[style].display = 'block';
        checkScrollbar();
        setScrollbar();
        adjustDialog();
        addClass(DOC[body], component + '-open');
        addClass(modal, showClass);
        modal[setAttribute](ariaHidden, false);
        hasClass(modal, 'fade') ? emulateTransitionEnd(modal, triggerShow) : triggerShow();
      }, supportTransitions && overlay ? overlayDelay : 0);
    };

    this.hide = function () {
      bootstrapCustomEvent.call(modal, hideEvent, component);
      overlay = queryElement('.' + modalBackdropString);
      overlayDelay = overlay && getTransitionDurationFromElement(overlay);
      removeClass(modal, showClass);
      modal[setAttribute](ariaHidden, true);
      setTimeout(function () {
        hasClass(modal, 'fade') ? emulateTransitionEnd(modal, triggerHide) : triggerHide();
      }, supportTransitions && overlay ? overlayDelay : 0);
    };

    this.setContent = function (content) {
      queryElement('.' + component + '-content', modal)[innerHTML] = content;
    };

    this.update = function () {
      if (hasClass(modal, showClass)) {
        checkScrollbar();
        setScrollbar();
        adjustDialog();
      }
    }; // init
    // prevent adding event handlers over and over
    // modal is independent of a triggering element


    if (!!element && !(stringModal in element)) {
      on(element, clickEvent, clickHandler);
    }

    if (!!self[content]) {
      self.setContent(self[content]);
    }

    !!element && (element[stringModal] = self);
  }; // DATA API


  supports[push]([stringModal, Modal, '[' + dataToggle + '="modal"]']);
  /* Native Javascript for Bootstrap 4 | Popover
  ----------------------------------------------*/
  // POPOVER DEFINITION
  // ==================

  var Popover = function Popover(element, options) {
    // initialization element
    element = queryElement(element); // set options

    options = options || {}; // DATA API

    var triggerData = element[getAttribute](dataTrigger),
        // click / hover / focus
    animationData = element[getAttribute](dataAnimation),
        // true / false
    placementData = element[getAttribute](dataPlacement),
        dismissibleData = element[getAttribute](dataDismissible),
        delayData = element[getAttribute](dataDelay),
        containerData = element[getAttribute](dataContainer),
        // internal strings
    component = 'popover',
        template = 'template',
        trigger = 'trigger',
        classString = 'class',
        div = 'div',
        fade = 'fade',
        content = 'content',
        dataContent = 'data-content',
        dismissible = 'dismissible',
        closeBtn = '<button type="button" class="close">×</button>',
        // check container
    containerElement = queryElement(options[container]),
        containerDataElement = queryElement(containerData),
        // maybe the element is inside a modal
    modal = getClosest(element, '.modal'),
        // maybe the element is inside a fixed navbar
    navbarFixedTop = getClosest(element, '.' + fixedTop),
        navbarFixedBottom = getClosest(element, '.' + fixedBottom); // set instance options

    this[template] = options[template] ? options[template] : null; // JavaScript only

    this[trigger] = options[trigger] ? options[trigger] : triggerData || hoverEvent;
    this[animation] = options[animation] && options[animation] !== fade ? options[animation] : animationData || fade;
    this[placement] = options[placement] ? options[placement] : placementData || top;
    this[delay] = parseInt(options[delay] || delayData) || 200;
    this[dismissible] = options[dismissible] || dismissibleData === 'true' ? true : false;
    this[container] = containerElement ? containerElement : containerDataElement ? containerDataElement : navbarFixedTop ? navbarFixedTop : navbarFixedBottom ? navbarFixedBottom : modal ? modal : DOC[body]; // bind, content

    var self = this,
        titleString = element[getAttribute](dataTitle) || null,
        contentString = element[getAttribute](dataContent) || null;
    if (!contentString && !this[template]) return; // invalidate
    // constants, vars

    var popover = null,
        timer = 0,
        placementSetting = this[placement],
        // handlers
    dismissibleHandler = function dismissibleHandler(e) {
      if (popover !== null && e[target] === queryElement('.close', popover)) {
        self.hide();
      }
    },
        // private methods
    removePopover = function removePopover() {
      self[container].removeChild(popover);
      timer = null;
      popover = null;
    },
        createPopover = function createPopover() {
      titleString = element[getAttribute](dataTitle); // check content again

      contentString = element[getAttribute](dataContent);
      popover = DOC[createElement](div); // popover arrow

      var popoverArrow = DOC[createElement](div);
      popoverArrow[setAttribute](classString, 'arrow');
      popover[appendChild](popoverArrow);

      if (contentString !== null && self[template] === null) {
        //create the popover from data attributes
        popover[setAttribute]('role', 'tooltip');

        if (titleString !== null) {
          var popoverTitle = DOC[createElement]('h3');
          popoverTitle[setAttribute](classString, component + '-header');
          popoverTitle[innerHTML] = self[dismissible] ? titleString + closeBtn : titleString;
          popover[appendChild](popoverTitle);
        } //set popover content


        var popoverContent = DOC[createElement](div);
        popoverContent[setAttribute](classString, component + '-body');
        popoverContent[innerHTML] = self[dismissible] && titleString === null ? contentString + closeBtn : contentString;
        popover[appendChild](popoverContent);
      } else {
        // or create the popover from template
        var popoverTemplate = DOC[createElement](div);
        popoverTemplate[innerHTML] = self[template];
        popover[innerHTML] = popoverTemplate.firstChild[innerHTML];
      } //append to the container


      self[container][appendChild](popover);
      popover[style].display = 'block';
      popover[setAttribute](classString, component + ' bs-' + component + '-' + placementSetting + ' ' + self[animation]);
    },
        showPopover = function showPopover() {
      !hasClass(popover, showClass) && addClass(popover, showClass);
    },
        updatePopover = function updatePopover() {
      styleTip(element, popover, placementSetting, self[container]);
    },
        // event toggle
    dismissHandlerToggle = function dismissHandlerToggle(type) {
      if (clickEvent == self[trigger] || 'focus' == self[trigger]) {
        !self[dismissible] && type(element, 'blur', self.hide);
      }

      self[dismissible] && type(DOC, clickEvent, dismissibleHandler);
      type(globalObject, resizeEvent, self.hide);
    },
        // triggers
    showTrigger = function showTrigger() {
      dismissHandlerToggle(on);
      bootstrapCustomEvent.call(element, shownEvent, component);
    },
        hideTrigger = function hideTrigger() {
      dismissHandlerToggle(off);
      removePopover();
      bootstrapCustomEvent.call(element, hiddenEvent, component);
    }; // public methods / handlers


    this.toggle = function () {
      if (popover === null) {
        self.show();
      } else {
        self.hide();
      }
    };

    this.show = function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (popover === null) {
          placementSetting = self[placement]; // we reset placement in all cases

          createPopover();
          updatePopover();
          showPopover();
          bootstrapCustomEvent.call(element, showEvent, component);
          !!self[animation] ? emulateTransitionEnd(popover, showTrigger) : showTrigger();
        }
      }, 20);
    };

    this.hide = function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (popover && popover !== null && hasClass(popover, showClass)) {
          bootstrapCustomEvent.call(element, hideEvent, component);
          removeClass(popover, showClass);
          !!self[animation] ? emulateTransitionEnd(popover, hideTrigger) : hideTrigger();
        }
      }, self[delay]);
    }; // init


    if (!(stringPopover in element)) {
      // prevent adding event handlers twice
      if (self[trigger] === hoverEvent) {
        on(element, mouseHover[0], self.show);

        if (!self[dismissible]) {
          on(element, mouseHover[1], self.hide);
        }
      } else if (clickEvent == self[trigger] || 'focus' == self[trigger]) {
        on(element, self[trigger], self.toggle);
      }
    }

    element[stringPopover] = self;
  }; // POPOVER DATA API
  // ================


  supports[push]([stringPopover, Popover, '[' + dataToggle + '="popover"]']);
  /* Native Javascript for Bootstrap 4 | ScrollSpy
  -----------------------------------------------*/
  // SCROLLSPY DEFINITION
  // ====================

  var ScrollSpy = function ScrollSpy(element, options) {
    // initialization element, the element we spy on
    element = queryElement(element); // DATA API

    var targetData = queryElement(element[getAttribute](dataTarget)),
        offsetData = element[getAttribute]('data-offset'); // set options

    options = options || {};

    if (!options[target] && !targetData) {
      return;
    } // invalidate
    // event targets, constants


    var self = this,
        spyTarget = options[target] && queryElement(options[target]) || targetData,
        links = spyTarget && spyTarget[getElementsByTagName]('A'),
        offset = parseInt(offsetData || options['offset']) || 10,
        items = [],
        targetItems = [],
        scrollOffset,
        scrollTarget = element[offsetHeight] < element[scrollHeight] ? element : globalObject,
        // determine which is the real scrollTarget
    isWindow = scrollTarget === globalObject; // populate items and targets

    for (var i = 0, il = links[length]; i < il; i++) {
      var href = links[i][getAttribute]('href'),
          targetItem = href && href.charAt(0) === '#' && href.slice(-1) !== '#' && queryElement(href);

      if (!!targetItem) {
        items[push](links[i]);
        targetItems[push](targetItem);
      }
    } // private methods


    var updateItem = function updateItem(index) {
      var item = items[index],
          targetItem = targetItems[index],
          // the menu item targets this element
      dropdown = item[parentNode][parentNode],
          dropdownLink = hasClass(dropdown, 'dropdown') && dropdown[getElementsByTagName]('A')[0],
          targetRect = isWindow && targetItem[getBoundingClientRect](),
          isActive = hasClass(item, active) || false,
          topEdge = (isWindow ? targetRect[top] + scrollOffset : targetItem[offsetTop]) - offset,
          bottomEdge = isWindow ? targetRect[bottom] + scrollOffset - offset : targetItems[index + 1] ? targetItems[index + 1][offsetTop] - offset : element[scrollHeight],
          inside = scrollOffset >= topEdge && bottomEdge > scrollOffset;

      if (!isActive && inside) {
        if (!hasClass(item, active)) {
          addClass(item, active);

          if (dropdownLink && !hasClass(dropdownLink, active)) {
            addClass(dropdownLink, active);
          }

          bootstrapCustomEvent.call(element, 'activate', 'scrollspy', items[index]);
        }
      } else if (!inside) {
        if (hasClass(item, active)) {
          removeClass(item, active);

          if (dropdownLink && hasClass(dropdownLink, active) && !getElementsByClassName(item[parentNode], active).length) {
            removeClass(dropdownLink, active);
          }
        }
      } else if (!inside && !isActive || isActive && inside) {
        return;
      }
    },
        updateItems = function updateItems() {
      scrollOffset = isWindow ? getScroll().y : element[scrollTop];

      for (var index = 0, itl = items[length]; index < itl; index++) {
        updateItem(index);
      }
    }; // public method


    this.refresh = function () {
      updateItems();
    }; // init


    if (!(stringScrollSpy in element)) {
      // prevent adding event handlers twice
      on(scrollTarget, scrollEvent, self.refresh);
      on(globalObject, resizeEvent, self.refresh);
    }

    self.refresh();
    element[stringScrollSpy] = self;
  }; // SCROLLSPY DATA API
  // ==================


  supports[push]([stringScrollSpy, ScrollSpy, '[' + dataSpy + '="scroll"]']);
  /* Native Javascript for Bootstrap 4 | Tab
  -----------------------------------------*/
  // TAB DEFINITION
  // ==============

  var Tab = function Tab(element, options) {
    // initialization element
    element = queryElement(element); // DATA API

    var heightData = element[getAttribute](dataHeight),
        // strings
    component = 'tab',
        height = 'height',
        float = 'float',
        isAnimating = 'isAnimating'; // set options

    options = options || {};
    this[height] = supportTransitions ? options[height] || heightData === 'true' : false; // bind, event targets

    var self = this,
        next,
        tabs = getClosest(element, '.nav'),
        tabsContentContainer = false,
        dropdown = tabs && queryElement('.dropdown-toggle', tabs),
        activeTab,
        activeContent,
        nextContent,
        containerHeight,
        equalContents,
        nextHeight,
        // trigger
    triggerEnd = function triggerEnd() {
      tabsContentContainer[style][height] = '';
      removeClass(tabsContentContainer, collapsing);
      tabs[isAnimating] = false;
    },
        triggerShow = function triggerShow() {
      if (tabsContentContainer) {
        // height animation
        if (equalContents) {
          triggerEnd();
        } else {
          setTimeout(function () {
            // enables height animation
            tabsContentContainer[style][height] = nextHeight + 'px'; // height animation

            tabsContentContainer[offsetWidth];
            emulateTransitionEnd(tabsContentContainer, triggerEnd);
          }, 50);
        }
      } else {
        tabs[isAnimating] = false;
      }

      bootstrapCustomEvent.call(next, shownEvent, component, activeTab);
    },
        triggerHide = function triggerHide() {
      if (tabsContentContainer) {
        activeContent[style][float] = left;
        nextContent[style][float] = left;
        containerHeight = activeContent[scrollHeight];
      }

      addClass(nextContent, active);
      bootstrapCustomEvent.call(next, showEvent, component, activeTab);
      removeClass(activeContent, active);
      bootstrapCustomEvent.call(activeTab, hiddenEvent, component, next);

      if (tabsContentContainer) {
        nextHeight = nextContent[scrollHeight];
        equalContents = nextHeight === containerHeight;
        addClass(tabsContentContainer, collapsing);
        tabsContentContainer[style][height] = containerHeight + 'px'; // height animation

        tabsContentContainer[offsetHeight];
        activeContent[style][float] = '';
        nextContent[style][float] = '';
      }

      if (hasClass(nextContent, 'fade')) {
        setTimeout(function () {
          addClass(nextContent, showClass);
          emulateTransitionEnd(nextContent, triggerShow);
        }, 20);
      } else {
        triggerShow();
      }
    };

    if (!tabs) return; // invalidate
    // set default animation state

    tabs[isAnimating] = false; // private methods

    var getActiveTab = function getActiveTab() {
      var activeTabs = getElementsByClassName(tabs, active),
          activeTab;

      if (activeTabs[length] === 1 && !hasClass(activeTabs[0][parentNode], 'dropdown')) {
        activeTab = activeTabs[0];
      } else if (activeTabs[length] > 1) {
        activeTab = activeTabs[activeTabs[length] - 1];
      }

      return activeTab;
    },
        getActiveContent = function getActiveContent() {
      return queryElement(getActiveTab()[getAttribute]('href'));
    },
        // handler 
    clickHandler = function clickHandler(e) {
      e[preventDefault]();
      next = e[currentTarget];
      !tabs[isAnimating] && !hasClass(next, active) && self.show();
    }; // public method


    this.show = function () {
      // the tab we clicked is now the next tab
      next = next || element;
      nextContent = queryElement(next[getAttribute]('href')); //this is the actual object, the next tab content to activate

      activeTab = getActiveTab();
      activeContent = getActiveContent();
      tabs[isAnimating] = true;
      removeClass(activeTab, active);
      activeTab[setAttribute](ariaSelected, 'false');
      addClass(next, active);
      next[setAttribute](ariaSelected, 'true');

      if (dropdown) {
        if (!hasClass(element[parentNode], 'dropdown-menu')) {
          if (hasClass(dropdown, active)) removeClass(dropdown, active);
        } else {
          if (!hasClass(dropdown, active)) addClass(dropdown, active);
        }
      }

      bootstrapCustomEvent.call(activeTab, hideEvent, component, next);

      if (hasClass(activeContent, 'fade')) {
        removeClass(activeContent, showClass);
        emulateTransitionEnd(activeContent, triggerHide);
      } else {
        triggerHide();
      }
    }; // init


    if (!(stringTab in element)) {
      // prevent adding event handlers twice
      on(element, clickEvent, clickHandler);
    }

    if (self[height]) {
      tabsContentContainer = getActiveContent()[parentNode];
    }

    element[stringTab] = self;
  }; // TAB DATA API
  // ============


  supports[push]([stringTab, Tab, '[' + dataToggle + '="tab"]']);
  /* Native Javascript for Bootstrap 4 | Tooltip
  ---------------------------------------------*/
  // TOOLTIP DEFINITION
  // ==================

  var Tooltip = function Tooltip(element, options) {
    // initialization element
    element = queryElement(element); // set options

    options = options || {}; // DATA API

    var animationData = element[getAttribute](dataAnimation),
        placementData = element[getAttribute](dataPlacement),
        delayData = element[getAttribute](dataDelay),
        containerData = element[getAttribute](dataContainer),
        // strings
    component = 'tooltip',
        classString = 'class',
        title = 'title',
        fade = 'fade',
        div = 'div',
        // check container
    containerElement = queryElement(options[container]),
        containerDataElement = queryElement(containerData),
        // maybe the element is inside a modal
    modal = getClosest(element, '.modal'),
        // maybe the element is inside a fixed navbar
    navbarFixedTop = getClosest(element, '.' + fixedTop),
        navbarFixedBottom = getClosest(element, '.' + fixedBottom); // set instance options

    this[animation] = options[animation] && options[animation] !== fade ? options[animation] : animationData || fade;
    this[placement] = options[placement] ? options[placement] : placementData || top;
    this[delay] = parseInt(options[delay] || delayData) || 200;
    this[container] = containerElement ? containerElement : containerDataElement ? containerDataElement : navbarFixedTop ? navbarFixedTop : navbarFixedBottom ? navbarFixedBottom : modal ? modal : DOC[body]; // bind, event targets, title and constants

    var self = this,
        timer = 0,
        placementSetting = this[placement],
        tooltip = null,
        titleString = element[getAttribute](title) || element[getAttribute](dataTitle) || element[getAttribute](dataOriginalTitle);
    if (!titleString || titleString == "") return; // invalidate
    // private methods

    var removeToolTip = function removeToolTip() {
      self[container].removeChild(tooltip);
      tooltip = null;
      timer = null;
    },
        createToolTip = function createToolTip() {
      titleString = element[getAttribute](title) || element[getAttribute](dataTitle) || element[getAttribute](dataOriginalTitle); // read the title again

      if (!titleString || titleString == "") return false; // invalidate

      tooltip = DOC[createElement](div);
      tooltip[setAttribute]('role', component); // tooltip arrow

      var tooltipArrow = DOC[createElement](div);
      tooltipArrow[setAttribute](classString, 'arrow');
      tooltip[appendChild](tooltipArrow);
      var tooltipInner = DOC[createElement](div);
      tooltipInner[setAttribute](classString, component + '-inner');
      tooltip[appendChild](tooltipInner);
      tooltipInner[innerHTML] = titleString;
      self[container][appendChild](tooltip);
      tooltip[setAttribute](classString, component + ' bs-' + component + '-' + placementSetting + ' ' + self[animation]);
    },
        updateTooltip = function updateTooltip() {
      styleTip(element, tooltip, placementSetting, self[container]);
    },
        showTooltip = function showTooltip() {
      !hasClass(tooltip, showClass) && addClass(tooltip, showClass);
    },
        // triggers
    showTrigger = function showTrigger() {
      on(globalObject, resizeEvent, self.hide);
      bootstrapCustomEvent.call(element, shownEvent, component);
    },
        hideTrigger = function hideTrigger() {
      off(globalObject, resizeEvent, self.hide);
      removeToolTip();
      bootstrapCustomEvent.call(element, hiddenEvent, component);
    }; // public methods


    this.show = function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (tooltip === null) {
          placementSetting = self[placement]; // we reset placement in all cases

          if (createToolTip() == false) return;
          updateTooltip();
          showTooltip();
          bootstrapCustomEvent.call(element, showEvent, component);
          !!self[animation] ? emulateTransitionEnd(tooltip, showTrigger) : showTrigger();
        }
      }, 20);
    };

    this.hide = function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (tooltip && hasClass(tooltip, showClass)) {
          bootstrapCustomEvent.call(element, hideEvent, component);
          removeClass(tooltip, showClass);
          !!self[animation] ? emulateTransitionEnd(tooltip, hideTrigger) : hideTrigger();
        }
      }, self[delay]);
    };

    this.toggle = function () {
      if (!tooltip) {
        self.show();
      } else {
        self.hide();
      }
    }; // init


    if (!(stringTooltip in element)) {
      // prevent adding event handlers twice
      element[setAttribute](dataOriginalTitle, titleString);
      element.removeAttribute(title);
      on(element, mouseHover[0], self.show);
      on(element, mouseHover[1], self.hide);
    }

    element[stringTooltip] = self;
  }; // TOOLTIP DATA API
  // =================


  supports[push]([stringTooltip, Tooltip, '[' + dataToggle + '="tooltip"]']);
  /* Native Javascript for Bootstrap 4 | Initialize Data API
  --------------------------------------------------------*/

  var initializeDataAPI = function initializeDataAPI(constructor, collection) {
    for (var i = 0, l = collection[length]; i < l; i++) {
      new constructor(collection[i]);
    }
  },
      initCallback = BSN.initCallback = function (lookUp) {
    lookUp = lookUp || DOC;

    for (var i = 0, l = supports[length]; i < l; i++) {
      initializeDataAPI(supports[i][1], lookUp[querySelectorAll](supports[i][2]));
    }
  }; // bulk initialize all components


  DOC[body] ? initCallback() : on(DOC, 'DOMContentLoaded', function () {
    initCallback();
  });
  return {
    Alert: Alert,
    Button: Button,
    Carousel: Carousel,
    Collapse: Collapse,
    Dropdown: Dropdown,
    Modal: Modal,
    Popover: Popover,
    ScrollSpy: ScrollSpy,
    Tab: Tab,
    Tooltip: Tooltip
  };
});
"use strict";

function _typeof2(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof2 = function _typeof2(obj) { return typeof obj; }; } else { _typeof2 = function _typeof2(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof2(obj); }

var VueTables =
/******/
function (modules) {
  // webpackBootstrap

  /******/
  // The module cache

  /******/
  var installedModules = {};
  /******/

  /******/
  // The require function

  /******/

  function __webpack_require__(moduleId) {
    /******/

    /******/
    // Check if module is in cache

    /******/
    if (installedModules[moduleId]) {
      /******/
      return installedModules[moduleId].exports;
      /******/
    }
    /******/
    // Create a new module (and put it into the cache)

    /******/


    var module = installedModules[moduleId] = {
      /******/
      i: moduleId,

      /******/
      l: false,

      /******/
      exports: {}
      /******/

    };
    /******/

    /******/
    // Execute the module function

    /******/

    modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
    /******/

    /******/
    // Flag the module as loaded

    /******/

    module.l = true;
    /******/

    /******/
    // Return the exports of the module

    /******/

    return module.exports;
    /******/
  }
  /******/

  /******/

  /******/
  // expose the modules object (__webpack_modules__)

  /******/


  __webpack_require__.m = modules;
  /******/

  /******/
  // expose the module cache

  /******/

  __webpack_require__.c = installedModules;
  /******/

  /******/
  // define getter function for harmony exports

  /******/

  __webpack_require__.d = function (exports, name, getter) {
    /******/
    if (!__webpack_require__.o(exports, name)) {
      /******/
      Object.defineProperty(exports, name, {
        /******/
        configurable: false,

        /******/
        enumerable: true,

        /******/
        get: getter
        /******/

      });
      /******/
    }
    /******/

  };
  /******/

  /******/
  // getDefaultExport function for compatibility with non-harmony modules

  /******/


  __webpack_require__.n = function (module) {
    /******/
    var getter = module && module.__esModule ?
    /******/
    function getDefault() {
      return module['default'];
    } :
    /******/
    function getModuleExports() {
      return module;
    };
    /******/

    __webpack_require__.d(getter, 'a', getter);
    /******/


    return getter;
    /******/
  };
  /******/

  /******/
  // Object.prototype.hasOwnProperty.call

  /******/


  __webpack_require__.o = function (object, property) {
    return Object.prototype.hasOwnProperty.call(object, property);
  };
  /******/

  /******/
  // __webpack_public_path__

  /******/


  __webpack_require__.p = "/dist/";
  /******/

  /******/
  // Load entry module and return exports

  /******/

  return __webpack_require__(__webpack_require__.s = 28);
  /******/
}(
/************************************************************************/

/******/
[
/* 0 */

/***/
function (module, exports, __webpack_require__) {
  /* WEBPACK VAR INJECTION */
  (function (module) {
    /*!
    * @name JavaScript/NodeJS Merge v1.2.0
    * @author yeikos
    * @repository https://github.com/yeikos/js.merge
    * Copyright 2014 yeikos - MIT license
    * https://raw.github.com/yeikos/js.merge/master/LICENSE
    */
    ;

    (function (isNode) {
      /**
       * Merge one or more objects 
       * @param bool? clone
       * @param mixed,... arguments
       * @return object
       */
      var Public = function Public(clone) {
        return merge(clone === true, false, arguments);
      },
          publicName = 'merge';
      /**
       * Merge two or more objects recursively 
       * @param bool? clone
       * @param mixed,... arguments
       * @return object
       */


      Public.recursive = function (clone) {
        return merge(clone === true, true, arguments);
      };
      /**
       * Clone the input removing any reference
       * @param mixed input
       * @return mixed
       */


      Public.clone = function (input) {
        var output = input,
            type = typeOf(input),
            index,
            size;

        if (type === 'array') {
          output = [];
          size = input.length;

          for (index = 0; index < size; ++index) {
            output[index] = Public.clone(input[index]);
          }
        } else if (type === 'object') {
          output = {};

          for (index in input) {
            output[index] = Public.clone(input[index]);
          }
        }

        return output;
      };
      /**
       * Merge two objects recursively
       * @param mixed input
       * @param mixed extend
       * @return mixed
       */


      function merge_recursive(base, extend) {
        if (typeOf(base) !== 'object') return extend;

        for (var key in extend) {
          if (typeOf(base[key]) === 'object' && typeOf(extend[key]) === 'object') {
            base[key] = merge_recursive(base[key], extend[key]);
          } else {
            base[key] = extend[key];
          }
        }

        return base;
      }
      /**
       * Merge two or more objects
       * @param bool clone
       * @param bool recursive
       * @param array argv
       * @return object
       */


      function merge(clone, recursive, argv) {
        var result = argv[0],
            size = argv.length;
        if (clone || typeOf(result) !== 'object') result = {};

        for (var index = 0; index < size; ++index) {
          var item = argv[index],
              type = typeOf(item);
          if (type !== 'object') continue;

          for (var key in item) {
            var sitem = clone ? Public.clone(item[key]) : item[key];

            if (recursive) {
              result[key] = merge_recursive(result[key], sitem);
            } else {
              result[key] = sitem;
            }
          }
        }

        return result;
      }
      /**
       * Get type of variable
       * @param mixed input
       * @return string
       *
       * @see http://jsperf.com/typeofvar
       */


      function typeOf(input) {
        return {}.toString.call(input).slice(8, -1).toLowerCase();
      }

      if (isNode) {
        module.exports = Public;
      } else {
        window[publicName] = Public;
      }
    })(_typeof2(module) === 'object' && module && _typeof2(module.exports) === 'object' && module.exports);
    /* WEBPACK VAR INJECTION */

  }).call(exports, __webpack_require__(32)(module));
  /***/
},
/* 1 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _vue = __webpack_require__(9);

  var _vue2 = _interopRequireDefault(_vue);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  var bus = new _vue2.default();
  exports.default = bus;
  /***/
},
/* 2 */

/***/
function (module, exports) {
  /*!
   * Determine if an object is a Buffer
   *
   * @author   Feross Aboukhadijeh <https://feross.org>
   * @license  MIT
   */
  // The _isBuffer check is for Safari 5-7 support, because it's missing
  // Object.prototype.constructor. Remove this eventually
  module.exports = function (obj) {
    return obj != null && (isBuffer(obj) || isSlowBuffer(obj) || !!obj._isBuffer);
  };

  function isBuffer(obj) {
    return !!obj.constructor && typeof obj.constructor.isBuffer === 'function' && obj.constructor.isBuffer(obj);
  } // For Node v0.10 support. Remove this eventually.


  function isSlowBuffer(obj) {
    return typeof obj.readFloatLE === 'function' && typeof obj.slice === 'function' && isBuffer(obj.slice(0, 0));
  }
  /***/

},
/* 3 */

/***/
function (module, exports) {
  /*!
   * is-extglob <https://github.com/jonschlinkert/is-extglob>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */
  module.exports = function isExtglob(str) {
    return typeof str === 'string' && /[@?!+*]\(/.test(str);
  };
  /***/

},
/* 4 */

/***/
function (module, exports, __webpack_require__) {
  /*!
   * is-glob <https://github.com/jonschlinkert/is-glob>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */
  var isExtglob = __webpack_require__(3);

  module.exports = function isGlob(str) {
    return typeof str === 'string' && (/[*!?{}(|)[\]]/.test(str) || isExtglob(str));
  };
  /***/

},
/* 5 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /* WEBPACK VAR INJECTION */

  (function (process) {
    var win32 = process && process.platform === 'win32';

    var path = __webpack_require__(7);

    var fileRe = __webpack_require__(74);

    var utils = module.exports;
    /**
     * Module dependencies
     */

    utils.diff = __webpack_require__(75);
    utils.unique = __webpack_require__(18);
    utils.braces = __webpack_require__(77);
    utils.brackets = __webpack_require__(90);
    utils.extglob = __webpack_require__(92);
    utils.isExtglob = __webpack_require__(3);
    utils.isGlob = __webpack_require__(4);
    utils.typeOf = __webpack_require__(93);
    utils.normalize = __webpack_require__(94);
    utils.omit = __webpack_require__(96);
    utils.parseGlob = __webpack_require__(99);
    utils.cache = __webpack_require__(103);
    /**
     * Get the filename of a filepath
     *
     * @param {String} `string`
     * @return {String}
     */

    utils.filename = function filename(fp) {
      var seg = fp.match(fileRe());
      return seg && seg[0];
    };
    /**
     * Returns a function that returns true if the given
     * pattern is the same as a given `filepath`
     *
     * @param {String} `pattern`
     * @return {Function}
     */


    utils.isPath = function isPath(pattern, opts) {
      opts = opts || {};
      return function (fp) {
        var unixified = utils.unixify(fp, opts);

        if (opts.nocase) {
          return pattern.toLowerCase() === unixified.toLowerCase();
        }

        return pattern === unixified;
      };
    };
    /**
     * Returns a function that returns true if the given
     * pattern contains a `filepath`
     *
     * @param {String} `pattern`
     * @return {Function}
     */


    utils.hasPath = function hasPath(pattern, opts) {
      return function (fp) {
        return utils.unixify(pattern, opts).indexOf(fp) !== -1;
      };
    };
    /**
     * Returns a function that returns true if the given
     * pattern matches or contains a `filepath`
     *
     * @param {String} `pattern`
     * @return {Function}
     */


    utils.matchPath = function matchPath(pattern, opts) {
      var fn = opts && opts.contains ? utils.hasPath(pattern, opts) : utils.isPath(pattern, opts);
      return fn;
    };
    /**
     * Returns a function that returns true if the given
     * regex matches the `filename` of a file path.
     *
     * @param {RegExp} `re`
     * @return {Boolean}
     */


    utils.hasFilename = function hasFilename(re) {
      return function (fp) {
        var name = utils.filename(fp);
        return name && re.test(name);
      };
    };
    /**
     * Coerce `val` to an array
     *
     * @param  {*} val
     * @return {Array}
     */


    utils.arrayify = function arrayify(val) {
      return !Array.isArray(val) ? [val] : val;
    };
    /**
     * Normalize all slashes in a file path or glob pattern to
     * forward slashes.
     */


    utils.unixify = function unixify(fp, opts) {
      if (opts && opts.unixify === false) return fp;

      if (opts && opts.unixify === true || win32 || path.sep === '\\') {
        return utils.normalize(fp, false);
      }

      if (opts && opts.unescape === true) {
        return fp ? fp.toString().replace(/\\(\w)/g, '$1') : '';
      }

      return fp;
    };
    /**
     * Escape/unescape utils
     */


    utils.escapePath = function escapePath(fp) {
      return fp.replace(/[\\.]/g, '\\$&');
    };

    utils.unescapeGlob = function unescapeGlob(fp) {
      return fp.replace(/[\\"']/g, '');
    };

    utils.escapeRe = function escapeRe(str) {
      return str.replace(/[-[\\$*+?.#^\s{}(|)\]]/g, '\\$&');
    };
    /**
     * Expose `utils`
     */


    module.exports = utils;
    /* WEBPACK VAR INJECTION */
  }).call(exports, __webpack_require__(6));
  /***/
},
/* 6 */

/***/
function (module, exports) {
  // shim for using process in browser
  var process = module.exports = {}; // cached from whatever global is present so that test runners that stub it
  // don't break things.  But we need to wrap it in a try catch in case it is
  // wrapped in strict mode code which doesn't define any globals.  It's inside a
  // function because try/catches deoptimize in certain engines.

  var cachedSetTimeout;
  var cachedClearTimeout;

  function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
  }

  function defaultClearTimeout() {
    throw new Error('clearTimeout has not been defined');
  }

  (function () {
    try {
      if (typeof setTimeout === 'function') {
        cachedSetTimeout = setTimeout;
      } else {
        cachedSetTimeout = defaultSetTimout;
      }
    } catch (e) {
      cachedSetTimeout = defaultSetTimout;
    }

    try {
      if (typeof clearTimeout === 'function') {
        cachedClearTimeout = clearTimeout;
      } else {
        cachedClearTimeout = defaultClearTimeout;
      }
    } catch (e) {
      cachedClearTimeout = defaultClearTimeout;
    }
  })();

  function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
      //normal enviroments in sane situations
      return setTimeout(fun, 0);
    } // if setTimeout wasn't available but was latter defined


    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
      cachedSetTimeout = setTimeout;
      return setTimeout(fun, 0);
    }

    try {
      // when when somebody has screwed with setTimeout but no I.E. maddness
      return cachedSetTimeout(fun, 0);
    } catch (e) {
      try {
        // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
        return cachedSetTimeout.call(null, fun, 0);
      } catch (e) {
        // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
        return cachedSetTimeout.call(this, fun, 0);
      }
    }
  }

  function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
      //normal enviroments in sane situations
      return clearTimeout(marker);
    } // if clearTimeout wasn't available but was latter defined


    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
      cachedClearTimeout = clearTimeout;
      return clearTimeout(marker);
    }

    try {
      // when when somebody has screwed with setTimeout but no I.E. maddness
      return cachedClearTimeout(marker);
    } catch (e) {
      try {
        // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
        return cachedClearTimeout.call(null, marker);
      } catch (e) {
        // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
        // Some versions of I.E. have different rules for clearTimeout vs setTimeout
        return cachedClearTimeout.call(this, marker);
      }
    }
  }

  var queue = [];
  var draining = false;
  var currentQueue;
  var queueIndex = -1;

  function cleanUpNextTick() {
    if (!draining || !currentQueue) {
      return;
    }

    draining = false;

    if (currentQueue.length) {
      queue = currentQueue.concat(queue);
    } else {
      queueIndex = -1;
    }

    if (queue.length) {
      drainQueue();
    }
  }

  function drainQueue() {
    if (draining) {
      return;
    }

    var timeout = runTimeout(cleanUpNextTick);
    draining = true;
    var len = queue.length;

    while (len) {
      currentQueue = queue;
      queue = [];

      while (++queueIndex < len) {
        if (currentQueue) {
          currentQueue[queueIndex].run();
        }
      }

      queueIndex = -1;
      len = queue.length;
    }

    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
  }

  process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);

    if (arguments.length > 1) {
      for (var i = 1; i < arguments.length; i++) {
        args[i - 1] = arguments[i];
      }
    }

    queue.push(new Item(fun, args));

    if (queue.length === 1 && !draining) {
      runTimeout(drainQueue);
    }
  }; // v8 likes predictible objects


  function Item(fun, array) {
    this.fun = fun;
    this.array = array;
  }

  Item.prototype.run = function () {
    this.fun.apply(null, this.array);
  };

  process.title = 'browser';
  process.browser = true;
  process.env = {};
  process.argv = [];
  process.version = ''; // empty string to avoid regexp issues

  process.versions = {};

  function noop() {}

  process.on = noop;
  process.addListener = noop;
  process.once = noop;
  process.off = noop;
  process.removeListener = noop;
  process.removeAllListeners = noop;
  process.emit = noop;
  process.prependListener = noop;
  process.prependOnceListener = noop;

  process.listeners = function (name) {
    return [];
  };

  process.binding = function (name) {
    throw new Error('process.binding is not supported');
  };

  process.cwd = function () {
    return '/';
  };

  process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
  };

  process.umask = function () {
    return 0;
  };
  /***/

},
/* 7 */

/***/
function (module, exports, __webpack_require__) {
  /* WEBPACK VAR INJECTION */
  (function (process) {
    // Copyright Joyent, Inc. and other Node contributors.
    //
    // Permission is hereby granted, free of charge, to any person obtaining a
    // copy of this software and associated documentation files (the
    // "Software"), to deal in the Software without restriction, including
    // without limitation the rights to use, copy, modify, merge, publish,
    // distribute, sublicense, and/or sell copies of the Software, and to permit
    // persons to whom the Software is furnished to do so, subject to the
    // following conditions:
    //
    // The above copyright notice and this permission notice shall be included
    // in all copies or substantial portions of the Software.
    //
    // THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
    // OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
    // MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
    // NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
    // DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
    // OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
    // USE OR OTHER DEALINGS IN THE SOFTWARE.
    // resolves . and .. elements in a path array with directory names there
    // must be no slashes, empty elements, or device names (c:\) in the array
    // (so also no leading and trailing slashes - it does not distinguish
    // relative and absolute paths)
    function normalizeArray(parts, allowAboveRoot) {
      // if the path tries to go above the root, `up` ends up > 0
      var up = 0;

      for (var i = parts.length - 1; i >= 0; i--) {
        var last = parts[i];

        if (last === '.') {
          parts.splice(i, 1);
        } else if (last === '..') {
          parts.splice(i, 1);
          up++;
        } else if (up) {
          parts.splice(i, 1);
          up--;
        }
      } // if the path is allowed to go above the root, restore leading ..s


      if (allowAboveRoot) {
        for (; up--; up) {
          parts.unshift('..');
        }
      }

      return parts;
    } // Split a filename into [root, dir, basename, ext], unix version
    // 'root' is just a slash, or nothing.


    var splitPathRe = /^(\/?|)([\s\S]*?)((?:\.{1,2}|[^\/]+?|)(\.[^.\/]*|))(?:[\/]*)$/;

    var splitPath = function splitPath(filename) {
      return splitPathRe.exec(filename).slice(1);
    }; // path.resolve([from ...], to)
    // posix version


    exports.resolve = function () {
      var resolvedPath = '',
          resolvedAbsolute = false;

      for (var i = arguments.length - 1; i >= -1 && !resolvedAbsolute; i--) {
        var path = i >= 0 ? arguments[i] : process.cwd(); // Skip empty and invalid entries

        if (typeof path !== 'string') {
          throw new TypeError('Arguments to path.resolve must be strings');
        } else if (!path) {
          continue;
        }

        resolvedPath = path + '/' + resolvedPath;
        resolvedAbsolute = path.charAt(0) === '/';
      } // At this point the path should be resolved to a full absolute path, but
      // handle relative paths to be safe (might happen when process.cwd() fails)
      // Normalize the path


      resolvedPath = normalizeArray(filter(resolvedPath.split('/'), function (p) {
        return !!p;
      }), !resolvedAbsolute).join('/');
      return (resolvedAbsolute ? '/' : '') + resolvedPath || '.';
    }; // path.normalize(path)
    // posix version


    exports.normalize = function (path) {
      var isAbsolute = exports.isAbsolute(path),
          trailingSlash = substr(path, -1) === '/'; // Normalize the path

      path = normalizeArray(filter(path.split('/'), function (p) {
        return !!p;
      }), !isAbsolute).join('/');

      if (!path && !isAbsolute) {
        path = '.';
      }

      if (path && trailingSlash) {
        path += '/';
      }

      return (isAbsolute ? '/' : '') + path;
    }; // posix version


    exports.isAbsolute = function (path) {
      return path.charAt(0) === '/';
    }; // posix version


    exports.join = function () {
      var paths = Array.prototype.slice.call(arguments, 0);
      return exports.normalize(filter(paths, function (p, index) {
        if (typeof p !== 'string') {
          throw new TypeError('Arguments to path.join must be strings');
        }

        return p;
      }).join('/'));
    }; // path.relative(from, to)
    // posix version


    exports.relative = function (from, to) {
      from = exports.resolve(from).substr(1);
      to = exports.resolve(to).substr(1);

      function trim(arr) {
        var start = 0;

        for (; start < arr.length; start++) {
          if (arr[start] !== '') break;
        }

        var end = arr.length - 1;

        for (; end >= 0; end--) {
          if (arr[end] !== '') break;
        }

        if (start > end) return [];
        return arr.slice(start, end - start + 1);
      }

      var fromParts = trim(from.split('/'));
      var toParts = trim(to.split('/'));
      var length = Math.min(fromParts.length, toParts.length);
      var samePartsLength = length;

      for (var i = 0; i < length; i++) {
        if (fromParts[i] !== toParts[i]) {
          samePartsLength = i;
          break;
        }
      }

      var outputParts = [];

      for (var i = samePartsLength; i < fromParts.length; i++) {
        outputParts.push('..');
      }

      outputParts = outputParts.concat(toParts.slice(samePartsLength));
      return outputParts.join('/');
    };

    exports.sep = '/';
    exports.delimiter = ':';

    exports.dirname = function (path) {
      var result = splitPath(path),
          root = result[0],
          dir = result[1];

      if (!root && !dir) {
        // No dirname whatsoever
        return '.';
      }

      if (dir) {
        // It has a dirname, strip trailing slash
        dir = dir.substr(0, dir.length - 1);
      }

      return root + dir;
    };

    exports.basename = function (path, ext) {
      var f = splitPath(path)[2]; // TODO: make this comparison case-insensitive on windows?

      if (ext && f.substr(-1 * ext.length) === ext) {
        f = f.substr(0, f.length - ext.length);
      }

      return f;
    };

    exports.extname = function (path) {
      return splitPath(path)[3];
    };

    function filter(xs, f) {
      if (xs.filter) return xs.filter(f);
      var res = [];

      for (var i = 0; i < xs.length; i++) {
        if (f(xs[i], i, xs)) res.push(xs[i]);
      }

      return res;
    } // String.prototype.substr - negative index don't work in IE8


    var substr = 'ab'.substr(-1) === 'b' ? function (str, start, len) {
      return str.substr(start, len);
    } : function (str, start, len) {
      if (start < 0) start = str.length + start;
      return str.substr(start, len);
    };
    /* WEBPACK VAR INJECTION */
  }).call(exports, __webpack_require__(6));
  /***/
},
/* 8 */

/***/
function (module, exports) {
  /**
   * Returns a function, that, as long as it continues to be invoked, will not
   * be triggered. The function will be called after it stops being called for
   * N milliseconds. If `immediate` is passed, trigger the function on the
   * leading edge, instead of the trailing. The function also has a property 'clear' 
   * that is a function which will clear the timer to prevent previously scheduled executions. 
   *
   * @source underscore.js
   * @see http://unscriptable.com/2009/03/20/debouncing-javascript-methods/
   * @param {Function} function to wrap
   * @param {Number} timeout in ms (`100`)
   * @param {Boolean} whether to execute at the beginning (`false`)
   * @api public
   */
  module.exports = function debounce(func, wait, immediate) {
    var timeout, args, context, timestamp, result;
    if (null == wait) wait = 100;

    function later() {
      var last = Date.now() - timestamp;

      if (last < wait && last >= 0) {
        timeout = setTimeout(later, wait - last);
      } else {
        timeout = null;

        if (!immediate) {
          result = func.apply(context, args);
          context = args = null;
        }
      }
    }

    ;

    var debounced = function debounced() {
      context = this;
      args = arguments;
      timestamp = Date.now();
      var callNow = immediate && !timeout;
      if (!timeout) timeout = setTimeout(later, wait);

      if (callNow) {
        result = func.apply(context, args);
        context = args = null;
      }

      return result;
    };

    debounced.clear = function () {
      if (timeout) {
        clearTimeout(timeout);
        timeout = null;
      }
    };

    debounced.flush = function () {
      if (timeout) {
        result = func.apply(context, args);
        context = args = null;
        clearTimeout(timeout);
        timeout = null;
      }
    };

    return debounced;
  };
  /***/

},
/* 9 */

/***/
function (module, exports) {
  module.exports = Vue;
  /***/
},
/* 10 */

/***/
function (module, exports, __webpack_require__) {
  var Pagination = __webpack_require__(30);

  var PaginationEvent = __webpack_require__(11);

  module.exports = {
    Pagination: Pagination,
    PaginationEvent: PaginationEvent
    /***/

  };
},
/* 11 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _vue = __webpack_require__(9);

  var _vue2 = _interopRequireDefault(_vue);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  var bus = new _vue2.default();
  module.exports = bus;
  /***/
},
/* 12 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  exports.default = function (source) {
    var extra = source == 'server' ? serverExtra() : clientExtra();
    return _merge2.default.recursive(true, {
      props: {
        name: {
          type: String,
          required: true
        }
      },
      computed: {
        state: function state() {
          return this.$store.state[this.name];
        },
        Page: function Page() {
          return this.state.page;
        },
        count: function count() {
          return this.state.count;
        },
        Columns: function Columns() {
          return this.state.columns;
        },
        tableData: function tableData() {
          return this.state.data;
        },
        page: function page() {
          return this.state.page;
        },
        limit: function limit() {
          return this.state.limit;
        },
        customQueries: function customQueries() {
          return this.state.customQueries;
        },
        query: function query() {
          return this.state.query;
        },
        orderBy: function orderBy() {
          return {
            column: this.state.sortBy,
            ascending: this.state.ascending
          };
        }
      },
      methods: {
        commit: function commit(action, payload) {
          return this.$store.commit(this.name + '/' + action, payload);
        },
        orderByColumn: function orderByColumn(column, ev) {
          if (!this.sortable(column)) return;

          if (ev.shiftKey && this.orderBy.column && this.hasMultiSort) {
            this.setUserMultiSort(column);
          } else {
            var ascending = this.orderBy.column === column ? !this.orderBy.ascending : this._initialOrderAscending(column);
            var orderBy = {
              column: column,
              ascending: ascending
            };
            this.updateState('orderBy', orderBy);
            this.commit('SORT', orderBy);
            this.dispatch('sorted', orderBy);
          }
        },
        setLimit: function setLimit(e) {
          var limit = (typeof e === 'undefined' ? 'undefined' : _typeof(e)) === 'object' ? parseInt(e.target.value) : e;
          this.updateState('perPage', limit);
          this.commit('SET_LIMIT', limit);
          this.dispatch('limit', limit);
        },
        setOrder: function setOrder(column, ascending) {
          this.updateState('orderBy', {
            column: column,
            ascending: ascending
          });
          this.commit('SORT', {
            column: column,
            ascending: ascending
          });
        },
        setPage: function setPage(page) {
          if (!page) {
            page = this.$refs.page.value;
          }

          if (!this.opts.pagination.dropdown) this.$refs.pagination.Page = page;
          this.commit('PAGINATE', page);
        }
      }
    }, extra);
  };

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  function serverExtra() {
    return {
      methods: {
        setData: function setData(data) {
          this.commit('SET_DATA', data);
          setTimeout(function () {
            this.dispatch('loaded', data);
          }.bind(this), 0);
        }
      }
    };
  }

  function clientExtra() {
    return {};
  }
  /***/

},
/* 13 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function () {
    return {
      computed: {
        Columns: function Columns() {
          return this.columns;
        }
      }
    };
  };
  /***/

},
/* 14 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function () {
    return {
      methods: methods,
      computed: computed,
      directives: directives,
      beforeDestroy: beforeDestroy
    };
  };

  var methods = __webpack_require__(37);

  var computed = __webpack_require__(144);

  var directives = __webpack_require__(156);

  var beforeDestroy = __webpack_require__(159);
  /***/

},
/* 15 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (e, dateEvent) {
    // we need to handle the store this.query to make sure we're not mutating outside of it
    var query = this.vuex ? JSON.parse(JSON.stringify(this.query)) : this.query; // in case we pass an object manually (mostly used for init-date-filters should refactor

    if (Object.prototype.toString.call(e).slice(8, -1) == 'Object') {
      query = this.vuex ? JSON.parse(JSON.stringify(e)) : e;
      if (!this.vuex) this.query = query;
      var name = dateEvent.target.name;
      var value = dateEvent.target.value;

      if (name) {
        this.dispatch('filter', {
          name: name,
          value: value
        });
        this.dispatch('filter::' + name, value);
      } else {
        this.dispatch('filter', value);
      }

      this.updateState('query', query);
    } else if (e) {
      var _name = this.getName(e.target.name);

      var _value = e.target.value;

      if (_name) {
        query[_name] = _value;
      } else {
        query = _value;
      }

      if (!this.vuex) this.query = query;

      if (_name) {
        this.dispatch('filter', {
          name: _name,
          value: _value
        });
        this.dispatch('filter::' + _name, _value);
      } else {
        this.dispatch('filter', _value);
      }

      this.updateState('query', query);
    }

    return search(this, query);
  };

  function search(that, query) {
    if (that.vuex) {
      that.commit('SET_FILTER', query);
    } else {
      that.initPagination();

      if (that.opts.pagination.dropdown) {
        that.getData();
      }
    }
  }

  function noDebounce(e, name, opts) {
    return !e || name && (opts.dateColumns.indexOf(name) > -1 || Object.keys(opts.listColumns).indexOf(name) > -1);
  }
  /***/

},
/* 16 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (val) {
    return val && typeof val.isValid == 'function' && val.isValid();
  };
  /***/

},
/* 17 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /* WEBPACK VAR INJECTION */

  (function (global) {
    /*!
    * The buffer module from node.js, for the browser.
    *
    * @author   Feross Aboukhadijeh <feross@feross.org> <http://feross.org>
    * @license  MIT
    */

    /* eslint-disable no-proto */
    var base64 = __webpack_require__(66);

    var ieee754 = __webpack_require__(67);

    var isArray = __webpack_require__(68);

    exports.Buffer = Buffer;
    exports.SlowBuffer = SlowBuffer;
    exports.INSPECT_MAX_BYTES = 50;
    /**
     * If `Buffer.TYPED_ARRAY_SUPPORT`:
     *   === true    Use Uint8Array implementation (fastest)
     *   === false   Use Object implementation (most compatible, even IE6)
     *
     * Browsers that support typed arrays are IE 10+, Firefox 4+, Chrome 7+, Safari 5.1+,
     * Opera 11.6+, iOS 4.2+.
     *
     * Due to various browser bugs, sometimes the Object implementation will be used even
     * when the browser supports typed arrays.
     *
     * Note:
     *
     *   - Firefox 4-29 lacks support for adding new properties to `Uint8Array` instances,
     *     See: https://bugzilla.mozilla.org/show_bug.cgi?id=695438.
     *
     *   - Chrome 9-10 is missing the `TypedArray.prototype.subarray` function.
     *
     *   - IE10 has a broken `TypedArray.prototype.subarray` function which returns arrays of
     *     incorrect length in some situations.
    
     * We detect these buggy browsers and set `Buffer.TYPED_ARRAY_SUPPORT` to `false` so they
     * get the Object implementation, which is slower but behaves correctly.
     */

    Buffer.TYPED_ARRAY_SUPPORT = global.TYPED_ARRAY_SUPPORT !== undefined ? global.TYPED_ARRAY_SUPPORT : typedArraySupport();
    /*
     * Export kMaxLength after typed array support is determined.
     */

    exports.kMaxLength = kMaxLength();

    function typedArraySupport() {
      try {
        var arr = new Uint8Array(1);
        arr.__proto__ = {
          __proto__: Uint8Array.prototype,
          foo: function foo() {
            return 42;
          }
        };
        return arr.foo() === 42 && // typed array instances can be augmented
        typeof arr.subarray === 'function' && // chrome 9-10 lack `subarray`
        arr.subarray(1, 1).byteLength === 0; // ie10 has broken `subarray`
      } catch (e) {
        return false;
      }
    }

    function kMaxLength() {
      return Buffer.TYPED_ARRAY_SUPPORT ? 0x7fffffff : 0x3fffffff;
    }

    function createBuffer(that, length) {
      if (kMaxLength() < length) {
        throw new RangeError('Invalid typed array length');
      }

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        // Return an augmented `Uint8Array` instance, for best performance
        that = new Uint8Array(length);
        that.__proto__ = Buffer.prototype;
      } else {
        // Fallback: Return an object instance of the Buffer class
        if (that === null) {
          that = new Buffer(length);
        }

        that.length = length;
      }

      return that;
    }
    /**
     * The Buffer constructor returns instances of `Uint8Array` that have their
     * prototype changed to `Buffer.prototype`. Furthermore, `Buffer` is a subclass of
     * `Uint8Array`, so the returned instances will have all the node `Buffer` methods
     * and the `Uint8Array` methods. Square bracket notation works as expected -- it
     * returns a single octet.
     *
     * The `Uint8Array` prototype remains unmodified.
     */


    function Buffer(arg, encodingOrOffset, length) {
      if (!Buffer.TYPED_ARRAY_SUPPORT && !(this instanceof Buffer)) {
        return new Buffer(arg, encodingOrOffset, length);
      } // Common case.


      if (typeof arg === 'number') {
        if (typeof encodingOrOffset === 'string') {
          throw new Error('If encoding is specified then the first argument must be a string');
        }

        return allocUnsafe(this, arg);
      }

      return from(this, arg, encodingOrOffset, length);
    }

    Buffer.poolSize = 8192; // not used by this implementation
    // TODO: Legacy, not needed anymore. Remove in next major version.

    Buffer._augment = function (arr) {
      arr.__proto__ = Buffer.prototype;
      return arr;
    };

    function from(that, value, encodingOrOffset, length) {
      if (typeof value === 'number') {
        throw new TypeError('"value" argument must not be a number');
      }

      if (typeof ArrayBuffer !== 'undefined' && value instanceof ArrayBuffer) {
        return fromArrayBuffer(that, value, encodingOrOffset, length);
      }

      if (typeof value === 'string') {
        return fromString(that, value, encodingOrOffset);
      }

      return fromObject(that, value);
    }
    /**
     * Functionally equivalent to Buffer(arg, encoding) but throws a TypeError
     * if value is a number.
     * Buffer.from(str[, encoding])
     * Buffer.from(array)
     * Buffer.from(buffer)
     * Buffer.from(arrayBuffer[, byteOffset[, length]])
     **/


    Buffer.from = function (value, encodingOrOffset, length) {
      return from(null, value, encodingOrOffset, length);
    };

    if (Buffer.TYPED_ARRAY_SUPPORT) {
      Buffer.prototype.__proto__ = Uint8Array.prototype;
      Buffer.__proto__ = Uint8Array;

      if (typeof Symbol !== 'undefined' && Symbol.species && Buffer[Symbol.species] === Buffer) {
        // Fix subarray() in ES2016. See: https://github.com/feross/buffer/pull/97
        Object.defineProperty(Buffer, Symbol.species, {
          value: null,
          configurable: true
        });
      }
    }

    function assertSize(size) {
      if (typeof size !== 'number') {
        throw new TypeError('"size" argument must be a number');
      } else if (size < 0) {
        throw new RangeError('"size" argument must not be negative');
      }
    }

    function alloc(that, size, fill, encoding) {
      assertSize(size);

      if (size <= 0) {
        return createBuffer(that, size);
      }

      if (fill !== undefined) {
        // Only pay attention to encoding if it's a string. This
        // prevents accidentally sending in a number that would
        // be interpretted as a start offset.
        return typeof encoding === 'string' ? createBuffer(that, size).fill(fill, encoding) : createBuffer(that, size).fill(fill);
      }

      return createBuffer(that, size);
    }
    /**
     * Creates a new filled Buffer instance.
     * alloc(size[, fill[, encoding]])
     **/


    Buffer.alloc = function (size, fill, encoding) {
      return alloc(null, size, fill, encoding);
    };

    function allocUnsafe(that, size) {
      assertSize(size);
      that = createBuffer(that, size < 0 ? 0 : checked(size) | 0);

      if (!Buffer.TYPED_ARRAY_SUPPORT) {
        for (var i = 0; i < size; ++i) {
          that[i] = 0;
        }
      }

      return that;
    }
    /**
     * Equivalent to Buffer(num), by default creates a non-zero-filled Buffer instance.
     * */


    Buffer.allocUnsafe = function (size) {
      return allocUnsafe(null, size);
    };
    /**
     * Equivalent to SlowBuffer(num), by default creates a non-zero-filled Buffer instance.
     */


    Buffer.allocUnsafeSlow = function (size) {
      return allocUnsafe(null, size);
    };

    function fromString(that, string, encoding) {
      if (typeof encoding !== 'string' || encoding === '') {
        encoding = 'utf8';
      }

      if (!Buffer.isEncoding(encoding)) {
        throw new TypeError('"encoding" must be a valid string encoding');
      }

      var length = byteLength(string, encoding) | 0;
      that = createBuffer(that, length);
      var actual = that.write(string, encoding);

      if (actual !== length) {
        // Writing a hex string, for example, that contains invalid characters will
        // cause everything after the first invalid character to be ignored. (e.g.
        // 'abxxcd' will be treated as 'ab')
        that = that.slice(0, actual);
      }

      return that;
    }

    function fromArrayLike(that, array) {
      var length = array.length < 0 ? 0 : checked(array.length) | 0;
      that = createBuffer(that, length);

      for (var i = 0; i < length; i += 1) {
        that[i] = array[i] & 255;
      }

      return that;
    }

    function fromArrayBuffer(that, array, byteOffset, length) {
      array.byteLength; // this throws if `array` is not a valid ArrayBuffer

      if (byteOffset < 0 || array.byteLength < byteOffset) {
        throw new RangeError('\'offset\' is out of bounds');
      }

      if (array.byteLength < byteOffset + (length || 0)) {
        throw new RangeError('\'length\' is out of bounds');
      }

      if (byteOffset === undefined && length === undefined) {
        array = new Uint8Array(array);
      } else if (length === undefined) {
        array = new Uint8Array(array, byteOffset);
      } else {
        array = new Uint8Array(array, byteOffset, length);
      }

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        // Return an augmented `Uint8Array` instance, for best performance
        that = array;
        that.__proto__ = Buffer.prototype;
      } else {
        // Fallback: Return an object instance of the Buffer class
        that = fromArrayLike(that, array);
      }

      return that;
    }

    function fromObject(that, obj) {
      if (Buffer.isBuffer(obj)) {
        var len = checked(obj.length) | 0;
        that = createBuffer(that, len);

        if (that.length === 0) {
          return that;
        }

        obj.copy(that, 0, 0, len);
        return that;
      }

      if (obj) {
        if (typeof ArrayBuffer !== 'undefined' && obj.buffer instanceof ArrayBuffer || 'length' in obj) {
          if (typeof obj.length !== 'number' || isnan(obj.length)) {
            return createBuffer(that, 0);
          }

          return fromArrayLike(that, obj);
        }

        if (obj.type === 'Buffer' && isArray(obj.data)) {
          return fromArrayLike(that, obj.data);
        }
      }

      throw new TypeError('First argument must be a string, Buffer, ArrayBuffer, Array, or array-like object.');
    }

    function checked(length) {
      // Note: cannot use `length < kMaxLength()` here because that fails when
      // length is NaN (which is otherwise coerced to zero.)
      if (length >= kMaxLength()) {
        throw new RangeError('Attempt to allocate Buffer larger than maximum ' + 'size: 0x' + kMaxLength().toString(16) + ' bytes');
      }

      return length | 0;
    }

    function SlowBuffer(length) {
      if (+length != length) {
        // eslint-disable-line eqeqeq
        length = 0;
      }

      return Buffer.alloc(+length);
    }

    Buffer.isBuffer = function isBuffer(b) {
      return !!(b != null && b._isBuffer);
    };

    Buffer.compare = function compare(a, b) {
      if (!Buffer.isBuffer(a) || !Buffer.isBuffer(b)) {
        throw new TypeError('Arguments must be Buffers');
      }

      if (a === b) return 0;
      var x = a.length;
      var y = b.length;

      for (var i = 0, len = Math.min(x, y); i < len; ++i) {
        if (a[i] !== b[i]) {
          x = a[i];
          y = b[i];
          break;
        }
      }

      if (x < y) return -1;
      if (y < x) return 1;
      return 0;
    };

    Buffer.isEncoding = function isEncoding(encoding) {
      switch (String(encoding).toLowerCase()) {
        case 'hex':
        case 'utf8':
        case 'utf-8':
        case 'ascii':
        case 'latin1':
        case 'binary':
        case 'base64':
        case 'ucs2':
        case 'ucs-2':
        case 'utf16le':
        case 'utf-16le':
          return true;

        default:
          return false;
      }
    };

    Buffer.concat = function concat(list, length) {
      if (!isArray(list)) {
        throw new TypeError('"list" argument must be an Array of Buffers');
      }

      if (list.length === 0) {
        return Buffer.alloc(0);
      }

      var i;

      if (length === undefined) {
        length = 0;

        for (i = 0; i < list.length; ++i) {
          length += list[i].length;
        }
      }

      var buffer = Buffer.allocUnsafe(length);
      var pos = 0;

      for (i = 0; i < list.length; ++i) {
        var buf = list[i];

        if (!Buffer.isBuffer(buf)) {
          throw new TypeError('"list" argument must be an Array of Buffers');
        }

        buf.copy(buffer, pos);
        pos += buf.length;
      }

      return buffer;
    };

    function byteLength(string, encoding) {
      if (Buffer.isBuffer(string)) {
        return string.length;
      }

      if (typeof ArrayBuffer !== 'undefined' && typeof ArrayBuffer.isView === 'function' && (ArrayBuffer.isView(string) || string instanceof ArrayBuffer)) {
        return string.byteLength;
      }

      if (typeof string !== 'string') {
        string = '' + string;
      }

      var len = string.length;
      if (len === 0) return 0; // Use a for loop to avoid recursion

      var loweredCase = false;

      for (;;) {
        switch (encoding) {
          case 'ascii':
          case 'latin1':
          case 'binary':
            return len;

          case 'utf8':
          case 'utf-8':
          case undefined:
            return utf8ToBytes(string).length;

          case 'ucs2':
          case 'ucs-2':
          case 'utf16le':
          case 'utf-16le':
            return len * 2;

          case 'hex':
            return len >>> 1;

          case 'base64':
            return base64ToBytes(string).length;

          default:
            if (loweredCase) return utf8ToBytes(string).length; // assume utf8

            encoding = ('' + encoding).toLowerCase();
            loweredCase = true;
        }
      }
    }

    Buffer.byteLength = byteLength;

    function slowToString(encoding, start, end) {
      var loweredCase = false; // No need to verify that "this.length <= MAX_UINT32" since it's a read-only
      // property of a typed array.
      // This behaves neither like String nor Uint8Array in that we set start/end
      // to their upper/lower bounds if the value passed is out of range.
      // undefined is handled specially as per ECMA-262 6th Edition,
      // Section 13.3.3.7 Runtime Semantics: KeyedBindingInitialization.

      if (start === undefined || start < 0) {
        start = 0;
      } // Return early if start > this.length. Done here to prevent potential uint32
      // coercion fail below.


      if (start > this.length) {
        return '';
      }

      if (end === undefined || end > this.length) {
        end = this.length;
      }

      if (end <= 0) {
        return '';
      } // Force coersion to uint32. This will also coerce falsey/NaN values to 0.


      end >>>= 0;
      start >>>= 0;

      if (end <= start) {
        return '';
      }

      if (!encoding) encoding = 'utf8';

      while (true) {
        switch (encoding) {
          case 'hex':
            return hexSlice(this, start, end);

          case 'utf8':
          case 'utf-8':
            return utf8Slice(this, start, end);

          case 'ascii':
            return asciiSlice(this, start, end);

          case 'latin1':
          case 'binary':
            return latin1Slice(this, start, end);

          case 'base64':
            return base64Slice(this, start, end);

          case 'ucs2':
          case 'ucs-2':
          case 'utf16le':
          case 'utf-16le':
            return utf16leSlice(this, start, end);

          default:
            if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding);
            encoding = (encoding + '').toLowerCase();
            loweredCase = true;
        }
      }
    } // The property is used by `Buffer.isBuffer` and `is-buffer` (in Safari 5-7) to detect
    // Buffer instances.


    Buffer.prototype._isBuffer = true;

    function swap(b, n, m) {
      var i = b[n];
      b[n] = b[m];
      b[m] = i;
    }

    Buffer.prototype.swap16 = function swap16() {
      var len = this.length;

      if (len % 2 !== 0) {
        throw new RangeError('Buffer size must be a multiple of 16-bits');
      }

      for (var i = 0; i < len; i += 2) {
        swap(this, i, i + 1);
      }

      return this;
    };

    Buffer.prototype.swap32 = function swap32() {
      var len = this.length;

      if (len % 4 !== 0) {
        throw new RangeError('Buffer size must be a multiple of 32-bits');
      }

      for (var i = 0; i < len; i += 4) {
        swap(this, i, i + 3);
        swap(this, i + 1, i + 2);
      }

      return this;
    };

    Buffer.prototype.swap64 = function swap64() {
      var len = this.length;

      if (len % 8 !== 0) {
        throw new RangeError('Buffer size must be a multiple of 64-bits');
      }

      for (var i = 0; i < len; i += 8) {
        swap(this, i, i + 7);
        swap(this, i + 1, i + 6);
        swap(this, i + 2, i + 5);
        swap(this, i + 3, i + 4);
      }

      return this;
    };

    Buffer.prototype.toString = function toString() {
      var length = this.length | 0;
      if (length === 0) return '';
      if (arguments.length === 0) return utf8Slice(this, 0, length);
      return slowToString.apply(this, arguments);
    };

    Buffer.prototype.equals = function equals(b) {
      if (!Buffer.isBuffer(b)) throw new TypeError('Argument must be a Buffer');
      if (this === b) return true;
      return Buffer.compare(this, b) === 0;
    };

    Buffer.prototype.inspect = function inspect() {
      var str = '';
      var max = exports.INSPECT_MAX_BYTES;

      if (this.length > 0) {
        str = this.toString('hex', 0, max).match(/.{2}/g).join(' ');
        if (this.length > max) str += ' ... ';
      }

      return '<Buffer ' + str + '>';
    };

    Buffer.prototype.compare = function compare(target, start, end, thisStart, thisEnd) {
      if (!Buffer.isBuffer(target)) {
        throw new TypeError('Argument must be a Buffer');
      }

      if (start === undefined) {
        start = 0;
      }

      if (end === undefined) {
        end = target ? target.length : 0;
      }

      if (thisStart === undefined) {
        thisStart = 0;
      }

      if (thisEnd === undefined) {
        thisEnd = this.length;
      }

      if (start < 0 || end > target.length || thisStart < 0 || thisEnd > this.length) {
        throw new RangeError('out of range index');
      }

      if (thisStart >= thisEnd && start >= end) {
        return 0;
      }

      if (thisStart >= thisEnd) {
        return -1;
      }

      if (start >= end) {
        return 1;
      }

      start >>>= 0;
      end >>>= 0;
      thisStart >>>= 0;
      thisEnd >>>= 0;
      if (this === target) return 0;
      var x = thisEnd - thisStart;
      var y = end - start;
      var len = Math.min(x, y);
      var thisCopy = this.slice(thisStart, thisEnd);
      var targetCopy = target.slice(start, end);

      for (var i = 0; i < len; ++i) {
        if (thisCopy[i] !== targetCopy[i]) {
          x = thisCopy[i];
          y = targetCopy[i];
          break;
        }
      }

      if (x < y) return -1;
      if (y < x) return 1;
      return 0;
    }; // Finds either the first index of `val` in `buffer` at offset >= `byteOffset`,
    // OR the last index of `val` in `buffer` at offset <= `byteOffset`.
    //
    // Arguments:
    // - buffer - a Buffer to search
    // - val - a string, Buffer, or number
    // - byteOffset - an index into `buffer`; will be clamped to an int32
    // - encoding - an optional encoding, relevant is val is a string
    // - dir - true for indexOf, false for lastIndexOf


    function bidirectionalIndexOf(buffer, val, byteOffset, encoding, dir) {
      // Empty buffer means no match
      if (buffer.length === 0) return -1; // Normalize byteOffset

      if (typeof byteOffset === 'string') {
        encoding = byteOffset;
        byteOffset = 0;
      } else if (byteOffset > 0x7fffffff) {
        byteOffset = 0x7fffffff;
      } else if (byteOffset < -0x80000000) {
        byteOffset = -0x80000000;
      }

      byteOffset = +byteOffset; // Coerce to Number.

      if (isNaN(byteOffset)) {
        // byteOffset: it it's undefined, null, NaN, "foo", etc, search whole buffer
        byteOffset = dir ? 0 : buffer.length - 1;
      } // Normalize byteOffset: negative offsets start from the end of the buffer


      if (byteOffset < 0) byteOffset = buffer.length + byteOffset;

      if (byteOffset >= buffer.length) {
        if (dir) return -1;else byteOffset = buffer.length - 1;
      } else if (byteOffset < 0) {
        if (dir) byteOffset = 0;else return -1;
      } // Normalize val


      if (typeof val === 'string') {
        val = Buffer.from(val, encoding);
      } // Finally, search either indexOf (if dir is true) or lastIndexOf


      if (Buffer.isBuffer(val)) {
        // Special case: looking for empty string/buffer always fails
        if (val.length === 0) {
          return -1;
        }

        return arrayIndexOf(buffer, val, byteOffset, encoding, dir);
      } else if (typeof val === 'number') {
        val = val & 0xFF; // Search for a byte value [0-255]

        if (Buffer.TYPED_ARRAY_SUPPORT && typeof Uint8Array.prototype.indexOf === 'function') {
          if (dir) {
            return Uint8Array.prototype.indexOf.call(buffer, val, byteOffset);
          } else {
            return Uint8Array.prototype.lastIndexOf.call(buffer, val, byteOffset);
          }
        }

        return arrayIndexOf(buffer, [val], byteOffset, encoding, dir);
      }

      throw new TypeError('val must be string, number or Buffer');
    }

    function arrayIndexOf(arr, val, byteOffset, encoding, dir) {
      var indexSize = 1;
      var arrLength = arr.length;
      var valLength = val.length;

      if (encoding !== undefined) {
        encoding = String(encoding).toLowerCase();

        if (encoding === 'ucs2' || encoding === 'ucs-2' || encoding === 'utf16le' || encoding === 'utf-16le') {
          if (arr.length < 2 || val.length < 2) {
            return -1;
          }

          indexSize = 2;
          arrLength /= 2;
          valLength /= 2;
          byteOffset /= 2;
        }
      }

      function read(buf, i) {
        if (indexSize === 1) {
          return buf[i];
        } else {
          return buf.readUInt16BE(i * indexSize);
        }
      }

      var i;

      if (dir) {
        var foundIndex = -1;

        for (i = byteOffset; i < arrLength; i++) {
          if (read(arr, i) === read(val, foundIndex === -1 ? 0 : i - foundIndex)) {
            if (foundIndex === -1) foundIndex = i;
            if (i - foundIndex + 1 === valLength) return foundIndex * indexSize;
          } else {
            if (foundIndex !== -1) i -= i - foundIndex;
            foundIndex = -1;
          }
        }
      } else {
        if (byteOffset + valLength > arrLength) byteOffset = arrLength - valLength;

        for (i = byteOffset; i >= 0; i--) {
          var found = true;

          for (var j = 0; j < valLength; j++) {
            if (read(arr, i + j) !== read(val, j)) {
              found = false;
              break;
            }
          }

          if (found) return i;
        }
      }

      return -1;
    }

    Buffer.prototype.includes = function includes(val, byteOffset, encoding) {
      return this.indexOf(val, byteOffset, encoding) !== -1;
    };

    Buffer.prototype.indexOf = function indexOf(val, byteOffset, encoding) {
      return bidirectionalIndexOf(this, val, byteOffset, encoding, true);
    };

    Buffer.prototype.lastIndexOf = function lastIndexOf(val, byteOffset, encoding) {
      return bidirectionalIndexOf(this, val, byteOffset, encoding, false);
    };

    function hexWrite(buf, string, offset, length) {
      offset = Number(offset) || 0;
      var remaining = buf.length - offset;

      if (!length) {
        length = remaining;
      } else {
        length = Number(length);

        if (length > remaining) {
          length = remaining;
        }
      } // must be an even number of digits


      var strLen = string.length;
      if (strLen % 2 !== 0) throw new TypeError('Invalid hex string');

      if (length > strLen / 2) {
        length = strLen / 2;
      }

      for (var i = 0; i < length; ++i) {
        var parsed = parseInt(string.substr(i * 2, 2), 16);
        if (isNaN(parsed)) return i;
        buf[offset + i] = parsed;
      }

      return i;
    }

    function utf8Write(buf, string, offset, length) {
      return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length);
    }

    function asciiWrite(buf, string, offset, length) {
      return blitBuffer(asciiToBytes(string), buf, offset, length);
    }

    function latin1Write(buf, string, offset, length) {
      return asciiWrite(buf, string, offset, length);
    }

    function base64Write(buf, string, offset, length) {
      return blitBuffer(base64ToBytes(string), buf, offset, length);
    }

    function ucs2Write(buf, string, offset, length) {
      return blitBuffer(utf16leToBytes(string, buf.length - offset), buf, offset, length);
    }

    Buffer.prototype.write = function write(string, offset, length, encoding) {
      // Buffer#write(string)
      if (offset === undefined) {
        encoding = 'utf8';
        length = this.length;
        offset = 0; // Buffer#write(string, encoding)
      } else if (length === undefined && typeof offset === 'string') {
        encoding = offset;
        length = this.length;
        offset = 0; // Buffer#write(string, offset[, length][, encoding])
      } else if (isFinite(offset)) {
        offset = offset | 0;

        if (isFinite(length)) {
          length = length | 0;
          if (encoding === undefined) encoding = 'utf8';
        } else {
          encoding = length;
          length = undefined;
        } // legacy write(string, encoding, offset, length) - remove in v0.13

      } else {
        throw new Error('Buffer.write(string, encoding, offset[, length]) is no longer supported');
      }

      var remaining = this.length - offset;
      if (length === undefined || length > remaining) length = remaining;

      if (string.length > 0 && (length < 0 || offset < 0) || offset > this.length) {
        throw new RangeError('Attempt to write outside buffer bounds');
      }

      if (!encoding) encoding = 'utf8';
      var loweredCase = false;

      for (;;) {
        switch (encoding) {
          case 'hex':
            return hexWrite(this, string, offset, length);

          case 'utf8':
          case 'utf-8':
            return utf8Write(this, string, offset, length);

          case 'ascii':
            return asciiWrite(this, string, offset, length);

          case 'latin1':
          case 'binary':
            return latin1Write(this, string, offset, length);

          case 'base64':
            // Warning: maxLength not taken into account in base64Write
            return base64Write(this, string, offset, length);

          case 'ucs2':
          case 'ucs-2':
          case 'utf16le':
          case 'utf-16le':
            return ucs2Write(this, string, offset, length);

          default:
            if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding);
            encoding = ('' + encoding).toLowerCase();
            loweredCase = true;
        }
      }
    };

    Buffer.prototype.toJSON = function toJSON() {
      return {
        type: 'Buffer',
        data: Array.prototype.slice.call(this._arr || this, 0)
      };
    };

    function base64Slice(buf, start, end) {
      if (start === 0 && end === buf.length) {
        return base64.fromByteArray(buf);
      } else {
        return base64.fromByteArray(buf.slice(start, end));
      }
    }

    function utf8Slice(buf, start, end) {
      end = Math.min(buf.length, end);
      var res = [];
      var i = start;

      while (i < end) {
        var firstByte = buf[i];
        var codePoint = null;
        var bytesPerSequence = firstByte > 0xEF ? 4 : firstByte > 0xDF ? 3 : firstByte > 0xBF ? 2 : 1;

        if (i + bytesPerSequence <= end) {
          var secondByte, thirdByte, fourthByte, tempCodePoint;

          switch (bytesPerSequence) {
            case 1:
              if (firstByte < 0x80) {
                codePoint = firstByte;
              }

              break;

            case 2:
              secondByte = buf[i + 1];

              if ((secondByte & 0xC0) === 0x80) {
                tempCodePoint = (firstByte & 0x1F) << 0x6 | secondByte & 0x3F;

                if (tempCodePoint > 0x7F) {
                  codePoint = tempCodePoint;
                }
              }

              break;

            case 3:
              secondByte = buf[i + 1];
              thirdByte = buf[i + 2];

              if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80) {
                tempCodePoint = (firstByte & 0xF) << 0xC | (secondByte & 0x3F) << 0x6 | thirdByte & 0x3F;

                if (tempCodePoint > 0x7FF && (tempCodePoint < 0xD800 || tempCodePoint > 0xDFFF)) {
                  codePoint = tempCodePoint;
                }
              }

              break;

            case 4:
              secondByte = buf[i + 1];
              thirdByte = buf[i + 2];
              fourthByte = buf[i + 3];

              if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80 && (fourthByte & 0xC0) === 0x80) {
                tempCodePoint = (firstByte & 0xF) << 0x12 | (secondByte & 0x3F) << 0xC | (thirdByte & 0x3F) << 0x6 | fourthByte & 0x3F;

                if (tempCodePoint > 0xFFFF && tempCodePoint < 0x110000) {
                  codePoint = tempCodePoint;
                }
              }

          }
        }

        if (codePoint === null) {
          // we did not generate a valid codePoint so insert a
          // replacement char (U+FFFD) and advance only 1 byte
          codePoint = 0xFFFD;
          bytesPerSequence = 1;
        } else if (codePoint > 0xFFFF) {
          // encode to utf16 (surrogate pair dance)
          codePoint -= 0x10000;
          res.push(codePoint >>> 10 & 0x3FF | 0xD800);
          codePoint = 0xDC00 | codePoint & 0x3FF;
        }

        res.push(codePoint);
        i += bytesPerSequence;
      }

      return decodeCodePointsArray(res);
    } // Based on http://stackoverflow.com/a/22747272/680742, the browser with
    // the lowest limit is Chrome, with 0x10000 args.
    // We go 1 magnitude less, for safety


    var MAX_ARGUMENTS_LENGTH = 0x1000;

    function decodeCodePointsArray(codePoints) {
      var len = codePoints.length;

      if (len <= MAX_ARGUMENTS_LENGTH) {
        return String.fromCharCode.apply(String, codePoints); // avoid extra slice()
      } // Decode in chunks to avoid "call stack size exceeded".


      var res = '';
      var i = 0;

      while (i < len) {
        res += String.fromCharCode.apply(String, codePoints.slice(i, i += MAX_ARGUMENTS_LENGTH));
      }

      return res;
    }

    function asciiSlice(buf, start, end) {
      var ret = '';
      end = Math.min(buf.length, end);

      for (var i = start; i < end; ++i) {
        ret += String.fromCharCode(buf[i] & 0x7F);
      }

      return ret;
    }

    function latin1Slice(buf, start, end) {
      var ret = '';
      end = Math.min(buf.length, end);

      for (var i = start; i < end; ++i) {
        ret += String.fromCharCode(buf[i]);
      }

      return ret;
    }

    function hexSlice(buf, start, end) {
      var len = buf.length;
      if (!start || start < 0) start = 0;
      if (!end || end < 0 || end > len) end = len;
      var out = '';

      for (var i = start; i < end; ++i) {
        out += toHex(buf[i]);
      }

      return out;
    }

    function utf16leSlice(buf, start, end) {
      var bytes = buf.slice(start, end);
      var res = '';

      for (var i = 0; i < bytes.length; i += 2) {
        res += String.fromCharCode(bytes[i] + bytes[i + 1] * 256);
      }

      return res;
    }

    Buffer.prototype.slice = function slice(start, end) {
      var len = this.length;
      start = ~~start;
      end = end === undefined ? len : ~~end;

      if (start < 0) {
        start += len;
        if (start < 0) start = 0;
      } else if (start > len) {
        start = len;
      }

      if (end < 0) {
        end += len;
        if (end < 0) end = 0;
      } else if (end > len) {
        end = len;
      }

      if (end < start) end = start;
      var newBuf;

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        newBuf = this.subarray(start, end);
        newBuf.__proto__ = Buffer.prototype;
      } else {
        var sliceLen = end - start;
        newBuf = new Buffer(sliceLen, undefined);

        for (var i = 0; i < sliceLen; ++i) {
          newBuf[i] = this[i + start];
        }
      }

      return newBuf;
    };
    /*
     * Need to make sure that buffer isn't trying to write out of bounds.
     */


    function checkOffset(offset, ext, length) {
      if (offset % 1 !== 0 || offset < 0) throw new RangeError('offset is not uint');
      if (offset + ext > length) throw new RangeError('Trying to access beyond buffer length');
    }

    Buffer.prototype.readUIntLE = function readUIntLE(offset, byteLength, noAssert) {
      offset = offset | 0;
      byteLength = byteLength | 0;
      if (!noAssert) checkOffset(offset, byteLength, this.length);
      var val = this[offset];
      var mul = 1;
      var i = 0;

      while (++i < byteLength && (mul *= 0x100)) {
        val += this[offset + i] * mul;
      }

      return val;
    };

    Buffer.prototype.readUIntBE = function readUIntBE(offset, byteLength, noAssert) {
      offset = offset | 0;
      byteLength = byteLength | 0;

      if (!noAssert) {
        checkOffset(offset, byteLength, this.length);
      }

      var val = this[offset + --byteLength];
      var mul = 1;

      while (byteLength > 0 && (mul *= 0x100)) {
        val += this[offset + --byteLength] * mul;
      }

      return val;
    };

    Buffer.prototype.readUInt8 = function readUInt8(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 1, this.length);
      return this[offset];
    };

    Buffer.prototype.readUInt16LE = function readUInt16LE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 2, this.length);
      return this[offset] | this[offset + 1] << 8;
    };

    Buffer.prototype.readUInt16BE = function readUInt16BE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 2, this.length);
      return this[offset] << 8 | this[offset + 1];
    };

    Buffer.prototype.readUInt32LE = function readUInt32LE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 4, this.length);
      return (this[offset] | this[offset + 1] << 8 | this[offset + 2] << 16) + this[offset + 3] * 0x1000000;
    };

    Buffer.prototype.readUInt32BE = function readUInt32BE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 4, this.length);
      return this[offset] * 0x1000000 + (this[offset + 1] << 16 | this[offset + 2] << 8 | this[offset + 3]);
    };

    Buffer.prototype.readIntLE = function readIntLE(offset, byteLength, noAssert) {
      offset = offset | 0;
      byteLength = byteLength | 0;
      if (!noAssert) checkOffset(offset, byteLength, this.length);
      var val = this[offset];
      var mul = 1;
      var i = 0;

      while (++i < byteLength && (mul *= 0x100)) {
        val += this[offset + i] * mul;
      }

      mul *= 0x80;
      if (val >= mul) val -= Math.pow(2, 8 * byteLength);
      return val;
    };

    Buffer.prototype.readIntBE = function readIntBE(offset, byteLength, noAssert) {
      offset = offset | 0;
      byteLength = byteLength | 0;
      if (!noAssert) checkOffset(offset, byteLength, this.length);
      var i = byteLength;
      var mul = 1;
      var val = this[offset + --i];

      while (i > 0 && (mul *= 0x100)) {
        val += this[offset + --i] * mul;
      }

      mul *= 0x80;
      if (val >= mul) val -= Math.pow(2, 8 * byteLength);
      return val;
    };

    Buffer.prototype.readInt8 = function readInt8(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 1, this.length);
      if (!(this[offset] & 0x80)) return this[offset];
      return (0xff - this[offset] + 1) * -1;
    };

    Buffer.prototype.readInt16LE = function readInt16LE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 2, this.length);
      var val = this[offset] | this[offset + 1] << 8;
      return val & 0x8000 ? val | 0xFFFF0000 : val;
    };

    Buffer.prototype.readInt16BE = function readInt16BE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 2, this.length);
      var val = this[offset + 1] | this[offset] << 8;
      return val & 0x8000 ? val | 0xFFFF0000 : val;
    };

    Buffer.prototype.readInt32LE = function readInt32LE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 4, this.length);
      return this[offset] | this[offset + 1] << 8 | this[offset + 2] << 16 | this[offset + 3] << 24;
    };

    Buffer.prototype.readInt32BE = function readInt32BE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 4, this.length);
      return this[offset] << 24 | this[offset + 1] << 16 | this[offset + 2] << 8 | this[offset + 3];
    };

    Buffer.prototype.readFloatLE = function readFloatLE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 4, this.length);
      return ieee754.read(this, offset, true, 23, 4);
    };

    Buffer.prototype.readFloatBE = function readFloatBE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 4, this.length);
      return ieee754.read(this, offset, false, 23, 4);
    };

    Buffer.prototype.readDoubleLE = function readDoubleLE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 8, this.length);
      return ieee754.read(this, offset, true, 52, 8);
    };

    Buffer.prototype.readDoubleBE = function readDoubleBE(offset, noAssert) {
      if (!noAssert) checkOffset(offset, 8, this.length);
      return ieee754.read(this, offset, false, 52, 8);
    };

    function checkInt(buf, value, offset, ext, max, min) {
      if (!Buffer.isBuffer(buf)) throw new TypeError('"buffer" argument must be a Buffer instance');
      if (value > max || value < min) throw new RangeError('"value" argument is out of bounds');
      if (offset + ext > buf.length) throw new RangeError('Index out of range');
    }

    Buffer.prototype.writeUIntLE = function writeUIntLE(value, offset, byteLength, noAssert) {
      value = +value;
      offset = offset | 0;
      byteLength = byteLength | 0;

      if (!noAssert) {
        var maxBytes = Math.pow(2, 8 * byteLength) - 1;
        checkInt(this, value, offset, byteLength, maxBytes, 0);
      }

      var mul = 1;
      var i = 0;
      this[offset] = value & 0xFF;

      while (++i < byteLength && (mul *= 0x100)) {
        this[offset + i] = value / mul & 0xFF;
      }

      return offset + byteLength;
    };

    Buffer.prototype.writeUIntBE = function writeUIntBE(value, offset, byteLength, noAssert) {
      value = +value;
      offset = offset | 0;
      byteLength = byteLength | 0;

      if (!noAssert) {
        var maxBytes = Math.pow(2, 8 * byteLength) - 1;
        checkInt(this, value, offset, byteLength, maxBytes, 0);
      }

      var i = byteLength - 1;
      var mul = 1;
      this[offset + i] = value & 0xFF;

      while (--i >= 0 && (mul *= 0x100)) {
        this[offset + i] = value / mul & 0xFF;
      }

      return offset + byteLength;
    };

    Buffer.prototype.writeUInt8 = function writeUInt8(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 1, 0xff, 0);
      if (!Buffer.TYPED_ARRAY_SUPPORT) value = Math.floor(value);
      this[offset] = value & 0xff;
      return offset + 1;
    };

    function objectWriteUInt16(buf, value, offset, littleEndian) {
      if (value < 0) value = 0xffff + value + 1;

      for (var i = 0, j = Math.min(buf.length - offset, 2); i < j; ++i) {
        buf[offset + i] = (value & 0xff << 8 * (littleEndian ? i : 1 - i)) >>> (littleEndian ? i : 1 - i) * 8;
      }
    }

    Buffer.prototype.writeUInt16LE = function writeUInt16LE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value & 0xff;
        this[offset + 1] = value >>> 8;
      } else {
        objectWriteUInt16(this, value, offset, true);
      }

      return offset + 2;
    };

    Buffer.prototype.writeUInt16BE = function writeUInt16BE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value >>> 8;
        this[offset + 1] = value & 0xff;
      } else {
        objectWriteUInt16(this, value, offset, false);
      }

      return offset + 2;
    };

    function objectWriteUInt32(buf, value, offset, littleEndian) {
      if (value < 0) value = 0xffffffff + value + 1;

      for (var i = 0, j = Math.min(buf.length - offset, 4); i < j; ++i) {
        buf[offset + i] = value >>> (littleEndian ? i : 3 - i) * 8 & 0xff;
      }
    }

    Buffer.prototype.writeUInt32LE = function writeUInt32LE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset + 3] = value >>> 24;
        this[offset + 2] = value >>> 16;
        this[offset + 1] = value >>> 8;
        this[offset] = value & 0xff;
      } else {
        objectWriteUInt32(this, value, offset, true);
      }

      return offset + 4;
    };

    Buffer.prototype.writeUInt32BE = function writeUInt32BE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value >>> 24;
        this[offset + 1] = value >>> 16;
        this[offset + 2] = value >>> 8;
        this[offset + 3] = value & 0xff;
      } else {
        objectWriteUInt32(this, value, offset, false);
      }

      return offset + 4;
    };

    Buffer.prototype.writeIntLE = function writeIntLE(value, offset, byteLength, noAssert) {
      value = +value;
      offset = offset | 0;

      if (!noAssert) {
        var limit = Math.pow(2, 8 * byteLength - 1);
        checkInt(this, value, offset, byteLength, limit - 1, -limit);
      }

      var i = 0;
      var mul = 1;
      var sub = 0;
      this[offset] = value & 0xFF;

      while (++i < byteLength && (mul *= 0x100)) {
        if (value < 0 && sub === 0 && this[offset + i - 1] !== 0) {
          sub = 1;
        }

        this[offset + i] = (value / mul >> 0) - sub & 0xFF;
      }

      return offset + byteLength;
    };

    Buffer.prototype.writeIntBE = function writeIntBE(value, offset, byteLength, noAssert) {
      value = +value;
      offset = offset | 0;

      if (!noAssert) {
        var limit = Math.pow(2, 8 * byteLength - 1);
        checkInt(this, value, offset, byteLength, limit - 1, -limit);
      }

      var i = byteLength - 1;
      var mul = 1;
      var sub = 0;
      this[offset + i] = value & 0xFF;

      while (--i >= 0 && (mul *= 0x100)) {
        if (value < 0 && sub === 0 && this[offset + i + 1] !== 0) {
          sub = 1;
        }

        this[offset + i] = (value / mul >> 0) - sub & 0xFF;
      }

      return offset + byteLength;
    };

    Buffer.prototype.writeInt8 = function writeInt8(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 1, 0x7f, -0x80);
      if (!Buffer.TYPED_ARRAY_SUPPORT) value = Math.floor(value);
      if (value < 0) value = 0xff + value + 1;
      this[offset] = value & 0xff;
      return offset + 1;
    };

    Buffer.prototype.writeInt16LE = function writeInt16LE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value & 0xff;
        this[offset + 1] = value >>> 8;
      } else {
        objectWriteUInt16(this, value, offset, true);
      }

      return offset + 2;
    };

    Buffer.prototype.writeInt16BE = function writeInt16BE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value >>> 8;
        this[offset + 1] = value & 0xff;
      } else {
        objectWriteUInt16(this, value, offset, false);
      }

      return offset + 2;
    };

    Buffer.prototype.writeInt32LE = function writeInt32LE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000);

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value & 0xff;
        this[offset + 1] = value >>> 8;
        this[offset + 2] = value >>> 16;
        this[offset + 3] = value >>> 24;
      } else {
        objectWriteUInt32(this, value, offset, true);
      }

      return offset + 4;
    };

    Buffer.prototype.writeInt32BE = function writeInt32BE(value, offset, noAssert) {
      value = +value;
      offset = offset | 0;
      if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000);
      if (value < 0) value = 0xffffffff + value + 1;

      if (Buffer.TYPED_ARRAY_SUPPORT) {
        this[offset] = value >>> 24;
        this[offset + 1] = value >>> 16;
        this[offset + 2] = value >>> 8;
        this[offset + 3] = value & 0xff;
      } else {
        objectWriteUInt32(this, value, offset, false);
      }

      return offset + 4;
    };

    function checkIEEE754(buf, value, offset, ext, max, min) {
      if (offset + ext > buf.length) throw new RangeError('Index out of range');
      if (offset < 0) throw new RangeError('Index out of range');
    }

    function writeFloat(buf, value, offset, littleEndian, noAssert) {
      if (!noAssert) {
        checkIEEE754(buf, value, offset, 4, 3.4028234663852886e+38, -3.4028234663852886e+38);
      }

      ieee754.write(buf, value, offset, littleEndian, 23, 4);
      return offset + 4;
    }

    Buffer.prototype.writeFloatLE = function writeFloatLE(value, offset, noAssert) {
      return writeFloat(this, value, offset, true, noAssert);
    };

    Buffer.prototype.writeFloatBE = function writeFloatBE(value, offset, noAssert) {
      return writeFloat(this, value, offset, false, noAssert);
    };

    function writeDouble(buf, value, offset, littleEndian, noAssert) {
      if (!noAssert) {
        checkIEEE754(buf, value, offset, 8, 1.7976931348623157E+308, -1.7976931348623157E+308);
      }

      ieee754.write(buf, value, offset, littleEndian, 52, 8);
      return offset + 8;
    }

    Buffer.prototype.writeDoubleLE = function writeDoubleLE(value, offset, noAssert) {
      return writeDouble(this, value, offset, true, noAssert);
    };

    Buffer.prototype.writeDoubleBE = function writeDoubleBE(value, offset, noAssert) {
      return writeDouble(this, value, offset, false, noAssert);
    }; // copy(targetBuffer, targetStart=0, sourceStart=0, sourceEnd=buffer.length)


    Buffer.prototype.copy = function copy(target, targetStart, start, end) {
      if (!start) start = 0;
      if (!end && end !== 0) end = this.length;
      if (targetStart >= target.length) targetStart = target.length;
      if (!targetStart) targetStart = 0;
      if (end > 0 && end < start) end = start; // Copy 0 bytes; we're done

      if (end === start) return 0;
      if (target.length === 0 || this.length === 0) return 0; // Fatal error conditions

      if (targetStart < 0) {
        throw new RangeError('targetStart out of bounds');
      }

      if (start < 0 || start >= this.length) throw new RangeError('sourceStart out of bounds');
      if (end < 0) throw new RangeError('sourceEnd out of bounds'); // Are we oob?

      if (end > this.length) end = this.length;

      if (target.length - targetStart < end - start) {
        end = target.length - targetStart + start;
      }

      var len = end - start;
      var i;

      if (this === target && start < targetStart && targetStart < end) {
        // descending copy from end
        for (i = len - 1; i >= 0; --i) {
          target[i + targetStart] = this[i + start];
        }
      } else if (len < 1000 || !Buffer.TYPED_ARRAY_SUPPORT) {
        // ascending copy from start
        for (i = 0; i < len; ++i) {
          target[i + targetStart] = this[i + start];
        }
      } else {
        Uint8Array.prototype.set.call(target, this.subarray(start, start + len), targetStart);
      }

      return len;
    }; // Usage:
    //    buffer.fill(number[, offset[, end]])
    //    buffer.fill(buffer[, offset[, end]])
    //    buffer.fill(string[, offset[, end]][, encoding])


    Buffer.prototype.fill = function fill(val, start, end, encoding) {
      // Handle string cases:
      if (typeof val === 'string') {
        if (typeof start === 'string') {
          encoding = start;
          start = 0;
          end = this.length;
        } else if (typeof end === 'string') {
          encoding = end;
          end = this.length;
        }

        if (val.length === 1) {
          var code = val.charCodeAt(0);

          if (code < 256) {
            val = code;
          }
        }

        if (encoding !== undefined && typeof encoding !== 'string') {
          throw new TypeError('encoding must be a string');
        }

        if (typeof encoding === 'string' && !Buffer.isEncoding(encoding)) {
          throw new TypeError('Unknown encoding: ' + encoding);
        }
      } else if (typeof val === 'number') {
        val = val & 255;
      } // Invalid ranges are not set to a default, so can range check early.


      if (start < 0 || this.length < start || this.length < end) {
        throw new RangeError('Out of range index');
      }

      if (end <= start) {
        return this;
      }

      start = start >>> 0;
      end = end === undefined ? this.length : end >>> 0;
      if (!val) val = 0;
      var i;

      if (typeof val === 'number') {
        for (i = start; i < end; ++i) {
          this[i] = val;
        }
      } else {
        var bytes = Buffer.isBuffer(val) ? val : utf8ToBytes(new Buffer(val, encoding).toString());
        var len = bytes.length;

        for (i = 0; i < end - start; ++i) {
          this[i + start] = bytes[i % len];
        }
      }

      return this;
    }; // HELPER FUNCTIONS
    // ================


    var INVALID_BASE64_RE = /[^+\/0-9A-Za-z-_]/g;

    function base64clean(str) {
      // Node strips out invalid characters like \n and \t from the string, base64-js does not
      str = stringtrim(str).replace(INVALID_BASE64_RE, ''); // Node converts strings with length < 2 to ''

      if (str.length < 2) return ''; // Node allows for non-padded base64 strings (missing trailing ===), base64-js does not

      while (str.length % 4 !== 0) {
        str = str + '=';
      }

      return str;
    }

    function stringtrim(str) {
      if (str.trim) return str.trim();
      return str.replace(/^\s+|\s+$/g, '');
    }

    function toHex(n) {
      if (n < 16) return '0' + n.toString(16);
      return n.toString(16);
    }

    function utf8ToBytes(string, units) {
      units = units || Infinity;
      var codePoint;
      var length = string.length;
      var leadSurrogate = null;
      var bytes = [];

      for (var i = 0; i < length; ++i) {
        codePoint = string.charCodeAt(i); // is surrogate component

        if (codePoint > 0xD7FF && codePoint < 0xE000) {
          // last char was a lead
          if (!leadSurrogate) {
            // no lead yet
            if (codePoint > 0xDBFF) {
              // unexpected trail
              if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
              continue;
            } else if (i + 1 === length) {
              // unpaired lead
              if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
              continue;
            } // valid lead


            leadSurrogate = codePoint;
            continue;
          } // 2 leads in a row


          if (codePoint < 0xDC00) {
            if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
            leadSurrogate = codePoint;
            continue;
          } // valid surrogate pair


          codePoint = (leadSurrogate - 0xD800 << 10 | codePoint - 0xDC00) + 0x10000;
        } else if (leadSurrogate) {
          // valid bmp char, but last char was a lead
          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD);
        }

        leadSurrogate = null; // encode utf8

        if (codePoint < 0x80) {
          if ((units -= 1) < 0) break;
          bytes.push(codePoint);
        } else if (codePoint < 0x800) {
          if ((units -= 2) < 0) break;
          bytes.push(codePoint >> 0x6 | 0xC0, codePoint & 0x3F | 0x80);
        } else if (codePoint < 0x10000) {
          if ((units -= 3) < 0) break;
          bytes.push(codePoint >> 0xC | 0xE0, codePoint >> 0x6 & 0x3F | 0x80, codePoint & 0x3F | 0x80);
        } else if (codePoint < 0x110000) {
          if ((units -= 4) < 0) break;
          bytes.push(codePoint >> 0x12 | 0xF0, codePoint >> 0xC & 0x3F | 0x80, codePoint >> 0x6 & 0x3F | 0x80, codePoint & 0x3F | 0x80);
        } else {
          throw new Error('Invalid code point');
        }
      }

      return bytes;
    }

    function asciiToBytes(str) {
      var byteArray = [];

      for (var i = 0; i < str.length; ++i) {
        // Node's code seems to be doing this and not & 0x7F..
        byteArray.push(str.charCodeAt(i) & 0xFF);
      }

      return byteArray;
    }

    function utf16leToBytes(str, units) {
      var c, hi, lo;
      var byteArray = [];

      for (var i = 0; i < str.length; ++i) {
        if ((units -= 2) < 0) break;
        c = str.charCodeAt(i);
        hi = c >> 8;
        lo = c % 256;
        byteArray.push(lo);
        byteArray.push(hi);
      }

      return byteArray;
    }

    function base64ToBytes(str) {
      return base64.toByteArray(base64clean(str));
    }

    function blitBuffer(src, dst, offset, length) {
      for (var i = 0; i < length; ++i) {
        if (i + offset >= dst.length || i >= src.length) break;
        dst[i + offset] = src[i];
      }

      return i;
    }

    function isnan(val) {
      return val !== val; // eslint-disable-line no-self-compare
    }
    /* WEBPACK VAR INJECTION */

  }).call(exports, __webpack_require__(65));
  /***/
},
/* 18 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * array-unique <https://github.com/jonschlinkert/array-unique>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  module.exports = function unique(arr) {
    if (!Array.isArray(arr)) {
      throw new TypeError('array-unique expects an array.');
    }

    var len = arr.length;
    var i = -1;

    while (i++ < len) {
      var j = i + 1;

      for (; j < arr.length; ++j) {
        if (arr[i] === arr[j]) {
          arr.splice(j--, 1);
        }
      }
    }

    return arr;
  };
  /***/

},
/* 19 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * repeat-element <https://github.com/jonschlinkert/repeat-element>
   *
   * Copyright (c) 2015 Jon Schlinkert.
   * Licensed under the MIT license.
   */

  module.exports = function repeat(ele, num) {
    var arr = new Array(num);

    for (var i = 0; i < num; i++) {
      arr[i] = ele;
    }

    return arr;
  };
  /***/

},
/* 20 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * for-own <https://github.com/jonschlinkert/for-own>
   *
   * Copyright (c) 2014-2017, Jon Schlinkert.
   * Released under the MIT License.
   */

  var forIn = __webpack_require__(98);

  var hasOwn = Object.prototype.hasOwnProperty;

  module.exports = function forOwn(obj, fn, thisArg) {
    forIn(obj, function (val, key) {
      if (hasOwn.call(obj, key)) {
        return fn.call(thisArg, obj[key], key, obj);
      }
    });
  };
  /***/

},
/* 21 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function (useVuex, source) {
    var page = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 1;
    var data = {
      vuex: true,
      activeState: false,
      userColumnsDisplay: [],
      userControlsColumns: false,
      displayColumnsDropdown: false,
      collapsedGroups: []
    };
    if (useVuex) return data;
    data = (0, _merge2.default)(data, {
      vuex: false,
      count: 0,
      customQueries: {},
      query: null,
      page: page,
      limit: 10,
      windowWidth: typeof window !== 'undefined' ? window.innerWidth : null,
      orderBy: {
        column: false,
        ascending: true
      }
    });
    if (source == 'server') data.data = [];
    return data;
  };

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }
  /***/

},
/* 22 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return {
      id: makeId(),
      allFilteredData: [],
      openChildRows: [],
      windowWidth: typeof window !== 'undefined' ? window.innerWidth : null,
      userMultiSorting: {}
    };
  };

  function makeId() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for (var i = 0; i < 5; i++) {
      text += possible.charAt(Math.floor(Math.random() * possible.length));
    }

    return text;
  }
  /***/

},
/* 23 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var is_empty = __webpack_require__(160);

  var registerVuexModule = __webpack_require__(161);

  module.exports = function (self) {
    if (self.vuex) {
      registerVuexModule(self);
    } else {
      self.limit = self.opts.perPage;
    }

    if (is_empty(self.opts.columnsDisplay) || typeof window === 'undefined') return;
    self.columnsDisplay = getColumnsDisplay(self.opts.columnsDisplay);
    window.addEventListener('resize', function () {
      self.windowWidth = window.innerWidth;
    }.bind(self));
  };

  function getColumnsDisplay(columnsDisplay) {
    var res = {};
    var range;
    var device;
    var operator;

    for (var column in columnsDisplay) {
      operator = getOperator(columnsDisplay[column]);

      try {
        device = getDevice(columnsDisplay[column]);
        range = getRange(device, operator);
        res[column] = range.concat([operator]);
      } catch (err) {
        console.warn('Unknown device ' + device);
      }
    }

    return res;
  }

  function getRange(device, operator) {
    var devices = {
      desktop: [1024, null],
      tablet: [480, 1024],
      mobile: [0, 480],
      tabletL: [768, 1024],
      tabletP: [480, 768],
      mobileL: [320, 480],
      mobileP: [0, 320]
    };

    switch (operator) {
      case 'min':
        return [devices[device][0], null];

      case 'max':
        return [0, devices[device][1]];

      default:
        return devices[device];
    }
  }

  function getOperator(val) {
    var pieces = val.split('_');
    if (['not', 'min', 'max'].indexOf(pieces[0]) > -1) return pieces[0];
    return false;
  }

  function getDevice(val) {
    var pieces = val.split('_');
    return pieces.length > 1 ? pieces[1] : pieces[0];
  }
  /***/

},
/* 24 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function (template, theme) {
    var themes = {
      bootstrap3: __webpack_require__(164)(),
      bootstrap4: __webpack_require__(165)(),
      bulma: __webpack_require__(166)()
    };
    var templates = {
      default: __webpack_require__(167),
      footerPagination: __webpack_require__(168)
    };
    return function (h) {
      var modules = {
        rows: __webpack_require__(169).call(this, h),
        normalFilter: __webpack_require__(170).call(this, h),
        dropdownPagination: __webpack_require__(171).call(this, h),
        dropdownPaginationCount: __webpack_require__(172).call(this, h),
        columnFilters: __webpack_require__(173).call(this, h),
        pagination: __webpack_require__(177).call(this, h),
        headings: __webpack_require__(178).call(this, h),
        perPage: __webpack_require__(180).call(this, h),
        columnsDropdown: __webpack_require__(181).call(this, h)
      };

      if (typeof template === 'string' && (!templates[template] || typeof templates[template] !== 'function')) {
        throw 'vue-tables-2: Template "' + template + '" does not exist';
      }

      if (typeof theme === 'string' && (!themes[theme] || _typeof(themes[theme]) !== 'object')) {
        throw 'vue-tables-2: Theme "' + theme + '" does not exist';
      }

      var tpl = typeof template === 'string' ? templates[template] : template;
      var thm = typeof theme === 'string' ? themes[theme] : theme();

      var slots = __webpack_require__(184).call(this);

      return tpl.call(this, h, modules, thm, slots);
    };
  };
  /***/

},
/* 25 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    var perpageValues = [];
    this.opts.perPageValues.every(function (value) {
      var isLastEntry = value >= _this.count;
      var selected = _this.limit == value || isLastEntry && _this.limit > value;
      perpageValues.push(h("option", {
        attrs: {
          value: value
        },
        domProps: {
          "selected": selected
        }
      }, [value]));
      return !isLastEntry;
    });
    return perpageValues;
  };
  /***/

},
/* 26 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return Math.ceil(this.count / this.limit);
  };
  /***/

},
/* 27 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  var object_filled_keys_count = __webpack_require__(188);

  var is_valid_moment_object = __webpack_require__(16);

  var filterByCustomFilters = __webpack_require__(189);

  module.exports = function (data, e) {
    if (e) {
      var _query = this.query;
      this.setPage(1, true);
      var name = this.getName(e.target.name);
      var value = _typeof(e.target.value) === 'object' ? e.target.value : '' + e.target.value;

      if (name) {
        _query[name] = value;
      } else {
        _query = value;
      }

      this.vuex ? this.commit('SET_FILTER', _query) : this.query = _query;
      this.updateState('query', _query);

      if (name) {
        this.dispatch('filter', {
          name: name,
          value: value
        });
        this.dispatch('filter::' + name, value);
      } else {
        this.dispatch('filter', value);
      }
    }

    var query = this.query;
    var totalQueries = !query ? 0 : 1;
    if (!this.opts) return data;

    if (this.opts.filterByColumn) {
      totalQueries = object_filled_keys_count(query);
    }

    var value;
    var found;
    var currentQuery;
    var dateFormat;
    var filterByDate;
    var isListFilter;
    var data = filterByCustomFilters(data, this.opts.customFilters, this.customQueries);
    if (!totalQueries) return data;
    return data.filter(function (row, index) {
      found = 0;
      this.filterableColumns.forEach(function (column) {
        filterByDate = this.opts.dateColumns.indexOf(column) > -1 && this.opts.filterByColumn;
        isListFilter = this.isListFilter(column) && this.opts.filterByColumn;
        dateFormat = this.dateFormat(column);
        value = this._getValue(row, column);

        if (is_valid_moment_object(value) && !filterByDate) {
          value = value.format(dateFormat);
        }

        currentQuery = this.opts.filterByColumn ? query[column] : query;
        currentQuery = setCurrentQuery(currentQuery);
        if (currentQuery && foundMatch(currentQuery, value, isListFilter)) found++;
      }.bind(this));
      return found >= totalQueries;
    }.bind(this));
  };

  function setCurrentQuery(query) {
    if (!query) return '';
    if (typeof query == 'string') return query.toLowerCase(); // Date Range

    return query;
  }

  function foundMatch(query, value, isListFilter) {
    if (['string', 'number'].indexOf(typeof value === 'undefined' ? 'undefined' : _typeof(value)) > -1) {
      value = String(value).toLowerCase();
    } // List Filter


    if (isListFilter) {
      return value == query;
    } //Text Filter


    if (typeof value === 'string') {
      return value.indexOf(query) > -1;
    } // Date range


    if (is_valid_moment_object(value)) {
      var start = moment(query.start, 'YYYY-MM-DD HH:mm:ss');
      var end = moment(query.end, 'YYYY-MM-DD HH:mm:ss');
      return value >= start && value <= end;
    }

    if ((typeof value === 'undefined' ? 'undefined' : _typeof(value)) === 'object') {
      for (var key in value) {
        if (foundMatch(query, value[key])) return true;
      }

      return false;
    }

    return value >= start && value <= end;
  }
  /***/

},
/* 28 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _bus = __webpack_require__(1);

  var _bus2 = _interopRequireDefault(_bus);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  var ClientTable = __webpack_require__(29);

  var ServerTable = __webpack_require__(195);

  module.exports = {
    ClientTable: ClientTable,
    ServerTable: ServerTable,
    Event: _bus2.default
  };
  /***/
},
/* 29 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _vuePagination = __webpack_require__(10);

  var _vuex = __webpack_require__(12);

  var _vuex2 = _interopRequireDefault(_vuex);

  var _normal = __webpack_require__(13);

  var _normal2 = _interopRequireDefault(_normal);

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  var _table = __webpack_require__(14);

  var _table2 = _interopRequireDefault(_table);

  var _data2 = __webpack_require__(21);

  var _data3 = _interopRequireDefault(_data2);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  var _data = __webpack_require__(22);

  var _created = __webpack_require__(23);

  var templateCompiler = __webpack_require__(24);

  exports.install = function (Vue, globalOptions, useVuex) {
    var theme = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'bootstrap3';
    var template = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 'default';

    var client = _merge2.default.recursive(true, (0, _table2.default)(), {
      name: 'client-table',
      components: {
        Pagination: _vuePagination.Pagination
      },
      render: templateCompiler.call(this, template, theme),
      props: {
        columns: {
          type: Array,
          required: true
        },
        data: {
          type: Array,
          required: true
        },
        name: {
          type: String,
          required: false
        },
        options: {
          type: Object,
          required: false,
          default: function _default() {
            return {};
          }
        }
      },
      created: function created() {
        _created(this);

        if (!this.vuex) {
          this.initOrderBy();
          this.query = this.initQuery();
          this.customQueries = this.initCustomFilters();
        }
      },
      mounted: function mounted() {
        this._setColumnsDropdownCloseListener();

        if (this.opts.toMomentFormat) this.transformDateStringsToMoment();

        if (!this.vuex) {
          this.registerClientFilters();
          if (this.options.initialPage) this.setPage(this.options.initialPage);
        }

        if (this.opts.groupBy && !this.opts.orderBy) {
          this.orderBy.column = this.opts.groupBy;
        }

        this.loadState();

        if (this.hasDateFilters()) {
          this.initDateFilters();
        }
      },
      data: function data() {
        return _merge2.default.recursive(_data(), {
          source: 'client',
          globalOptions: globalOptions,
          currentlySorting: {},
          time: Date.now()
        }, (0, _data3.default)(useVuex, 'client', this.options.initialPage));
      },
      computed: {
        q: __webpack_require__(185),
        customQ: __webpack_require__(186),
        totalPages: __webpack_require__(26),
        filteredData: __webpack_require__(187),
        hasMultiSort: function hasMultiSort() {
          return this.opts.clientMultiSorting;
        }
      },
      methods: {
        transformDateStringsToMoment: __webpack_require__(191),
        registerClientFilters: __webpack_require__(192),
        search: __webpack_require__(27),
        defaultSort: __webpack_require__(193),
        getGroupSlot: __webpack_require__(194),
        toggleGroup: function toggleGroup(group, e) {
          e.stopPropagation();
          var i = this.collapsedGroups.indexOf(group);

          if (i >= 0) {
            this.collapsedGroups.splice(i, 1);
          } else {
            this.collapsedGroups.push(group);
          }
        },
        groupToggleIcon: function groupToggleIcon(group) {
          var cls = this.opts.sortIcon.base + ' ';
          cls += this.collapsedGroups.indexOf(group) > -1 ? this.opts.sortIcon.down : this.opts.sortIcon.up;
          return cls;
        },
        loadState: function loadState() {
          if (!this.opts.saveState) return;

          if (!this.storage.getItem(this.stateKey)) {
            this.initState();
            this.activeState = true;
            return;
          }

          var state = JSON.parse(this.storage.getItem(this.stateKey));
          if (this.opts.filterable) this.setFilter(state.query);
          this.setOrder(state.orderBy.column, state.orderBy.ascending);

          if (this.vuex) {
            this.commit('SET_LIMIT', state.perPage);
          } else {
            this.limit = state.perPage;
          }

          this.setPage(state.page);
          this.activeState = true;

          if (state.userControlsColumns) {
            this.userColumnsDisplay = state.userColumnsDisplay;
            this.userControlsColumns = state.userControlsColumns;
          } // TODO: Custom Queries

        }
      }
    });

    var state = useVuex ? (0, _vuex2.default)() : (0, _normal2.default)();
    client = _merge2.default.recursive(client, state);
    Vue.component('v-client-table', client);
    return client;
  };
  /***/

},
/* 30 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  var _config = __webpack_require__(31);

  var _config2 = _interopRequireDefault(_config);

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  var template = __webpack_require__(33);

  var bus = __webpack_require__(11);

  module.exports = {
    render: template.call(undefined),
    props: {
      for: {
        type: String,
        required: false
      },
      records: {
        type: Number,
        required: true
      },
      perPage: {
        type: Number,
        default: 25
      },
      vuex: {
        type: Boolean
      },
      options: {
        type: Object
      }
    },
    created: function created() {
      if (!this.vuex) return;

      if (!this.for) {
        throw new Error('vue-pagination-2: The "for" prop is required when using vuex');
      }

      var name = this.for;
      if (this.$store.state[name]) return;
      this.$store.registerModule(this.for, {
        state: {
          page: 1
        },
        mutations: _defineProperty({}, name + '/PAGINATE', function undefined(state, page) {
          state.page = page;
        })
      });
    },
    data: function data() {
      return {
        Page: 1,
        firstPage: 1
      };
    },
    computed: {
      opts: function opts() {
        return (0, _merge2.default)((0, _config2.default)(), this.options);
      },
      Theme: function Theme() {
        if (_typeof(this.opts.theme) === 'object') {
          return this.opts.theme;
        }

        var themes = {
          bootstrap3: __webpack_require__(34),
          bootstrap4: __webpack_require__(35),
          bulma: __webpack_require__(36)
        };

        if (_typeof(themes[this.opts.theme]) === undefined) {
          throw 'vue-pagination-2: the theme ' + this.opts.theme + ' does not exist';
        }

        return themes[this.opts.theme];
      },
      page: function page() {
        return this.vuex ? this.$store.state[this.for].page : this.Page;
      },
      pages: function pages() {
        if (!this.records) return [];
        return range(this.paginationStart, this.pagesInCurrentChunk);
      },
      totalPages: function totalPages() {
        return this.records ? Math.ceil(this.records / this.perPage) : 1;
      },
      totalChunks: function totalChunks() {
        return Math.ceil(this.totalPages / this.opts.chunk);
      },
      currentChunk: function currentChunk() {
        return Math.ceil(this.page / this.opts.chunk);
      },
      paginationStart: function paginationStart() {
        if (this.opts.chunksNavigation === 'scroll') {
          return this.firstPage;
        }

        return (this.currentChunk - 1) * this.opts.chunk + 1;
      },
      pagesInCurrentChunk: function pagesInCurrentChunk() {
        return this.paginationStart + this.opts.chunk <= this.totalPages ? this.opts.chunk : this.totalPages - this.paginationStart + 1;
      },
      count: function count() {
        if (/{page}/.test(this.opts.texts.count)) {
          if (this.totalPages <= 1) return '';
          return this.opts.texts.count.replace('{page}', this.page).replace('{pages}', this.totalPages);
        }

        var parts = this.opts.texts.count.split('|');
        var from = (this.page - 1) * this.perPage + 1;
        var to = this.page == this.totalPages ? this.records : from + this.perPage - 1;
        var i = Math.min(this.records == 1 ? 2 : this.totalPages == 1 ? 1 : 0, parts.length - 1);
        return parts[i].replace('{count}', this.formatNumber(this.records)).replace('{from}', this.formatNumber(from)).replace('{to}', this.formatNumber(to));
      }
    },
    methods: {
      setPage: function setPage(page) {
        if (this.allowedPage(page)) {
          this.paginate(page);
        }
      },
      paginate: function paginate(page) {
        if (this.vuex) {
          this.$store.commit(this.for + '/PAGINATE', page);
        } else {
          this.Page = page;
        }

        this.$emit('paginate', page);

        if (this.for) {
          bus.$emit('vue-pagination::' + this.for, page);
        }
      },
      next: function next() {
        var page = this.page + 1;

        if (this.opts.chunksNavigation === 'scroll' && this.allowedPage(page) && !this.inDisplay(page)) {
          this.firstPage++;
        }

        return this.setPage(page);
      },
      prev: function prev() {
        var page = this.page - 1;

        if (this.opts.chunksNavigation === 'scroll' && this.allowedPage(page) && !this.inDisplay(page)) {
          this.firstPage--;
        }

        return this.setPage(page);
      },
      inDisplay: function inDisplay(page) {
        var start = this.firstPage;
        var end = start + this.opts.chunk - 1;
        return page >= start && page <= end;
      },
      nextChunk: function nextChunk() {
        return this.setChunk(1);
      },
      prevChunk: function prevChunk() {
        return this.setChunk(-1);
      },
      setChunk: function setChunk(direction) {
        this.setPage((this.currentChunk - 1 + direction) * this.opts.chunk + 1);
      },
      allowedPage: function allowedPage(page) {
        return page >= 1 && page <= this.totalPages;
      },
      allowedChunk: function allowedChunk(direction) {
        return direction == 1 && this.currentChunk < this.totalChunks || direction == -1 && this.currentChunk > 1;
      },
      allowedPageClass: function allowedPageClass(direction) {
        return this.allowedPage(direction) ? '' : this.Theme.disabled;
      },
      allowedChunkClass: function allowedChunkClass(direction) {
        return this.allowedChunk(direction) ? '' : this.Theme.disabled;
      },
      activeClass: function activeClass(page) {
        return this.page == page ? this.Theme.active : '';
      },
      formatNumber: function formatNumber(num) {
        if (!this.opts.format) return num;
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }
    },
    beforeDestroy: function beforeDestroy() {
      bus.$off();
      bus.$destroy();
    }
  };

  function range(start, count) {
    return Array.apply(0, Array(count)).map(function (element, index) {
      return index + start;
    });
  }
  /***/

},
/* 31 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function () {
    return {
      format: true,
      chunk: 10,
      chunksNavigation: 'fixed',
      edgeNavigation: false,
      theme: 'bootstrap3',
      texts: {
        count: 'Showing {from} to {to} of {count} records|{count} records|One record',
        first: 'First',
        last: 'Last'
      }
    };
  };
  /***/

},
/* 32 */

/***/
function (module, exports) {
  module.exports = function (module) {
    if (!module.webpackPolyfill) {
      module.deprecate = function () {};

      module.paths = []; // module.parent = undefined by default

      if (!module.children) module.children = [];
      Object.defineProperty(module, "loaded", {
        enumerable: true,
        get: function get() {
          return module.l;
        }
      });
      Object.defineProperty(module, "id", {
        enumerable: true,
        get: function get() {
          return module.i;
        }
      });
      module.webpackPolyfill = 1;
    }

    return module;
  };
  /***/

},
/* 33 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return function (h) {
      var theme = this.Theme;
      var prevChunk = '';
      var nextChunk = '';
      var firstPage = '';
      var lastPage = '';
      var items = this.pages.map(function (page) {
        return h('li', {
          'class': 'VuePagination__pagination-item ' + theme.item + ' ' + this.activeClass(page)
        }, [h('a', {
          'class': theme.link + ' ' + this.activeClass(page),
          attrs: {
            href: 'javascript:void(0)',
            role: 'button'
          },
          on: {
            'click': this.setPage.bind(this, page)
          }
        }, [page])]);
      }.bind(this));

      if (this.opts.edgeNavigation && this.totalChunks > 1) {
        firstPage = h('li', {
          'class': 'VuePagination__pagination-item ' + theme.item + ' ' + (this.page === 1 ? theme.disabled : '') + ' VuePagination__pagination-item-prev-chunk'
        }, [h('a', {
          'class': theme.link,
          attrs: {
            href: 'javascript:void(0);',
            disabled: this.page === 1
          },
          on: {
            'click': this.setPage.bind(this, 1)
          }
        }, [this.opts.texts.first])]);
        lastPage = h('li', {
          'class': 'VuePagination__pagination-item ' + theme.item + ' ' + (this.page === this.totalPages ? theme.disabled : '') + ' VuePagination__pagination-item-prev-chunk'
        }, [h('a', {
          'class': theme.link,
          attrs: {
            href: 'javascript:void(0);',
            disabled: this.page === this.totalPages
          },
          on: {
            'click': this.setPage.bind(this, this.totalPages)
          }
        }, [this.opts.texts.last])]);
      }

      if (this.opts.chunksNavigation === 'fixed') {
        prevChunk = h('li', {
          'class': 'VuePagination__pagination-item ' + theme.item + ' ' + theme.prev + ' VuePagination__pagination-item-prev-chunk ' + this.allowedChunkClass(-1)
        }, [h('a', {
          'class': theme.link,
          attrs: {
            href: 'javascript:void(0);',
            disabled: !!this.allowedChunkClass(-1)
          },
          on: {
            'click': this.setChunk.bind(this, -1)
          }
        }, ['<<'])]);
        nextChunk = h('li', {
          'class': 'VuePagination__pagination-item ' + theme.item + ' ' + theme.next + ' VuePagination__pagination-item-next-chunk ' + this.allowedChunkClass(1)
        }, [h('a', {
          'class': theme.link,
          attrs: {
            href: 'javascript:void(0);',
            disabled: !!this.allowedChunkClass(1)
          },
          on: {
            'click': this.setChunk.bind(this, 1)
          }
        }, ['>>'])]);
      }

      return h('div', {
        'class': 'VuePagination ' + theme.wrapper
      }, [h('nav', {
        'class': '' + theme.nav
      }, [h('ul', {
        directives: [{
          name: 'show',
          value: this.totalPages > 1
        }],
        'class': theme.list + ' VuePagination__pagination'
      }, [firstPage, prevChunk, h('li', {
        'class': 'VuePagination__pagination-item ' + theme.item + ' ' + theme.prev + ' VuePagination__pagination-item-prev-page ' + this.allowedPageClass(this.page - 1)
      }, [h('a', {
        'class': theme.link,
        attrs: {
          href: 'javascript:void(0);',
          disabled: !!this.allowedPageClass(this.page - 1)
        },
        on: {
          'click': this.prev.bind(this)
        }
      }, ['<'])]), items, h('li', {
        'class': 'VuePagination__pagination-item ' + theme.item + ' ' + theme.next + ' VuePagination__pagination-item-next-page ' + this.allowedPageClass(this.page + 1)
      }, [h('a', {
        'class': theme.link,
        attrs: {
          href: 'javascript:void(0);',
          disabled: !!this.allowedPageClass(this.page + 1)
        },
        on: {
          'click': this.next.bind(this)
        }
      }, ['>'])]), nextChunk, lastPage]), h('p', {
        directives: [{
          name: 'show',
          value: parseInt(this.records)
        }],
        'class': 'VuePagination__count ' + theme.count
      }, [this.count])])]);
    };
  };
  /***/

},
/* 34 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    nav: '',
    count: '',
    wrapper: '',
    list: 'pagination',
    item: 'page-item',
    link: 'page-link',
    next: '',
    prev: '',
    active: 'active',
    disabled: 'disabled'
  };
  /***/
},
/* 35 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    nav: '',
    count: '',
    wrapper: '',
    list: 'pagination',
    item: 'page-item',
    link: 'page-link',
    next: '',
    prev: '',
    active: 'active',
    disabled: 'disabled'
  };
  /***/
},
/* 36 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    nav: '',
    count: '',
    wrapper: 'pagination',
    list: 'pagination-list',
    item: '',
    link: 'pagination-link',
    next: '',
    prev: '',
    active: 'is-current',
    disabled: '' // uses the disabled HTML attirbute

  };
  /***/
},
/* 37 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    initQuery: __webpack_require__(38),
    initCustomFilters: __webpack_require__(39),
    initOptions: __webpack_require__(40),
    sortableClass: __webpack_require__(41),
    sortableChevronClass: __webpack_require__(42),
    display: __webpack_require__(43),
    orderByColumn: __webpack_require__(44),
    getHeading: __webpack_require__(45),
    getHeadingTooltip: __webpack_require__(47),
    sortable: __webpack_require__(48),
    serverSearch: __webpack_require__(15),
    initOrderBy: __webpack_require__(49),
    initDateFilters: __webpack_require__(50),
    setFilter: __webpack_require__(51),
    setPage: __webpack_require__(52),
    setOrder: __webpack_require__(53),
    initPagination: __webpack_require__(54),
    filterable: __webpack_require__(55),
    isTextFilter: __webpack_require__(56),
    isDateFilter: __webpack_require__(57),
    isListFilter: __webpack_require__(58),
    highlightMatch: __webpack_require__(59),
    formatDate: __webpack_require__(60),
    hasDateFilters: __webpack_require__(61),
    applyFilters: __webpack_require__(112),
    optionText: __webpack_require__(113),
    render: __webpack_require__(114),
    rowWasClicked: __webpack_require__(115),
    setLimit: __webpack_require__(116),
    getOpenChildRows: __webpack_require__(117),
    dispatch: __webpack_require__(118),
    toggleChildRow: __webpack_require__(119),
    childRowTogglerClass: __webpack_require__(120),
    sendRequest: __webpack_require__(121),
    getResponseData: __webpack_require__(122),
    getSortFn: __webpack_require__(123),
    initState: __webpack_require__(124),
    updateState: __webpack_require__(125),
    columnClass: __webpack_require__(126),
    getName: __webpack_require__(127),
    toggleColumn: __webpack_require__(128),
    setUserMultiSort: __webpack_require__(129),
    _setFiltersDOM: __webpack_require__(130),
    _currentlySorted: __webpack_require__(131),
    _getChildRowTemplate: __webpack_require__(132),
    _toggleColumnsDropdown: __webpack_require__(133),
    _onlyColumn: __webpack_require__(134),
    _onPagination: __webpack_require__(135),
    _toggleGroupDirection: __webpack_require__(136),
    _getInitialDateRange: __webpack_require__(137),
    _setDatepickerText: __webpack_require__(138),
    _initialOrderAscending: __webpack_require__(139),
    dateFormat: __webpack_require__(140),
    _setColumnsDropdownCloseListener: __webpack_require__(141),
    _getValue: __webpack_require__(142),
    _getColumnName: __webpack_require__(143)
  };
  /***/
},
/* 38 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function () {
    var init = this.opts.initFilters;
    if (!this.opts.filterByColumn) return init.hasOwnProperty('GENERIC') ? init.GENERIC : '';
    var query = {};
    var filterable = this.opts.filterable && _typeof(this.opts.filterable) == 'object' ? this.opts.filterable : this.columns;
    filterable.forEach(function (column) {
      query[column] = getInitialValue(init, column);
    }.bind(this));
    return query;
  };

  function getInitialValue(init, column) {
    if (!init.hasOwnProperty(column)) return '';
    if (typeof init[column].start == 'undefined') return init[column];
    return {
      start: init[column].start.format('YYYY-MM-DD HH:mm:ss'),
      end: init[column].end.format('YYYY-MM-DD HH:mm:ss')
    };
  }
  /***/

},
/* 39 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var customQueries = {};
    var init = this.opts.initFilters;
    var key = void 0;
    this.opts.customFilters.forEach(function (filter) {
      key = this.source == 'client' ? filter.name : filter;
      customQueries[key] = init.hasOwnProperty(key) ? init[key] : '';
    }.bind(this));
    return customQueries;
  };
  /***/

},
/* 40 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var merge = __webpack_require__(0);

  module.exports = function (defaults, globalOptions, localOptions) {
    if (globalOptions) defaults = merge.recursive(defaults, globalOptions);
    localOptions = merge.recursive(defaults, localOptions);
    return localOptions;
  };
  /***/

},
/* 41 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var c = this.sortable(column) ? 'VueTables__sortable ' : '';
    c += this.columnClass(column);
    return c;
  };
  /***/

},
/* 42 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var cls = this.opts.sortIcon.base + ' ';
    if (!this.sortable(column)) return;

    if (this.opts.sortIcon.is && !this._currentlySorted(column)) {
      cls += this.opts.sortIcon.is + ' ';
    }

    if (this.hasMultiSort && this.orderBy.column && this.userMultiSorting[this.orderBy.column]) {
      var col = this.userMultiSorting[this.orderBy.column].filter(function (c) {
        return c.column === column;
      })[0];
      if (col) cls += col.ascending ? this.opts.sortIcon.up : this.opts.sortIcon.down;
    }

    if (column == this.orderBy.column) {
      cls += this.orderBy.ascending == 1 ? this.opts.sortIcon.up : this.opts.sortIcon.down;
    }

    return cls;
  };
  /***/

},
/* 43 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (text, replacements) {
    if (!this.opts.texts) return '';
    var text = this.opts.texts[text];
    if (replacements) for (var key in replacements) {
      // console.log(key)
      text = text.replace('{' + key + '}', replacements[key]);
    }
    return text;
  };
  /***/

},
/* 44 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (colName, ev) {
    if (!this.sortable(colName)) return;

    if (ev.shiftKey && this.orderBy.column && this.hasMultiSort) {
      this.setUserMultiSort(colName);
    } else {
      this.userMultiSorting = {};
      this.orderBy.ascending = colName == this.orderBy.column ? !this.orderBy.ascending : this._initialOrderAscending(colName);
      this.orderBy.column = colName;
      this.updateState('orderBy', this.orderBy);
      this.dispatch('sorted', JSON.parse(JSON.stringify(this.orderBy)));
    }

    if (this.source == 'server') {
      this.getData();
    }
  };
  /***/

},
/* 45 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _ucfirst = __webpack_require__(46);

  var _ucfirst2 = _interopRequireDefault(_ucfirst);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function (value, h) {
    if (typeof value !== 'string') return '';

    if (typeof this.$slots['h__' + value] !== 'undefined') {
      return this.$slots['h__' + value];
    }

    var derivedHeading = (0, _ucfirst2.default)(value.split("_").join(" "));
    if (!this.opts.headings.hasOwnProperty(value)) return derivedHeading;

    if (typeof this.opts.headings[value] === 'function') {
      if (h) return this.opts.headings[value].call(this.$parent, h);
      return derivedHeading;
    }

    return this.opts.headings[value];
  };
  /***/

},
/* 46 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  };
  /***/

},
/* 47 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (value, h) {
    if (typeof value !== 'string') return '';
    var derivedHeadingTooltip = '';
    if (!this.opts.headingsTooltips.hasOwnProperty(value)) return derivedHeadingTooltip;

    if (typeof this.opts.headingsTooltips[value] === 'function') {
      if (h) return this.opts.headingsTooltips[value].call(this.$parent, h);
      return derivedHeadingTooltip;
    }

    return this.opts.headingsTooltips[value];
  };
  /***/

},
/* 48 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var sortAll = typeof this.opts.sortable == 'boolean' && this.opts.sortable;
    if (sortAll) return true;
    return this.opts.sortable.indexOf(column) > -1;
  };
  /***/

},
/* 49 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    if (!this.opts.orderBy) return;
    this.orderBy.column = this.opts.orderBy.column;
    this.orderBy.ascending = this.opts.orderBy.hasOwnProperty('ascending') ? this.opts.orderBy.ascending : true;
  };
  /***/

},
/* 50 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var merge = __webpack_require__(0);

  module.exports = function () {
    if (typeof $ === 'undefined') {
      console.error('Date filters require jquery and daterangepicker');
      return;
    }

    var el;
    var that = this;
    var query = this.vuex ? JSON.parse(JSON.stringify(this.query)) : this.query;
    var columnOptions;
    var dpOptions;

    var search = function search(query, e) {
      return that.source == 'client' ? that.search(that.data, e) : that.serverSearch(query, e);
    };

    var datepickerOptions = merge.recursive(this.opts.datepickerOptions, {
      autoUpdateInput: false,
      singleDatePicker: false
    });
    that.opts.dateColumns.forEach(function (column) {
      var range = that._getInitialDateRange(column);

      if (range) {
        that._setDatepickerText(column, range.start, range.end);

        range = {
          startDate: range.start,
          endDate: range.end
        };
      } else {
        range = {};
      }

      el = $(that.$el).find("#VueTables__" + column + "-filter");
      columnOptions = typeof that.opts.datepickerPerColumnOptions[column] !== 'undefined' ? that.opts.datepickerPerColumnOptions[column] : {};
      columnOptions = merge.recursive(columnOptions, {
        locale: {
          format: that.dateFormat(column)
        }
      });
      dpOptions = merge(true, datepickerOptions);

      if (columnOptions.ranges === false) {
        dpOptions.ranges = {};
      }

      el.daterangepicker(merge.recursive(dpOptions, columnOptions, range));
      el.on('apply.daterangepicker', function (ev, picker) {
        query[column] = {
          start: picker.startDate.format('YYYY-MM-DD HH:mm:ss'),
          end: picker.endDate.format('YYYY-MM-DD HH:mm:ss')
        };
        if (!that.vuex) that.query = query;

        that._setDatepickerText(column, picker.startDate, picker.endDate);

        that.updateState('query', query);
        search(query, {
          target: {
            name: that._getColumnName(column),
            value: query[column]
          }
        });
      });
      el.on('cancel.daterangepicker', function (ev, picker) {
        query[column] = '';
        if (!that.vuex) that.query = query;
        picker.setStartDate(moment());
        picker.setEndDate(moment());
        that.updateState('query', query);
        $(this).html("<span class='VueTables__filter-placeholder'>" + that.display('filterBy', {
          column: that.getHeading(column)
        }) + "</span>");
        search(query, {
          target: {
            name: that._getColumnName(column),
            value: query[column]
          }
        });
      });
    });
  };
  /***/

},
/* 51 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var merge = __webpack_require__(0);

  module.exports = function (filter) {
    if (!this.opts.filterable) {
      console.warn("vue-tables-2: Unable to set filter. Filtering is disabled (filterable: false)");
      return;
    }

    ;

    if (this.opts.filterByColumn && typeof filter === 'string') {
      console.warn("vue-tables-2: Unable to set filter. Filter value must be an object (`filterByColumn` is set to `true`)");
      return;
    }

    ;

    if (!this.opts.filterByColumn && typeof filter !== 'string') {
      console.warn("vue-tables-2: Unable to set filter. Filter value must be a string (`filterByColumn` is set to `false`)");
      return;
    }

    ;
    var mergedFilter = this.opts.filterByColumn ? merge(this.query, filter) : filter;

    if (this.vuex) {
      this.commit('SET_FILTER', mergedFilter);
    } else {
      this.query = mergedFilter;
      this.setPage(1, true);
    }

    this.updateState('query', mergedFilter);

    this._setFiltersDOM(filter);

    if (this.source == 'server') {
      this.getData();
    }
  };
  /***/

},
/* 52 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (page, preventRequest) {
    page = page ? page : this.$refs.page.value;
    if (!this.opts.pagination.dropdown) this.$refs.pagination.Page = page;
    this.page = page;
    this.updateState('page', page);
    if (this.source == 'server' && !preventRequest) this.getData();
  };
  /***/

},
/* 53 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column, ascending) {
    this.orderBy.column = column;
    this.orderBy.ascending = ascending;
    this.updateState('orderBy', {
      column: column,
      ascending: ascending
    });

    if (this.source == 'server') {
      this.getData();
    }
  };
  /***/

},
/* 54 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    this.page = 1;

    if (!this.opts.pagination.dropdown) {
      this.$refs.pagination.setPage(1);
    }
  };
  /***/

},
/* 55 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    if (!this.opts.filterable) return false;
    return typeof this.opts.filterable == 'boolean' && this.opts.filterable || this.opts.filterable.indexOf(column) > -1;
  };
  /***/

},
/* 56 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    return this.query.hasOwnProperty(column) && this.opts.dateColumns.indexOf(column) == -1 && !this.opts.listColumns.hasOwnProperty(column);
  };
  /***/

},
/* 57 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    return this.query.hasOwnProperty(column) && this.opts.dateColumns.indexOf(column) > -1;
  };
  /***/

},
/* 58 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    return this.query.hasOwnProperty(column) && this.opts.listColumns.hasOwnProperty(column);
  };
  /***/

},
/* 59 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (value, column, h) {
    var query = this.opts.filterByColumn ? this.query[column] : this.query;
    if (!query) return value;
    query = new RegExp("(" + escapeRegex(query) + ")", "i");
    return h("span", {
      class: 'VueTables__highlight'
    }, matches(value, query, h));
  };

  function matches(value, query, h) {
    var pieces = String(value).split(query);
    return pieces.map(function (piece) {
      if (query.test(piece)) {
        return h("b", {}, piece);
      }

      return piece;
    });
  }

  function escapeRegex(s) {
    return typeof s === 'string' ? s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') : s;
  }

  ;
  /***/
},
/* 60 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var validMoment = __webpack_require__(16);

  module.exports = function (value, dateFormat) {
    if (!validMoment(value)) return value;
    return value.format(dateFormat);
  };
  /***/

},
/* 61 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  var intersection = __webpack_require__(62);

  module.exports = function () {
    var opts = this.opts;
    return opts.dateColumns.length && opts.filterByColumn && (typeof opts.filterable == 'boolean' && opts.filterable || _typeof(opts.filterable) == 'object' && intersection(opts.filterable, opts.dateColumns).length);
  };
  /***/

},
/* 62 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * array-intersection <https://github.com/jonschlinkert/array-intersection>
   *
   * Copyright (c) 2014 Jon Schlinkert, contributors.
   * Licensed under the MIT License
   */

  var filter = __webpack_require__(63);

  var every = __webpack_require__(108);

  var unique = __webpack_require__(18);

  var slice = __webpack_require__(110);

  var indexOf = __webpack_require__(111);

  module.exports = function intersection(arr) {
    if (arr == null) {
      return [];
    }

    if (arguments.length === 1) {
      return unique(arr);
    }

    var arrays = slice(arguments, 1);
    return filter(unique(arr), function (ele) {
      return every(arrays, function (cur) {
        return indexOf(cur, ele) !== -1;
      });
    });
  };
  /***/

},
/* 63 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * filter-array <https://github.com/jonschlinkert/filter-array>
   *
   * Copyright (c) 2014-2015 Jon Schlinkert, contributors.
   * Licensed under the MIT License
   */

  var typeOf = __webpack_require__(64);

  var filter = __webpack_require__(69);

  var mm = __webpack_require__(72);
  /**
   * Filter array against given glob
   * patterns, regex or given function.
   *
   * ```js
   * var filter = require('filter-array');
   *
   * filter(['a', 'b', 'c', 'b', 'c', 'e'], function(ele) {
   *   return ele === 'a' || ele === 'b';
   * });
   *
   * //=> ['a', 'b', 'b']
   * ```
   *
   * @name   filterArray
   * @param  {Array} `arr` array to filter
   * @param  {Array|String|Function|RegExp} `filters`
   * @param  {Object} `opts` options to pass to [micromatch]
   * @return {Array}
   * @api public
   */


  module.exports = function filterArray(arr, filters, opts) {
    if (arr.length === 0) {
      return [];
    }

    if (typeOf(filters) === 'function' || typeOf(filters) === 'regexp') {
      var isMatch = mm.matcher(filters, opts);
      return filter(arr, function _filter(val) {
        return isMatch(val);
      });
    }

    if (typeOf(filters) === 'string' || typeOf(filters) === 'array') {
      return filter(arr, mm.filter(filters, opts));
    }

    return [];
  };
  /***/

},
/* 64 */

/***/
function (module, exports, __webpack_require__) {
  /* WEBPACK VAR INJECTION */
  (function (Buffer) {
    var toString = Object.prototype.toString;
    /**
     * Get the native `typeof` a value.
     *
     * @param  {*} `val`
     * @return {*} Native javascript type
     */

    module.exports = function kindOf(val) {
      if (val === undefined) {
        return 'undefined';
      }

      if (val === null) {
        return 'null';
      }

      if (val === true || val === false || val instanceof Boolean) {
        return 'boolean';
      }

      if (_typeof2(val) !== 'object') {
        return _typeof2(val);
      }

      if (Array.isArray(val)) {
        return 'array';
      }

      var type = toString.call(val);

      if (val instanceof RegExp || type === '[object RegExp]') {
        return 'regexp';
      }

      if (val instanceof Date || type === '[object Date]') {
        return 'date';
      }

      if (type === '[object Function]') {
        return 'function';
      }

      if (type === '[object Arguments]') {
        return 'arguments';
      }

      if (typeof Buffer !== 'undefined' && Buffer.isBuffer(val)) {
        return 'buffer';
      }

      return type.slice(8, -1).toLowerCase();
    };
    /* WEBPACK VAR INJECTION */

  }).call(exports, __webpack_require__(17).Buffer);
  /***/
},
/* 65 */

/***/
function (module, exports) {
  var g; // This works in non-strict mode

  g = function () {
    return this;
  }();

  try {
    // This works if eval is allowed (see CSP)
    g = g || Function("return this")() || (1, eval)("this");
  } catch (e) {
    // This works if the window reference is available
    if ((typeof window === "undefined" ? "undefined" : _typeof2(window)) === "object") g = window;
  } // g can still be undefined, but nothing to do about it...
  // We return undefined, instead of nothing here, so it's
  // easier to handle this case. if(!global) { ...}


  module.exports = g;
  /***/
},
/* 66 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  exports.byteLength = byteLength;
  exports.toByteArray = toByteArray;
  exports.fromByteArray = fromByteArray;
  var lookup = [];
  var revLookup = [];
  var Arr = typeof Uint8Array !== 'undefined' ? Uint8Array : Array;
  var code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

  for (var i = 0, len = code.length; i < len; ++i) {
    lookup[i] = code[i];
    revLookup[code.charCodeAt(i)] = i;
  } // Support decoding URL-safe base64 strings, as Node.js does.
  // See: https://en.wikipedia.org/wiki/Base64#URL_applications


  revLookup['-'.charCodeAt(0)] = 62;
  revLookup['_'.charCodeAt(0)] = 63;

  function placeHoldersCount(b64) {
    var len = b64.length;

    if (len % 4 > 0) {
      throw new Error('Invalid string. Length must be a multiple of 4');
    } // the number of equal signs (place holders)
    // if there are two placeholders, than the two characters before it
    // represent one byte
    // if there is only one, then the three characters before it represent 2 bytes
    // this is just a cheap hack to not do indexOf twice


    return b64[len - 2] === '=' ? 2 : b64[len - 1] === '=' ? 1 : 0;
  }

  function byteLength(b64) {
    // base64 is 4/3 + up to two characters of the original data
    return b64.length * 3 / 4 - placeHoldersCount(b64);
  }

  function toByteArray(b64) {
    var i, l, tmp, placeHolders, arr;
    var len = b64.length;
    placeHolders = placeHoldersCount(b64);
    arr = new Arr(len * 3 / 4 - placeHolders); // if there are placeholders, only get up to the last complete 4 chars

    l = placeHolders > 0 ? len - 4 : len;
    var L = 0;

    for (i = 0; i < l; i += 4) {
      tmp = revLookup[b64.charCodeAt(i)] << 18 | revLookup[b64.charCodeAt(i + 1)] << 12 | revLookup[b64.charCodeAt(i + 2)] << 6 | revLookup[b64.charCodeAt(i + 3)];
      arr[L++] = tmp >> 16 & 0xFF;
      arr[L++] = tmp >> 8 & 0xFF;
      arr[L++] = tmp & 0xFF;
    }

    if (placeHolders === 2) {
      tmp = revLookup[b64.charCodeAt(i)] << 2 | revLookup[b64.charCodeAt(i + 1)] >> 4;
      arr[L++] = tmp & 0xFF;
    } else if (placeHolders === 1) {
      tmp = revLookup[b64.charCodeAt(i)] << 10 | revLookup[b64.charCodeAt(i + 1)] << 4 | revLookup[b64.charCodeAt(i + 2)] >> 2;
      arr[L++] = tmp >> 8 & 0xFF;
      arr[L++] = tmp & 0xFF;
    }

    return arr;
  }

  function tripletToBase64(num) {
    return lookup[num >> 18 & 0x3F] + lookup[num >> 12 & 0x3F] + lookup[num >> 6 & 0x3F] + lookup[num & 0x3F];
  }

  function encodeChunk(uint8, start, end) {
    var tmp;
    var output = [];

    for (var i = start; i < end; i += 3) {
      tmp = (uint8[i] << 16 & 0xFF0000) + (uint8[i + 1] << 8 & 0xFF00) + (uint8[i + 2] & 0xFF);
      output.push(tripletToBase64(tmp));
    }

    return output.join('');
  }

  function fromByteArray(uint8) {
    var tmp;
    var len = uint8.length;
    var extraBytes = len % 3; // if we have 1 byte left, pad 2 bytes

    var output = '';
    var parts = [];
    var maxChunkLength = 16383; // must be multiple of 3
    // go through the array every three bytes, we'll deal with trailing stuff later

    for (var i = 0, len2 = len - extraBytes; i < len2; i += maxChunkLength) {
      parts.push(encodeChunk(uint8, i, i + maxChunkLength > len2 ? len2 : i + maxChunkLength));
    } // pad the end with zeros, but make sure to not forget the extra bytes


    if (extraBytes === 1) {
      tmp = uint8[len - 1];
      output += lookup[tmp >> 2];
      output += lookup[tmp << 4 & 0x3F];
      output += '==';
    } else if (extraBytes === 2) {
      tmp = (uint8[len - 2] << 8) + uint8[len - 1];
      output += lookup[tmp >> 10];
      output += lookup[tmp >> 4 & 0x3F];
      output += lookup[tmp << 2 & 0x3F];
      output += '=';
    }

    parts.push(output);
    return parts.join('');
  }
  /***/

},
/* 67 */

/***/
function (module, exports) {
  exports.read = function (buffer, offset, isLE, mLen, nBytes) {
    var e, m;
    var eLen = nBytes * 8 - mLen - 1;
    var eMax = (1 << eLen) - 1;
    var eBias = eMax >> 1;
    var nBits = -7;
    var i = isLE ? nBytes - 1 : 0;
    var d = isLE ? -1 : 1;
    var s = buffer[offset + i];
    i += d;
    e = s & (1 << -nBits) - 1;
    s >>= -nBits;
    nBits += eLen;

    for (; nBits > 0; e = e * 256 + buffer[offset + i], i += d, nBits -= 8) {}

    m = e & (1 << -nBits) - 1;
    e >>= -nBits;
    nBits += mLen;

    for (; nBits > 0; m = m * 256 + buffer[offset + i], i += d, nBits -= 8) {}

    if (e === 0) {
      e = 1 - eBias;
    } else if (e === eMax) {
      return m ? NaN : (s ? -1 : 1) * Infinity;
    } else {
      m = m + Math.pow(2, mLen);
      e = e - eBias;
    }

    return (s ? -1 : 1) * m * Math.pow(2, e - mLen);
  };

  exports.write = function (buffer, value, offset, isLE, mLen, nBytes) {
    var e, m, c;
    var eLen = nBytes * 8 - mLen - 1;
    var eMax = (1 << eLen) - 1;
    var eBias = eMax >> 1;
    var rt = mLen === 23 ? Math.pow(2, -24) - Math.pow(2, -77) : 0;
    var i = isLE ? 0 : nBytes - 1;
    var d = isLE ? 1 : -1;
    var s = value < 0 || value === 0 && 1 / value < 0 ? 1 : 0;
    value = Math.abs(value);

    if (isNaN(value) || value === Infinity) {
      m = isNaN(value) ? 1 : 0;
      e = eMax;
    } else {
      e = Math.floor(Math.log(value) / Math.LN2);

      if (value * (c = Math.pow(2, -e)) < 1) {
        e--;
        c *= 2;
      }

      if (e + eBias >= 1) {
        value += rt / c;
      } else {
        value += rt * Math.pow(2, 1 - eBias);
      }

      if (value * c >= 2) {
        e++;
        c /= 2;
      }

      if (e + eBias >= eMax) {
        m = 0;
        e = eMax;
      } else if (e + eBias >= 1) {
        m = (value * c - 1) * Math.pow(2, mLen);
        e = e + eBias;
      } else {
        m = value * Math.pow(2, eBias - 1) * Math.pow(2, mLen);
        e = 0;
      }
    }

    for (; mLen >= 8; buffer[offset + i] = m & 0xff, i += d, m /= 256, mLen -= 8) {}

    e = e << mLen | m;
    eLen += mLen;

    for (; eLen > 0; buffer[offset + i] = e & 0xff, i += d, e /= 256, eLen -= 8) {}

    buffer[offset + i - d] |= s * 128;
  };
  /***/

},
/* 68 */

/***/
function (module, exports) {
  var toString = {}.toString;

  module.exports = Array.isArray || function (arr) {
    return toString.call(arr) == '[object Array]';
  };
  /***/

},
/* 69 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * arr-filter <https://github.com/jonschlinkert/arr-filter>
   *
   * Copyright (c) 2014-2015, 2017, Jon Schlinkert.
   * Released under the MIT License.
   */

  var makeIterator = __webpack_require__(70);

  module.exports = function filter(arr, fn, thisArg) {
    if (arr == null) {
      return [];
    }

    if (typeof fn !== 'function') {
      throw new TypeError('expected callback to be a function');
    }

    var iterator = makeIterator(fn, thisArg);
    var len = arr.length;
    var res = arr.slice();
    var i = -1;

    while (len--) {
      if (!iterator(arr[len], i++)) {
        res.splice(len, 1);
      }
    }

    return res;
  };
  /***/

},
/* 70 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * make-iterator <https://github.com/jonschlinkert/make-iterator>
   *
   * Copyright (c) 2014-2018, Jon Schlinkert.
   * Released under the MIT License.
   */

  var typeOf = __webpack_require__(71);

  module.exports = function makeIterator(target, thisArg) {
    switch (typeOf(target)) {
      case 'undefined':
      case 'null':
        return noop;

      case 'function':
        // function is the first to improve perf (most common case)
        // also avoid using `Function#call` if not needed, which boosts
        // perf a lot in some cases
        return typeof thisArg !== 'undefined' ? function (val, i, arr) {
          return target.call(thisArg, val, i, arr);
        } : target;

      case 'object':
        return function (val) {
          return deepMatches(val, target);
        };

      case 'regexp':
        return function (str) {
          return target.test(str);
        };

      case 'string':
      case 'number':
      default:
        {
          return prop(target);
        }
    }
  };

  function containsMatch(array, value) {
    var len = array.length;
    var i = -1;

    while (++i < len) {
      if (deepMatches(array[i], value)) {
        return true;
      }
    }

    return false;
  }

  function matchArray(arr, value) {
    var len = value.length;
    var i = -1;

    while (++i < len) {
      if (!containsMatch(arr, value[i])) {
        return false;
      }
    }

    return true;
  }

  function matchObject(obj, value) {
    for (var key in value) {
      if (value.hasOwnProperty(key)) {
        if (deepMatches(obj[key], value[key]) === false) {
          return false;
        }
      }
    }

    return true;
  }
  /**
   * Recursively compare objects
   */


  function deepMatches(val, value) {
    if (typeOf(val) === 'object') {
      if (Array.isArray(val) && Array.isArray(value)) {
        return matchArray(val, value);
      } else {
        return matchObject(val, value);
      }
    } else {
      return val === value;
    }
  }

  function prop(name) {
    return function (obj) {
      return obj[name];
    };
  }

  function noop(val) {
    return val;
  }
  /***/

},
/* 71 */

/***/
function (module, exports) {
  var toString = Object.prototype.toString;

  module.exports = function kindOf(val) {
    if (val === void 0) return 'undefined';
    if (val === null) return 'null';

    var type = _typeof2(val);

    if (type === 'boolean') return 'boolean';
    if (type === 'string') return 'string';
    if (type === 'number') return 'number';
    if (type === 'symbol') return 'symbol';

    if (type === 'function') {
      return isGeneratorFn(val) ? 'generatorfunction' : 'function';
    }

    if (isArray(val)) return 'array';
    if (isBuffer(val)) return 'buffer';
    if (isArguments(val)) return 'arguments';
    if (isDate(val)) return 'date';
    if (isError(val)) return 'error';
    if (isRegexp(val)) return 'regexp';

    switch (ctorName(val)) {
      case 'Symbol':
        return 'symbol';

      case 'Promise':
        return 'promise';
      // Set, Map, WeakSet, WeakMap

      case 'WeakMap':
        return 'weakmap';

      case 'WeakSet':
        return 'weakset';

      case 'Map':
        return 'map';

      case 'Set':
        return 'set';
      // 8-bit typed arrays

      case 'Int8Array':
        return 'int8array';

      case 'Uint8Array':
        return 'uint8array';

      case 'Uint8ClampedArray':
        return 'uint8clampedarray';
      // 16-bit typed arrays

      case 'Int16Array':
        return 'int16array';

      case 'Uint16Array':
        return 'uint16array';
      // 32-bit typed arrays

      case 'Int32Array':
        return 'int32array';

      case 'Uint32Array':
        return 'uint32array';

      case 'Float32Array':
        return 'float32array';

      case 'Float64Array':
        return 'float64array';
    }

    if (isGeneratorObj(val)) {
      return 'generator';
    } // Non-plain objects


    type = toString.call(val);

    switch (type) {
      case '[object Object]':
        return 'object';
      // iterators

      case '[object Map Iterator]':
        return 'mapiterator';

      case '[object Set Iterator]':
        return 'setiterator';

      case '[object String Iterator]':
        return 'stringiterator';

      case '[object Array Iterator]':
        return 'arrayiterator';
    } // other


    return type.slice(8, -1).toLowerCase().replace(/\s/g, '');
  };

  function ctorName(val) {
    return val.constructor ? val.constructor.name : null;
  }

  function isArray(val) {
    if (Array.isArray) return Array.isArray(val);
    return val instanceof Array;
  }

  function isError(val) {
    return val instanceof Error || typeof val.message === 'string' && val.constructor && typeof val.constructor.stackTraceLimit === 'number';
  }

  function isDate(val) {
    if (val instanceof Date) return true;
    return typeof val.toDateString === 'function' && typeof val.getDate === 'function' && typeof val.setDate === 'function';
  }

  function isRegexp(val) {
    if (val instanceof RegExp) return true;
    return typeof val.flags === 'string' && typeof val.ignoreCase === 'boolean' && typeof val.multiline === 'boolean' && typeof val.global === 'boolean';
  }

  function isGeneratorFn(name, val) {
    return ctorName(name) === 'GeneratorFunction';
  }

  function isGeneratorObj(val) {
    return typeof val.throw === 'function' && typeof val.return === 'function' && typeof val.next === 'function';
  }

  function isArguments(val) {
    try {
      if (typeof val.length === 'number' && typeof val.callee === 'function') {
        return true;
      }
    } catch (err) {
      if (err.message.indexOf('callee') !== -1) {
        return true;
      }
    }

    return false;
  }
  /**
   * If you need to support Safari 5-7 (8-10 yr-old browser),
   * take a look at https://github.com/feross/is-buffer
   */


  function isBuffer(val) {
    if (val.constructor && typeof val.constructor.isBuffer === 'function') {
      return val.constructor.isBuffer(val);
    }

    return false;
  }
  /***/

},
/* 72 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * micromatch <https://github.com/jonschlinkert/micromatch>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var expand = __webpack_require__(73);

  var utils = __webpack_require__(5);
  /**
   * The main function. Pass an array of filepaths,
   * and a string or array of glob patterns
   *
   * @param  {Array|String} `files`
   * @param  {Array|String} `patterns`
   * @param  {Object} `opts`
   * @return {Array} Array of matches
   */


  function micromatch(files, patterns, opts) {
    if (!files || !patterns) return [];
    opts = opts || {};

    if (typeof opts.cache === 'undefined') {
      opts.cache = true;
    }

    if (!Array.isArray(patterns)) {
      return match(files, patterns, opts);
    }

    var len = patterns.length,
        i = 0;
    var omit = [],
        keep = [];

    while (len--) {
      var glob = patterns[i++];

      if (typeof glob === 'string' && glob.charCodeAt(0) === 33
      /* ! */
      ) {
          omit.push.apply(omit, match(files, glob.slice(1), opts));
        } else {
        keep.push.apply(keep, match(files, glob, opts));
      }
    }

    return utils.diff(keep, omit);
  }
  /**
   * Return an array of files that match the given glob pattern.
   *
   * This function is called by the main `micromatch` function If you only
   * need to pass a single pattern you might get very minor speed improvements
   * using this function.
   *
   * @param  {Array} `files`
   * @param  {String} `pattern`
   * @param  {Object} `options`
   * @return {Array}
   */


  function match(files, pattern, opts) {
    if (utils.typeOf(files) !== 'string' && !Array.isArray(files)) {
      throw new Error(msg('match', 'files', 'a string or array'));
    }

    files = utils.arrayify(files);
    opts = opts || {};
    var negate = opts.negate || false;
    var orig = pattern;

    if (typeof pattern === 'string') {
      negate = pattern.charAt(0) === '!';

      if (negate) {
        pattern = pattern.slice(1);
      } // we need to remove the character regardless,
      // so the above logic is still needed


      if (opts.nonegate === true) {
        negate = false;
      }
    }

    var _isMatch = matcher(pattern, opts);

    var len = files.length,
        i = 0;
    var res = [];

    while (i < len) {
      var file = files[i++];
      var fp = utils.unixify(file, opts);

      if (!_isMatch(fp)) {
        continue;
      }

      res.push(fp);
    }

    if (res.length === 0) {
      if (opts.failglob === true) {
        throw new Error('micromatch.match() found no matches for: "' + orig + '".');
      }

      if (opts.nonull || opts.nullglob) {
        res.push(utils.unescapeGlob(orig));
      }
    } // if `negate` was defined, diff negated files


    if (negate) {
      res = utils.diff(files, res);
    } // if `ignore` was defined, diff ignored filed


    if (opts.ignore && opts.ignore.length) {
      pattern = opts.ignore;
      opts = utils.omit(opts, ['ignore']);
      res = utils.diff(res, micromatch(res, pattern, opts));
    }

    if (opts.nodupes) {
      return utils.unique(res);
    }

    return res;
  }
  /**
   * Returns a function that takes a glob pattern or array of glob patterns
   * to be used with `Array#filter()`. (Internally this function generates
   * the matching function using the [matcher] method).
   *
   * ```js
   * var fn = mm.filter('[a-c]');
   * ['a', 'b', 'c', 'd', 'e'].filter(fn);
   * //=> ['a', 'b', 'c']
   * ```
   * @param  {String|Array} `patterns` Can be a glob or array of globs.
   * @param  {Options} `opts` Options to pass to the [matcher] method.
   * @return {Function} Filter function to be passed to `Array#filter()`.
   */


  function filter(patterns, opts) {
    if (!Array.isArray(patterns) && typeof patterns !== 'string') {
      throw new TypeError(msg('filter', 'patterns', 'a string or array'));
    }

    patterns = utils.arrayify(patterns);
    var len = patterns.length,
        i = 0;
    var patternMatchers = Array(len);

    while (i < len) {
      patternMatchers[i] = matcher(patterns[i++], opts);
    }

    return function (fp) {
      if (fp == null) return [];
      var len = patternMatchers.length,
          i = 0;
      var res = true;
      fp = utils.unixify(fp, opts);

      while (i < len) {
        var fn = patternMatchers[i++];

        if (!fn(fp)) {
          res = false;
          break;
        }
      }

      return res;
    };
  }
  /**
   * Returns true if the filepath contains the given
   * pattern. Can also return a function for matching.
   *
   * ```js
   * isMatch('foo.md', '*.md', {});
   * //=> true
   *
   * isMatch('*.md', {})('foo.md')
   * //=> true
   * ```
   * @param  {String} `fp`
   * @param  {String} `pattern`
   * @param  {Object} `opts`
   * @return {Boolean}
   */


  function isMatch(fp, pattern, opts) {
    if (typeof fp !== 'string') {
      throw new TypeError(msg('isMatch', 'filepath', 'a string'));
    }

    fp = utils.unixify(fp, opts);

    if (utils.typeOf(pattern) === 'object') {
      return matcher(fp, pattern);
    }

    return matcher(pattern, opts)(fp);
  }
  /**
   * Returns true if the filepath matches the
   * given pattern.
   */


  function contains(fp, pattern, opts) {
    if (typeof fp !== 'string') {
      throw new TypeError(msg('contains', 'pattern', 'a string'));
    }

    opts = opts || {};
    opts.contains = pattern !== '';
    fp = utils.unixify(fp, opts);

    if (opts.contains && !utils.isGlob(pattern)) {
      return fp.indexOf(pattern) !== -1;
    }

    return matcher(pattern, opts)(fp);
  }
  /**
   * Returns true if a file path matches any of the
   * given patterns.
   *
   * @param  {String} `fp` The filepath to test.
   * @param  {String|Array} `patterns` Glob patterns to use.
   * @param  {Object} `opts` Options to pass to the `matcher()` function.
   * @return {String}
   */


  function any(fp, patterns, opts) {
    if (!Array.isArray(patterns) && typeof patterns !== 'string') {
      throw new TypeError(msg('any', 'patterns', 'a string or array'));
    }

    patterns = utils.arrayify(patterns);
    var len = patterns.length;
    fp = utils.unixify(fp, opts);

    while (len--) {
      var isMatch = matcher(patterns[len], opts);

      if (isMatch(fp)) {
        return true;
      }
    }

    return false;
  }
  /**
   * Filter the keys of an object with the given `glob` pattern
   * and `options`
   *
   * @param  {Object} `object`
   * @param  {Pattern} `object`
   * @return {Array}
   */


  function matchKeys(obj, glob, options) {
    if (utils.typeOf(obj) !== 'object') {
      throw new TypeError(msg('matchKeys', 'first argument', 'an object'));
    }

    var fn = matcher(glob, options);
    var res = {};

    for (var key in obj) {
      if (obj.hasOwnProperty(key) && fn(key)) {
        res[key] = obj[key];
      }
    }

    return res;
  }
  /**
   * Return a function for matching based on the
   * given `pattern` and `options`.
   *
   * @param  {String} `pattern`
   * @param  {Object} `options`
   * @return {Function}
   */


  function matcher(pattern, opts) {
    // pattern is a function
    if (typeof pattern === 'function') {
      return pattern;
    } // pattern is a regex


    if (pattern instanceof RegExp) {
      return function (fp) {
        return pattern.test(fp);
      };
    }

    if (typeof pattern !== 'string') {
      throw new TypeError(msg('matcher', 'pattern', 'a string, regex, or function'));
    } // strings, all the way down...


    pattern = utils.unixify(pattern, opts); // pattern is a non-glob string

    if (!utils.isGlob(pattern)) {
      return utils.matchPath(pattern, opts);
    } // pattern is a glob string


    var re = makeRe(pattern, opts); // `matchBase` is defined

    if (opts && opts.matchBase) {
      return utils.hasFilename(re, opts);
    } // `matchBase` is not defined


    return function (fp) {
      fp = utils.unixify(fp, opts);
      return re.test(fp);
    };
  }
  /**
   * Create and cache a regular expression for matching
   * file paths.
   *
   * If the leading character in the `glob` is `!`, a negation
   * regex is returned.
   *
   * @param  {String} `glob`
   * @param  {Object} `options`
   * @return {RegExp}
   */


  function toRegex(glob, options) {
    // clone options to prevent  mutating the original object
    var opts = Object.create(options || {});
    var flags = opts.flags || '';

    if (opts.nocase && flags.indexOf('i') === -1) {
      flags += 'i';
    }

    var parsed = expand(glob, opts); // pass in tokens to avoid parsing more than once

    opts.negated = opts.negated || parsed.negated;
    opts.negate = opts.negated;
    glob = wrapGlob(parsed.pattern, opts);
    var re;

    try {
      re = new RegExp(glob, flags);
      return re;
    } catch (err) {
      err.reason = 'micromatch invalid regex: (' + re + ')';
      if (opts.strict) throw new SyntaxError(err);
    } // we're only here if a bad pattern was used and the user
    // passed `options.silent`, so match nothing


    return /$^/;
  }
  /**
   * Create the regex to do the matching. If the leading
   * character in the `glob` is `!` a negation regex is returned.
   *
   * @param {String} `glob`
   * @param {Boolean} `negate`
   */


  function wrapGlob(glob, opts) {
    var prefix = opts && !opts.contains ? '^' : '';
    var after = opts && !opts.contains ? '$' : '';
    glob = '(?:' + glob + ')' + after;

    if (opts && opts.negate) {
      return prefix + ('(?!^' + glob + ').*$');
    }

    return prefix + glob;
  }
  /**
   * Create and cache a regular expression for matching file paths.
   * If the leading character in the `glob` is `!`, a negation
   * regex is returned.
   *
   * @param  {String} `glob`
   * @param  {Object} `options`
   * @return {RegExp}
   */


  function makeRe(glob, opts) {
    if (utils.typeOf(glob) !== 'string') {
      throw new Error(msg('makeRe', 'glob', 'a string'));
    }

    return utils.cache(toRegex, glob, opts);
  }
  /**
   * Make error messages consistent. Follows this format:
   *
   * ```js
   * msg(methodName, argNumber, nativeType);
   * // example:
   * msg('matchKeys', 'first', 'an object');
   * ```
   *
   * @param  {String} `method`
   * @param  {String} `num`
   * @param  {String} `type`
   * @return {String}
   */


  function msg(method, what, type) {
    return 'micromatch.' + method + '(): ' + what + ' should be ' + type + '.';
  }
  /**
   * Public methods
   */

  /* eslint no-multi-spaces: 0 */


  micromatch.any = any;
  micromatch.braces = micromatch.braceExpand = utils.braces;
  micromatch.contains = contains;
  micromatch.expand = expand;
  micromatch.filter = filter;
  micromatch.isMatch = isMatch;
  micromatch.makeRe = makeRe;
  micromatch.match = match;
  micromatch.matcher = matcher;
  micromatch.matchKeys = matchKeys;
  /**
   * Expose `micromatch`
   */

  module.exports = micromatch;
  /***/
},
/* 73 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * micromatch <https://github.com/jonschlinkert/micromatch>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var utils = __webpack_require__(5);

  var Glob = __webpack_require__(106);
  /**
   * Expose `expand`
   */


  module.exports = expand;
  /**
   * Expand a glob pattern to resolve braces and
   * similar patterns before converting to regex.
   *
   * @param  {String|Array} `pattern`
   * @param  {Array} `files`
   * @param  {Options} `opts`
   * @return {Array}
   */

  function expand(pattern, options) {
    if (typeof pattern !== 'string') {
      throw new TypeError('micromatch.expand(): argument should be a string.');
    }

    var glob = new Glob(pattern, options || {});
    var opts = glob.options;

    if (!utils.isGlob(pattern)) {
      glob.pattern = glob.pattern.replace(/([\/.])/g, '\\$1');
      return glob;
    }

    glob.pattern = glob.pattern.replace(/(\+)(?!\()/g, '\\$1');
    glob.pattern = glob.pattern.split('$').join('\\$');

    if (typeof opts.braces !== 'boolean' && typeof opts.nobraces !== 'boolean') {
      opts.braces = true;
    }

    if (glob.pattern === '.*') {
      return {
        pattern: '\\.' + star,
        tokens: tok,
        options: opts
      };
    }

    if (glob.pattern === '*') {
      return {
        pattern: oneStar(opts.dot),
        tokens: tok,
        options: opts
      };
    } // parse the glob pattern into tokens


    glob.parse();
    var tok = glob.tokens;
    tok.is.negated = opts.negated; // dotfile handling

    if ((opts.dotfiles === true || tok.is.dotfile) && opts.dot !== false) {
      opts.dotfiles = true;
      opts.dot = true;
    }

    if ((opts.dotdirs === true || tok.is.dotdir) && opts.dot !== false) {
      opts.dotdirs = true;
      opts.dot = true;
    } // check for braces with a dotfile pattern


    if (/[{,]\./.test(glob.pattern)) {
      opts.makeRe = false;
      opts.dot = true;
    }

    if (opts.nonegate !== true) {
      opts.negated = glob.negated;
    } // if the leading character is a dot or a slash, escape it


    if (glob.pattern.charAt(0) === '.' && glob.pattern.charAt(1) !== '/') {
      glob.pattern = '\\' + glob.pattern;
    }
    /**
     * Extended globs
     */
    // expand braces, e.g `{1..5}`


    glob.track('before braces');

    if (tok.is.braces) {
      glob.braces();
    }

    glob.track('after braces'); // expand extglobs, e.g `foo/!(a|b)`

    glob.track('before extglob');

    if (tok.is.extglob) {
      glob.extglob();
    }

    glob.track('after extglob'); // expand brackets, e.g `[[:alpha:]]`

    glob.track('before brackets');

    if (tok.is.brackets) {
      glob.brackets();
    }

    glob.track('after brackets'); // special patterns

    glob._replace('[!', '[^');

    glob._replace('(?', '(%~');

    glob._replace(/\[\]/, '\\[\\]');

    glob._replace('/[', '/' + (opts.dot ? dotfiles : nodot) + '[', true);

    glob._replace('/?', '/' + (opts.dot ? dotfiles : nodot) + '[^/]', true);

    glob._replace('/.', '/(?=.)\\.', true); // windows drives


    glob._replace(/^(\w):([\\\/]+?)/gi, '(?=.)$1:$2', true); // negate slashes in exclusion ranges


    if (glob.pattern.indexOf('[^') !== -1) {
      glob.pattern = negateSlash(glob.pattern);
    }

    if (opts.globstar !== false && glob.pattern === '**') {
      glob.pattern = globstar(opts.dot);
    } else {
      glob.pattern = balance(glob.pattern, '[', ']');
      glob.escape(glob.pattern); // if the pattern has `**`

      if (tok.is.globstar) {
        glob.pattern = collapse(glob.pattern, '/**');
        glob.pattern = collapse(glob.pattern, '**/');

        glob._replace('/**/', '(?:/' + globstar(opts.dot) + '/|/)', true);

        glob._replace(/\*{2,}/g, '**'); // 'foo/*'


        glob._replace(/(\w+)\*(?!\/)/g, '$1[^/]*?', true);

        glob._replace(/\*\*\/\*(\w)/g, globstar(opts.dot) + '\\/' + (opts.dot ? dotfiles : nodot) + '[^/]*?$1', true);

        if (opts.dot !== true) {
          glob._replace(/\*\*\/(.)/g, '(?:**\\/|)$1');
        } // 'foo/**' or '{**,*}', but not 'foo**'


        if (tok.path.dirname !== '' || /,\*\*|\*\*,/.test(glob.orig)) {
          glob._replace('**', globstar(opts.dot), true);
        }
      } // ends with /*


      glob._replace(/\/\*$/, '\\/' + oneStar(opts.dot), true); // ends with *, no slashes


      glob._replace(/(?!\/)\*$/, star, true); // has 'n*.' (partial wildcard w/ file extension)


      glob._replace(/([^\/]+)\*/, '$1' + oneStar(true), true); // has '*'


      glob._replace('*', oneStar(opts.dot), true);

      glob._replace('?.', '?\\.', true);

      glob._replace('?:', '?:', true);

      glob._replace(/\?+/g, function (match) {
        var len = match.length;

        if (len === 1) {
          return qmark;
        }

        return qmark + '{' + len + '}';
      }); // escape '.abc' => '\\.abc'


      glob._replace(/\.([*\w]+)/g, '\\.$1'); // fix '[^\\\\/]'


      glob._replace(/\[\^[\\\/]+\]/g, qmark); // '///' => '\/'


      glob._replace(/\/+/g, '\\/'); // '\\\\\\' => '\\'


      glob._replace(/\\{2,}/g, '\\');
    } // unescape previously escaped patterns


    glob.unescape(glob.pattern);

    glob._replace('__UNESC_STAR__', '*'); // escape dots that follow qmarks


    glob._replace('?.', '?\\.'); // remove unnecessary slashes in character classes


    glob._replace('[^\\/]', qmark);

    if (glob.pattern.length > 1) {
      if (/^[\[?*]/.test(glob.pattern)) {
        // only prepend the string if we don't want to match dotfiles
        glob.pattern = (opts.dot ? dotfiles : nodot) + glob.pattern;
      }
    }

    return glob;
  }
  /**
   * Collapse repeated character sequences.
   *
   * ```js
   * collapse('a/../../../b', '../');
   * //=> 'a/../b'
   * ```
   *
   * @param  {String} `str`
   * @param  {String} `ch` Character sequence to collapse
   * @return {String}
   */


  function collapse(str, ch) {
    var res = str.split(ch);
    var isFirst = res[0] === '';
    var isLast = res[res.length - 1] === '';
    res = res.filter(Boolean);
    if (isFirst) res.unshift('');
    if (isLast) res.push('');
    return res.join(ch);
  }
  /**
   * Negate slashes in exclusion ranges, per glob spec:
   *
   * ```js
   * negateSlash('[^foo]');
   * //=> '[^\\/foo]'
   * ```
   *
   * @param  {String} `str` glob pattern
   * @return {String}
   */


  function negateSlash(str) {
    return str.replace(/\[\^([^\]]*?)\]/g, function (match, inner) {
      if (inner.indexOf('/') === -1) {
        inner = '\\/' + inner;
      }

      return '[^' + inner + ']';
    });
  }
  /**
   * Escape imbalanced braces/bracket. This is a very
   * basic, naive implementation that only does enough
   * to serve the purpose.
   */


  function balance(str, a, b) {
    var aarr = str.split(a);
    var alen = aarr.join('').length;
    var blen = str.split(b).join('').length;

    if (alen !== blen) {
      str = aarr.join('\\' + a);
      return str.split(b).join('\\' + b);
    }

    return str;
  }
  /**
   * Special patterns to be converted to regex.
   * Heuristics are used to simplify patterns
   * and speed up processing.
   */

  /* eslint no-multi-spaces: 0 */


  var qmark = '[^/]';
  var star = qmark + '*?';
  var nodot = '(?!\\.)(?=.)';
  var dotfileGlob = '(?:\\/|^)\\.{1,2}($|\\/)';
  var dotfiles = '(?!' + dotfileGlob + ')(?=.)';
  var twoStarDot = '(?:(?!' + dotfileGlob + ').)*?';
  /**
   * Create a regex for `*`.
   *
   * If `dot` is true, or the pattern does not begin with
   * a leading star, then return the simpler regex.
   */

  function oneStar(dotfile) {
    return dotfile ? '(?!' + dotfileGlob + ')(?=.)' + star : nodot + star;
  }

  function globstar(dotfile) {
    if (dotfile) {
      return twoStarDot;
    }

    return '(?:(?!(?:\\/|^)\\.).)*?';
  }
  /***/

},
/* 74 */

/***/
function (module, exports) {
  /*!
   * filename-regex <https://github.com/regexps/filename-regex>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert
   * Licensed under the MIT license.
   */
  module.exports = function filenameRegex() {
    return /([^\\\/]+)$/;
  };
  /***/

},
/* 75 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * arr-diff <https://github.com/jonschlinkert/arr-diff>
   *
   * Copyright (c) 2014 Jon Schlinkert, contributors.
   * Licensed under the MIT License
   */

  var flatten = __webpack_require__(76);

  var slice = [].slice;
  /**
   * Return the difference between the first array and
   * additional arrays.
   *
   * ```js
   * var diff = require('{%= name %}');
   *
   * var a = ['a', 'b', 'c', 'd'];
   * var b = ['b', 'c'];
   *
   * console.log(diff(a, b))
   * //=> ['a', 'd']
   * ```
   *
   * @param  {Array} `a`
   * @param  {Array} `b`
   * @return {Array}
   * @api public
   */

  function diff(arr, arrays) {
    var argsLen = arguments.length;
    var len = arr.length,
        i = -1;
    var res = [],
        arrays;

    if (argsLen === 1) {
      return arr;
    }

    if (argsLen > 2) {
      arrays = flatten(slice.call(arguments, 1));
    }

    while (++i < len) {
      if (!~arrays.indexOf(arr[i])) {
        res.push(arr[i]);
      }
    }

    return res;
  }
  /**
   * Expose `diff`
   */


  module.exports = diff;
  /***/
},
/* 76 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * arr-flatten <https://github.com/jonschlinkert/arr-flatten>
   *
   * Copyright (c) 2014-2017, Jon Schlinkert.
   * Released under the MIT License.
   */

  module.exports = function (arr) {
    return flat(arr, []);
  };

  function flat(arr, res) {
    var i = 0,
        cur;
    var len = arr.length;

    for (; i < len; i++) {
      cur = arr[i];
      Array.isArray(cur) ? flat(cur, res) : res.push(cur);
    }

    return res;
  }
  /***/

},
/* 77 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * braces <https://github.com/jonschlinkert/braces>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT license.
   */

  /**
   * Module dependencies
   */

  var expand = __webpack_require__(78);

  var repeat = __webpack_require__(19);

  var tokens = __webpack_require__(89);
  /**
   * Expose `braces`
   */


  module.exports = function (str, options) {
    if (typeof str !== 'string') {
      throw new Error('braces expects a string');
    }

    return braces(str, options);
  };
  /**
   * Expand `{foo,bar}` or `{1..5}` braces in the
   * given `string`.
   *
   * @param  {String} `str`
   * @param  {Array} `arr`
   * @param  {Object} `options`
   * @return {Array}
   */


  function braces(str, arr, options) {
    if (str === '') {
      return [];
    }

    if (!Array.isArray(arr)) {
      options = arr;
      arr = [];
    }

    var opts = options || {};
    arr = arr || [];

    if (typeof opts.nodupes === 'undefined') {
      opts.nodupes = true;
    }

    var fn = opts.fn;
    var es6;

    if (typeof opts === 'function') {
      fn = opts;
      opts = {};
    }

    if (!(patternRe instanceof RegExp)) {
      patternRe = patternRegex();
    }

    var matches = str.match(patternRe) || [];
    var m = matches[0];

    switch (m) {
      case '\\,':
        return escapeCommas(str, arr, opts);

      case '\\.':
        return escapeDots(str, arr, opts);

      case '\/.':
        return escapePaths(str, arr, opts);

      case ' ':
        return splitWhitespace(str);

      case '{,}':
        return exponential(str, opts, braces);

      case '{}':
        return emptyBraces(str, arr, opts);

      case '\\{':
      case '\\}':
        return escapeBraces(str, arr, opts);

      case '${':
        if (!/\{[^{]+\{/.test(str)) {
          return arr.concat(str);
        } else {
          es6 = true;
          str = tokens.before(str, es6Regex());
        }

    }

    if (!(braceRe instanceof RegExp)) {
      braceRe = braceRegex();
    }

    var match = braceRe.exec(str);

    if (match == null) {
      return [str];
    }

    var outter = match[1];
    var inner = match[2];

    if (inner === '') {
      return [str];
    }

    var segs, segsLength;

    if (inner.indexOf('..') !== -1) {
      segs = expand(inner, opts, fn) || inner.split(',');
      segsLength = segs.length;
    } else if (inner[0] === '"' || inner[0] === '\'') {
      return arr.concat(str.split(/['"]/).join(''));
    } else {
      segs = inner.split(',');

      if (opts.makeRe) {
        return braces(str.replace(outter, wrap(segs, '|')), opts);
      }

      segsLength = segs.length;

      if (segsLength === 1 && opts.bash) {
        segs[0] = wrap(segs[0], '\\');
      }
    }

    var len = segs.length;
    var i = 0,
        val;

    while (len--) {
      var path = segs[i++];

      if (/(\.[^.\/])/.test(path)) {
        if (segsLength > 1) {
          return segs;
        } else {
          return [str];
        }
      }

      val = splice(str, outter, path);

      if (/\{[^{}]+?\}/.test(val)) {
        arr = braces(val, arr, opts);
      } else if (val !== '') {
        if (opts.nodupes && arr.indexOf(val) !== -1) {
          continue;
        }

        arr.push(es6 ? tokens.after(val) : val);
      }
    }

    if (opts.strict) {
      return filter(arr, filterEmpty);
    }

    return arr;
  }
  /**
   * Expand exponential ranges
   *
   *   `a{,}{,}` => ['a', 'a', 'a', 'a']
   */


  function exponential(str, options, fn) {
    if (typeof options === 'function') {
      fn = options;
      options = null;
    }

    var opts = options || {};
    var esc = '__ESC_EXP__';
    var exp = 0;
    var res;
    var parts = str.split('{,}');

    if (opts.nodupes) {
      return fn(parts.join(''), opts);
    }

    exp = parts.length - 1;
    res = fn(parts.join(esc), opts);
    var len = res.length;
    var arr = [];
    var i = 0;

    while (len--) {
      var ele = res[i++];
      var idx = ele.indexOf(esc);

      if (idx === -1) {
        arr.push(ele);
      } else {
        ele = ele.split('__ESC_EXP__').join('');

        if (!!ele && opts.nodupes !== false) {
          arr.push(ele);
        } else {
          var num = Math.pow(2, exp);
          arr.push.apply(arr, repeat(ele, num));
        }
      }
    }

    return arr;
  }
  /**
   * Wrap a value with parens, brackets or braces,
   * based on the given character/separator.
   *
   * @param  {String|Array} `val`
   * @param  {String} `ch`
   * @return {String}
   */


  function wrap(val, ch) {
    if (ch === '|') {
      return '(' + val.join(ch) + ')';
    }

    if (ch === ',') {
      return '{' + val.join(ch) + '}';
    }

    if (ch === '-') {
      return '[' + val.join(ch) + ']';
    }

    if (ch === '\\') {
      return '\\{' + val + '\\}';
    }
  }
  /**
   * Handle empty braces: `{}`
   */


  function emptyBraces(str, arr, opts) {
    return braces(str.split('{}').join('\\{\\}'), arr, opts);
  }
  /**
   * Filter out empty-ish values
   */


  function filterEmpty(ele) {
    return !!ele && ele !== '\\';
  }
  /**
   * Handle patterns with whitespace
   */


  function splitWhitespace(str) {
    var segs = str.split(' ');
    var len = segs.length;
    var res = [];
    var i = 0;

    while (len--) {
      res.push.apply(res, braces(segs[i++]));
    }

    return res;
  }
  /**
   * Handle escaped braces: `\\{foo,bar}`
   */


  function escapeBraces(str, arr, opts) {
    if (!/\{[^{]+\{/.test(str)) {
      return arr.concat(str.split('\\').join(''));
    } else {
      str = str.split('\\{').join('__LT_BRACE__');
      str = str.split('\\}').join('__RT_BRACE__');
      return map(braces(str, arr, opts), function (ele) {
        ele = ele.split('__LT_BRACE__').join('{');
        return ele.split('__RT_BRACE__').join('}');
      });
    }
  }
  /**
   * Handle escaped dots: `{1\\.2}`
   */


  function escapeDots(str, arr, opts) {
    if (!/[^\\]\..+\\\./.test(str)) {
      return arr.concat(str.split('\\').join(''));
    } else {
      str = str.split('\\.').join('__ESC_DOT__');
      return map(braces(str, arr, opts), function (ele) {
        return ele.split('__ESC_DOT__').join('.');
      });
    }
  }
  /**
   * Handle escaped dots: `{1\\.2}`
   */


  function escapePaths(str, arr, opts) {
    str = str.split('\/.').join('__ESC_PATH__');
    return map(braces(str, arr, opts), function (ele) {
      return ele.split('__ESC_PATH__').join('\/.');
    });
  }
  /**
   * Handle escaped commas: `{a\\,b}`
   */


  function escapeCommas(str, arr, opts) {
    if (!/\w,/.test(str)) {
      return arr.concat(str.split('\\').join(''));
    } else {
      str = str.split('\\,').join('__ESC_COMMA__');
      return map(braces(str, arr, opts), function (ele) {
        return ele.split('__ESC_COMMA__').join(',');
      });
    }
  }
  /**
   * Regex for common patterns
   */


  function patternRegex() {
    return /\${|( (?=[{,}])|(?=[{,}]) )|{}|{,}|\\,(?=.*[{}])|\/\.(?=.*[{}])|\\\.(?={)|\\{|\\}/;
  }
  /**
   * Braces regex.
   */


  function braceRegex() {
    return /.*(\\?\{([^}]+)\})/;
  }
  /**
   * es6 delimiter regex.
   */


  function es6Regex() {
    return /\$\{([^}]+)\}/;
  }

  var braceRe;
  var patternRe;
  /**
   * Faster alternative to `String.replace()` when the
   * index of the token to be replaces can't be supplied
   */

  function splice(str, token, replacement) {
    var i = str.indexOf(token);
    return str.substr(0, i) + replacement + str.substr(i + token.length);
  }
  /**
   * Fast array map
   */


  function map(arr, fn) {
    if (arr == null) {
      return [];
    }

    var len = arr.length;
    var res = new Array(len);
    var i = -1;

    while (++i < len) {
      res[i] = fn(arr[i], i, arr);
    }

    return res;
  }
  /**
   * Fast array filter
   */


  function filter(arr, cb) {
    if (arr == null) return [];

    if (typeof cb !== 'function') {
      throw new TypeError('braces: filter expects a callback function.');
    }

    var len = arr.length;
    var res = arr.slice();
    var i = 0;

    while (len--) {
      if (!cb(arr[len], i++)) {
        res.splice(len, 1);
      }
    }

    return res;
  }
  /***/

},
/* 78 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * expand-range <https://github.com/jonschlinkert/expand-range>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT license.
   */

  var fill = __webpack_require__(79);

  module.exports = function expandRange(str, options, fn) {
    if (typeof str !== 'string') {
      throw new TypeError('expand-range expects a string.');
    }

    if (typeof options === 'function') {
      fn = options;
      options = {};
    }

    if (typeof options === 'boolean') {
      options = {};
      options.makeRe = true;
    } // create arguments to pass to fill-range


    var opts = options || {};
    var args = str.split('..');
    var len = args.length;

    if (len > 3) {
      return str;
    } // if only one argument, it can't expand so return it


    if (len === 1) {
      return args;
    } // if `true`, tell fill-range to regexify the string


    if (typeof fn === 'boolean' && fn === true) {
      opts.makeRe = true;
    }

    args.push(opts);
    return fill.apply(null, args.concat(fn));
  };
  /***/

},
/* 79 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * fill-range <https://github.com/jonschlinkert/fill-range>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var isObject = __webpack_require__(80);

  var isNumber = __webpack_require__(82);

  var randomize = __webpack_require__(84);

  var repeatStr = __webpack_require__(88);

  var repeat = __webpack_require__(19);
  /**
   * Expose `fillRange`
   */


  module.exports = fillRange;
  /**
   * Return a range of numbers or letters.
   *
   * @param  {String} `a` Start of the range
   * @param  {String} `b` End of the range
   * @param  {String} `step` Increment or decrement to use.
   * @param  {Function} `fn` Custom function to modify each element in the range.
   * @return {Array}
   */

  function fillRange(a, b, step, options, fn) {
    if (a == null || b == null) {
      throw new Error('fill-range expects the first and second args to be strings.');
    }

    if (typeof step === 'function') {
      fn = step;
      options = {};
      step = null;
    }

    if (typeof options === 'function') {
      fn = options;
      options = {};
    }

    if (isObject(step)) {
      options = step;
      step = '';
    }

    var expand,
        regex = false,
        sep = '';
    var opts = options || {};

    if (typeof opts.silent === 'undefined') {
      opts.silent = true;
    }

    step = step || opts.step; // store a ref to unmodified arg

    var origA = a,
        origB = b;
    b = b.toString() === '-0' ? 0 : b;

    if (opts.optimize || opts.makeRe) {
      step = step ? step += '~' : step;
      expand = true;
      regex = true;
      sep = '~';
    } // handle special step characters


    if (typeof step === 'string') {
      var match = stepRe().exec(step);

      if (match) {
        var i = match.index;
        var m = match[0]; // repeat string

        if (m === '+') {
          return repeat(a, b); // randomize a, `b` times
        } else if (m === '?') {
          return [randomize(a, b)]; // expand right, no regex reduction
        } else if (m === '>') {
          step = step.substr(0, i) + step.substr(i + 1);
          expand = true; // expand to an array, or if valid create a reduced
          // string for a regex logic `or`
        } else if (m === '|') {
          step = step.substr(0, i) + step.substr(i + 1);
          expand = true;
          regex = true;
          sep = m; // expand to an array, or if valid create a reduced
          // string for a regex range
        } else if (m === '~') {
          step = step.substr(0, i) + step.substr(i + 1);
          expand = true;
          regex = true;
          sep = m;
        }
      } else if (!isNumber(step)) {
        if (!opts.silent) {
          throw new TypeError('fill-range: invalid step.');
        }

        return null;
      }
    }

    if (/[.&*()[\]^%$#@!]/.test(a) || /[.&*()[\]^%$#@!]/.test(b)) {
      if (!opts.silent) {
        throw new RangeError('fill-range: invalid range arguments.');
      }

      return null;
    } // has neither a letter nor number, or has both letters and numbers
    // this needs to be after the step logic


    if (!noAlphaNum(a) || !noAlphaNum(b) || hasBoth(a) || hasBoth(b)) {
      if (!opts.silent) {
        throw new RangeError('fill-range: invalid range arguments.');
      }

      return null;
    } // validate arguments


    var isNumA = isNumber(zeros(a));
    var isNumB = isNumber(zeros(b));

    if (!isNumA && isNumB || isNumA && !isNumB) {
      if (!opts.silent) {
        throw new TypeError('fill-range: first range argument is incompatible with second.');
      }

      return null;
    } // by this point both are the same, so we
    // can use A to check going forward.


    var isNum = isNumA;
    var num = formatStep(step); // is the range alphabetical? or numeric?

    if (isNum) {
      // if numeric, coerce to an integer
      a = +a;
      b = +b;
    } else {
      // otherwise, get the charCode to expand alpha ranges
      a = a.charCodeAt(0);
      b = b.charCodeAt(0);
    } // is the pattern descending?


    var isDescending = a > b; // don't create a character class if the args are < 0

    if (a < 0 || b < 0) {
      expand = false;
      regex = false;
    } // detect padding


    var padding = isPadded(origA, origB);
    var res,
        pad,
        arr = [];
    var ii = 0; // character classes, ranges and logical `or`

    if (regex) {
      if (shouldExpand(a, b, num, isNum, padding, opts)) {
        // make sure the correct separator is used
        if (sep === '|' || sep === '~') {
          sep = detectSeparator(a, b, num, isNum, isDescending);
        }

        return wrap([origA, origB], sep, opts);
      }
    }

    while (isDescending ? a >= b : a <= b) {
      if (padding && isNum) {
        pad = padding(a);
      } // custom function


      if (typeof fn === 'function') {
        res = fn(a, isNum, pad, ii++); // letters
      } else if (!isNum) {
        if (regex && isInvalidChar(a)) {
          res = null;
        } else {
          res = String.fromCharCode(a);
        } // numbers

      } else {
        res = formatPadding(a, pad);
      } // add result to the array, filtering any nulled values


      if (res !== null) arr.push(res); // increment or decrement

      if (isDescending) {
        a -= num;
      } else {
        a += num;
      }
    } // now that the array is expanded, we need to handle regex
    // character classes, ranges or logical `or` that wasn't
    // already handled before the loop


    if ((regex || expand) && !opts.noexpand) {
      // make sure the correct separator is used
      if (sep === '|' || sep === '~') {
        sep = detectSeparator(a, b, num, isNum, isDescending);
      }

      if (arr.length === 1 || a < 0 || b < 0) {
        return arr;
      }

      return wrap(arr, sep, opts);
    }

    return arr;
  }
  /**
   * Wrap the string with the correct regex
   * syntax.
   */


  function wrap(arr, sep, opts) {
    if (sep === '~') {
      sep = '-';
    }

    var str = arr.join(sep);
    var pre = opts && opts.regexPrefix; // regex logical `or`

    if (sep === '|') {
      str = pre ? pre + str : str;
      str = '(' + str + ')';
    } // regex character class


    if (sep === '-') {
      str = pre && pre === '^' ? pre + str : str;
      str = '[' + str + ']';
    }

    return [str];
  }
  /**
   * Check for invalid characters
   */


  function isCharClass(a, b, step, isNum, isDescending) {
    if (isDescending) {
      return false;
    }

    if (isNum) {
      return a <= 9 && b <= 9;
    }

    if (a < b) {
      return step === 1;
    }

    return false;
  }
  /**
   * Detect the correct separator to use
   */


  function shouldExpand(a, b, num, isNum, padding, opts) {
    if (isNum && (a > 9 || b > 9)) {
      return false;
    }

    return !padding && num === 1 && a < b;
  }
  /**
   * Detect the correct separator to use
   */


  function detectSeparator(a, b, step, isNum, isDescending) {
    var isChar = isCharClass(a, b, step, isNum, isDescending);

    if (!isChar) {
      return '|';
    }

    return '~';
  }
  /**
   * Correctly format the step based on type
   */


  function formatStep(step) {
    return Math.abs(step >> 0) || 1;
  }
  /**
   * Format padding, taking leading `-` into account
   */


  function formatPadding(ch, pad) {
    var res = pad ? pad + ch : ch;

    if (pad && ch.toString().charAt(0) === '-') {
      res = '-' + pad + ch.toString().substr(1);
    }

    return res.toString();
  }
  /**
   * Check for invalid characters
   */


  function isInvalidChar(str) {
    var ch = toStr(str);
    return ch === '\\' || ch === '[' || ch === ']' || ch === '^' || ch === '(' || ch === ')' || ch === '`';
  }
  /**
   * Convert to a string from a charCode
   */


  function toStr(ch) {
    return String.fromCharCode(ch);
  }
  /**
   * Step regex
   */


  function stepRe() {
    return /\?|>|\||\+|\~/g;
  }
  /**
   * Return true if `val` has either a letter
   * or a number
   */


  function noAlphaNum(val) {
    return /[a-z0-9]/i.test(val);
  }
  /**
   * Return true if `val` has both a letter and
   * a number (invalid)
   */


  function hasBoth(val) {
    return /[a-z][0-9]|[0-9][a-z]/i.test(val);
  }
  /**
   * Normalize zeros for checks
   */


  function zeros(val) {
    if (/^-*0+$/.test(val.toString())) {
      return '0';
    }

    return val;
  }
  /**
   * Return true if `val` has leading zeros,
   * or a similar valid pattern.
   */


  function hasZeros(val) {
    return /[^.]\.|^-*0+[0-9]/.test(val);
  }
  /**
   * If the string is padded, returns a curried function with
   * the a cached padding string, or `false` if no padding.
   *
   * @param  {*} `origA` String or number.
   * @return {String|Boolean}
   */


  function isPadded(origA, origB) {
    if (hasZeros(origA) || hasZeros(origB)) {
      var alen = length(origA);
      var blen = length(origB);
      var len = alen >= blen ? alen : blen;
      return function (a) {
        return repeatStr('0', len - length(a));
      };
    }

    return false;
  }
  /**
   * Get the string length of `val`
   */


  function length(val) {
    return val.toString().length;
  }
  /***/

},
/* 80 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * isobject <https://github.com/jonschlinkert/isobject>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var isArray = __webpack_require__(81);

  module.exports = function isObject(val) {
    return val != null && _typeof2(val) === 'object' && isArray(val) === false;
  };
  /***/

},
/* 81 */

/***/
function (module, exports) {
  var toString = {}.toString;

  module.exports = Array.isArray || function (arr) {
    return toString.call(arr) == '[object Array]';
  };
  /***/

},
/* 82 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * is-number <https://github.com/jonschlinkert/is-number>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var typeOf = __webpack_require__(83);

  module.exports = function isNumber(num) {
    var type = typeOf(num);

    if (type !== 'number' && type !== 'string') {
      return false;
    }

    var n = +num;
    return n - n + 1 >= 0 && num !== '';
  };
  /***/

},
/* 83 */

/***/
function (module, exports, __webpack_require__) {
  var isBuffer = __webpack_require__(2);

  var toString = Object.prototype.toString;
  /**
   * Get the native `typeof` a value.
   *
   * @param  {*} `val`
   * @return {*} Native javascript type
   */

  module.exports = function kindOf(val) {
    // primitivies
    if (typeof val === 'undefined') {
      return 'undefined';
    }

    if (val === null) {
      return 'null';
    }

    if (val === true || val === false || val instanceof Boolean) {
      return 'boolean';
    }

    if (typeof val === 'string' || val instanceof String) {
      return 'string';
    }

    if (typeof val === 'number' || val instanceof Number) {
      return 'number';
    } // functions


    if (typeof val === 'function' || val instanceof Function) {
      return 'function';
    } // array


    if (typeof Array.isArray !== 'undefined' && Array.isArray(val)) {
      return 'array';
    } // check for instances of RegExp and Date before calling `toString`


    if (val instanceof RegExp) {
      return 'regexp';
    }

    if (val instanceof Date) {
      return 'date';
    } // other objects


    var type = toString.call(val);

    if (type === '[object RegExp]') {
      return 'regexp';
    }

    if (type === '[object Date]') {
      return 'date';
    }

    if (type === '[object Arguments]') {
      return 'arguments';
    }

    if (type === '[object Error]') {
      return 'error';
    } // buffer


    if (isBuffer(val)) {
      return 'buffer';
    } // es6: Map, WeakMap, Set, WeakSet


    if (type === '[object Set]') {
      return 'set';
    }

    if (type === '[object WeakSet]') {
      return 'weakset';
    }

    if (type === '[object Map]') {
      return 'map';
    }

    if (type === '[object WeakMap]') {
      return 'weakmap';
    }

    if (type === '[object Symbol]') {
      return 'symbol';
    } // typed arrays


    if (type === '[object Int8Array]') {
      return 'int8array';
    }

    if (type === '[object Uint8Array]') {
      return 'uint8array';
    }

    if (type === '[object Uint8ClampedArray]') {
      return 'uint8clampedarray';
    }

    if (type === '[object Int16Array]') {
      return 'int16array';
    }

    if (type === '[object Uint16Array]') {
      return 'uint16array';
    }

    if (type === '[object Int32Array]') {
      return 'int32array';
    }

    if (type === '[object Uint32Array]') {
      return 'uint32array';
    }

    if (type === '[object Float32Array]') {
      return 'float32array';
    }

    if (type === '[object Float64Array]') {
      return 'float64array';
    } // must be a plain object


    return 'object';
  };
  /***/

},
/* 84 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * randomatic <https://github.com/jonschlinkert/randomatic>
   *
   * Copyright (c) 2014-2017, Jon Schlinkert.
   * Released under the MIT License.
   */

  var isNumber = __webpack_require__(85);

  var typeOf = __webpack_require__(87);
  /**
   * Expose `randomatic`
   */


  module.exports = randomatic;
  /**
   * Available mask characters
   */

  var type = {
    lower: 'abcdefghijklmnopqrstuvwxyz',
    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    number: '0123456789',
    special: '~!@#$%^&()_+-={}[];\',.'
  };
  type.all = type.lower + type.upper + type.number + type.special;
  /**
   * Generate random character sequences of a specified `length`,
   * based on the given `pattern`.
   *
   * @param {String} `pattern` The pattern to use for generating the random string.
   * @param {String} `length` The length of the string to generate.
   * @param {String} `options`
   * @return {String}
   * @api public
   */

  function randomatic(pattern, length, options) {
    if (typeof pattern === 'undefined') {
      throw new Error('randomatic expects a string or number.');
    }

    var custom = false;

    if (arguments.length === 1) {
      if (typeof pattern === 'string') {
        length = pattern.length;
      } else if (isNumber(pattern)) {
        options = {};
        length = pattern;
        pattern = '*';
      }
    }

    if (typeOf(length) === 'object' && length.hasOwnProperty('chars')) {
      options = length;
      pattern = options.chars;
      length = pattern.length;
      custom = true;
    }

    var opts = options || {};
    var mask = '';
    var res = ''; // Characters to be used

    if (pattern.indexOf('?') !== -1) mask += opts.chars;
    if (pattern.indexOf('a') !== -1) mask += type.lower;
    if (pattern.indexOf('A') !== -1) mask += type.upper;
    if (pattern.indexOf('0') !== -1) mask += type.number;
    if (pattern.indexOf('!') !== -1) mask += type.special;
    if (pattern.indexOf('*') !== -1) mask += type.all;
    if (custom) mask += pattern;

    while (length--) {
      res += mask.charAt(parseInt(Math.random() * mask.length, 10));
    }

    return res;
  }

  ;
  /***/
},
/* 85 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * is-number <https://github.com/jonschlinkert/is-number>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var typeOf = __webpack_require__(86);

  module.exports = function isNumber(num) {
    var type = typeOf(num);

    if (type === 'string') {
      if (!num.trim()) return false;
    } else if (type !== 'number') {
      return false;
    }

    return num - num + 1 >= 0;
  };
  /***/

},
/* 86 */

/***/
function (module, exports, __webpack_require__) {
  var isBuffer = __webpack_require__(2);

  var toString = Object.prototype.toString;
  /**
   * Get the native `typeof` a value.
   *
   * @param  {*} `val`
   * @return {*} Native javascript type
   */

  module.exports = function kindOf(val) {
    // primitivies
    if (typeof val === 'undefined') {
      return 'undefined';
    }

    if (val === null) {
      return 'null';
    }

    if (val === true || val === false || val instanceof Boolean) {
      return 'boolean';
    }

    if (typeof val === 'string' || val instanceof String) {
      return 'string';
    }

    if (typeof val === 'number' || val instanceof Number) {
      return 'number';
    } // functions


    if (typeof val === 'function' || val instanceof Function) {
      return 'function';
    } // array


    if (typeof Array.isArray !== 'undefined' && Array.isArray(val)) {
      return 'array';
    } // check for instances of RegExp and Date before calling `toString`


    if (val instanceof RegExp) {
      return 'regexp';
    }

    if (val instanceof Date) {
      return 'date';
    } // other objects


    var type = toString.call(val);

    if (type === '[object RegExp]') {
      return 'regexp';
    }

    if (type === '[object Date]') {
      return 'date';
    }

    if (type === '[object Arguments]') {
      return 'arguments';
    }

    if (type === '[object Error]') {
      return 'error';
    } // buffer


    if (isBuffer(val)) {
      return 'buffer';
    } // es6: Map, WeakMap, Set, WeakSet


    if (type === '[object Set]') {
      return 'set';
    }

    if (type === '[object WeakSet]') {
      return 'weakset';
    }

    if (type === '[object Map]') {
      return 'map';
    }

    if (type === '[object WeakMap]') {
      return 'weakmap';
    }

    if (type === '[object Symbol]') {
      return 'symbol';
    } // typed arrays


    if (type === '[object Int8Array]') {
      return 'int8array';
    }

    if (type === '[object Uint8Array]') {
      return 'uint8array';
    }

    if (type === '[object Uint8ClampedArray]') {
      return 'uint8clampedarray';
    }

    if (type === '[object Int16Array]') {
      return 'int16array';
    }

    if (type === '[object Uint16Array]') {
      return 'uint16array';
    }

    if (type === '[object Int32Array]') {
      return 'int32array';
    }

    if (type === '[object Uint32Array]') {
      return 'uint32array';
    }

    if (type === '[object Float32Array]') {
      return 'float32array';
    }

    if (type === '[object Float64Array]') {
      return 'float64array';
    } // must be a plain object


    return 'object';
  };
  /***/

},
/* 87 */

/***/
function (module, exports, __webpack_require__) {
  var isBuffer = __webpack_require__(2);

  var toString = Object.prototype.toString;
  /**
   * Get the native `typeof` a value.
   *
   * @param  {*} `val`
   * @return {*} Native javascript type
   */

  module.exports = function kindOf(val) {
    // primitivies
    if (typeof val === 'undefined') {
      return 'undefined';
    }

    if (val === null) {
      return 'null';
    }

    if (val === true || val === false || val instanceof Boolean) {
      return 'boolean';
    }

    if (typeof val === 'string' || val instanceof String) {
      return 'string';
    }

    if (typeof val === 'number' || val instanceof Number) {
      return 'number';
    } // functions


    if (typeof val === 'function' || val instanceof Function) {
      return 'function';
    } // array


    if (typeof Array.isArray !== 'undefined' && Array.isArray(val)) {
      return 'array';
    } // check for instances of RegExp and Date before calling `toString`


    if (val instanceof RegExp) {
      return 'regexp';
    }

    if (val instanceof Date) {
      return 'date';
    } // other objects


    var type = toString.call(val);

    if (type === '[object RegExp]') {
      return 'regexp';
    }

    if (type === '[object Date]') {
      return 'date';
    }

    if (type === '[object Arguments]') {
      return 'arguments';
    }

    if (type === '[object Error]') {
      return 'error';
    }

    if (type === '[object Promise]') {
      return 'promise';
    } // buffer


    if (isBuffer(val)) {
      return 'buffer';
    } // es6: Map, WeakMap, Set, WeakSet


    if (type === '[object Set]') {
      return 'set';
    }

    if (type === '[object WeakSet]') {
      return 'weakset';
    }

    if (type === '[object Map]') {
      return 'map';
    }

    if (type === '[object WeakMap]') {
      return 'weakmap';
    }

    if (type === '[object Symbol]') {
      return 'symbol';
    } // typed arrays


    if (type === '[object Int8Array]') {
      return 'int8array';
    }

    if (type === '[object Uint8Array]') {
      return 'uint8array';
    }

    if (type === '[object Uint8ClampedArray]') {
      return 'uint8clampedarray';
    }

    if (type === '[object Int16Array]') {
      return 'int16array';
    }

    if (type === '[object Uint16Array]') {
      return 'uint16array';
    }

    if (type === '[object Int32Array]') {
      return 'int32array';
    }

    if (type === '[object Uint32Array]') {
      return 'uint32array';
    }

    if (type === '[object Float32Array]') {
      return 'float32array';
    }

    if (type === '[object Float64Array]') {
      return 'float64array';
    } // must be a plain object


    return 'object';
  };
  /***/

},
/* 88 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * repeat-string <https://github.com/jonschlinkert/repeat-string>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  /**
   * Results cache
   */

  var res = '';
  var cache;
  /**
   * Expose `repeat`
   */

  module.exports = repeat;
  /**
   * Repeat the given `string` the specified `number`
   * of times.
   *
   * **Example:**
   *
   * ```js
   * var repeat = require('repeat-string');
   * repeat('A', 5);
   * //=> AAAAA
   * ```
   *
   * @param {String} `string` The string to repeat
   * @param {Number} `number` The number of times to repeat the string
   * @return {String} Repeated string
   * @api public
   */

  function repeat(str, num) {
    if (typeof str !== 'string') {
      throw new TypeError('expected a string');
    } // cover common, quick use cases


    if (num === 1) return str;
    if (num === 2) return str + str;
    var max = str.length * num;

    if (cache !== str || typeof cache === 'undefined') {
      cache = str;
      res = '';
    } else if (res.length >= max) {
      return res.substr(0, max);
    }

    while (max > res.length && num > 1) {
      if (num & 1) {
        res += str;
      }

      num >>= 1;
      str += str;
    }

    res += str;
    res = res.substr(0, max);
    return res;
  }
  /***/

},
/* 89 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * preserve <https://github.com/jonschlinkert/preserve>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT license.
   */

  /**
   * Replace tokens in `str` with a temporary, heuristic placeholder.
   *
   * ```js
   * tokens.before('{a\\,b}');
   * //=> '{__ID1__}'
   * ```
   *
   * @param  {String} `str`
   * @return {String} String with placeholders.
   * @api public
   */

  exports.before = function before(str, re) {
    return str.replace(re, function (match) {
      var id = randomize();
      cache[id] = match;
      return '__ID' + id + '__';
    });
  };
  /**
   * Replace placeholders in `str` with original tokens.
   *
   * ```js
   * tokens.after('{__ID1__}');
   * //=> '{a\\,b}'
   * ```
   *
   * @param  {String} `str` String with placeholders
   * @return {String} `str` String with original tokens.
   * @api public
   */


  exports.after = function after(str) {
    return str.replace(/__ID(.{5})__/g, function (_, id) {
      return cache[id];
    });
  };

  function randomize() {
    return Math.random().toString().slice(2, 7);
  }

  var cache = {};
  /***/
},
/* 90 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * expand-brackets <https://github.com/jonschlinkert/expand-brackets>
   *
   * Copyright (c) 2015 Jon Schlinkert.
   * Licensed under the MIT license.
   */

  var isPosixBracket = __webpack_require__(91);
  /**
   * POSIX character classes
   */


  var POSIX = {
    alnum: 'a-zA-Z0-9',
    alpha: 'a-zA-Z',
    blank: ' \\t',
    cntrl: '\\x00-\\x1F\\x7F',
    digit: '0-9',
    graph: '\\x21-\\x7E',
    lower: 'a-z',
    print: '\\x20-\\x7E',
    punct: '-!"#$%&\'()\\*+,./:;<=>?@[\\]^_`{|}~',
    space: ' \\t\\r\\n\\v\\f',
    upper: 'A-Z',
    word: 'A-Za-z0-9_',
    xdigit: 'A-Fa-f0-9'
  };
  /**
   * Expose `brackets`
   */

  module.exports = brackets;

  function brackets(str) {
    if (!isPosixBracket(str)) {
      return str;
    }

    var negated = false;

    if (str.indexOf('[^') !== -1) {
      negated = true;
      str = str.split('[^').join('[');
    }

    if (str.indexOf('[!') !== -1) {
      negated = true;
      str = str.split('[!').join('[');
    }

    var a = str.split('[');
    var b = str.split(']');
    var imbalanced = a.length !== b.length;
    var parts = str.split(/(?::\]\[:|\[?\[:|:\]\]?)/);
    var len = parts.length,
        i = 0;
    var end = '',
        beg = '';
    var res = []; // start at the end (innermost) first

    while (len--) {
      var inner = parts[i++];

      if (inner === '^[!' || inner === '[!') {
        inner = '';
        negated = true;
      }

      var prefix = negated ? '^' : '';
      var ch = POSIX[inner];

      if (ch) {
        res.push('[' + prefix + ch + ']');
      } else if (inner) {
        if (/^\[?\w-\w\]?$/.test(inner)) {
          if (i === parts.length) {
            res.push('[' + prefix + inner);
          } else if (i === 1) {
            res.push(prefix + inner + ']');
          } else {
            res.push(prefix + inner);
          }
        } else {
          if (i === 1) {
            beg += inner;
          } else if (i === parts.length) {
            end += inner;
          } else {
            res.push('[' + prefix + inner + ']');
          }
        }
      }
    }

    var result = res.join('|');
    var rlen = res.length || 1;

    if (rlen > 1) {
      result = '(?:' + result + ')';
      rlen = 1;
    }

    if (beg) {
      rlen++;

      if (beg.charAt(0) === '[') {
        if (imbalanced) {
          beg = '\\[' + beg.slice(1);
        } else {
          beg += ']';
        }
      }

      result = beg + result;
    }

    if (end) {
      rlen++;

      if (end.slice(-1) === ']') {
        if (imbalanced) {
          end = end.slice(0, end.length - 1) + '\\]';
        } else {
          end = '[' + end;
        }
      }

      result += end;
    }

    if (rlen > 1) {
      result = result.split('][').join(']|[');

      if (result.indexOf('|') !== -1 && !/\(\?/.test(result)) {
        result = '(?:' + result + ')';
      }
    }

    result = result.replace(/\[+=|=\]+/g, '\\b');
    return result;
  }

  brackets.makeRe = function (pattern) {
    try {
      return new RegExp(brackets(pattern));
    } catch (err) {}
  };

  brackets.isMatch = function (str, pattern) {
    try {
      return brackets.makeRe(pattern).test(str);
    } catch (err) {
      return false;
    }
  };

  brackets.match = function (arr, pattern) {
    var len = arr.length,
        i = 0;
    var res = arr.slice();
    var re = brackets.makeRe(pattern);

    while (i < len) {
      var ele = arr[i++];

      if (!re.test(ele)) {
        continue;
      }

      res.splice(i, 1);
    }

    return res;
  };
  /***/

},
/* 91 */

/***/
function (module, exports) {
  /*!
   * is-posix-bracket <https://github.com/jonschlinkert/is-posix-bracket>
   *
   * Copyright (c) 2015-2016, Jon Schlinkert.
   * Licensed under the MIT License.
   */
  module.exports = function isPosixBracket(str) {
    return typeof str === 'string' && /\[([:.=+])(?:[^\[\]]|)+\1\]/.test(str);
  };
  /***/

},
/* 92 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * extglob <https://github.com/jonschlinkert/extglob>
   *
   * Copyright (c) 2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  /**
   * Module dependencies
   */

  var isExtglob = __webpack_require__(3);

  var re,
      cache = {};
  /**
   * Expose `extglob`
   */

  module.exports = extglob;
  /**
   * Convert the given extglob `string` to a regex-compatible
   * string.
   *
   * ```js
   * var extglob = require('extglob');
   * extglob('!(a?(b))');
   * //=> '(?!a(?:b)?)[^/]*?'
   * ```
   *
   * @param {String} `str` The string to convert.
   * @param {Object} `options`
   *   @option {Boolean} [options] `esc` If `false` special characters will not be escaped. Defaults to `true`.
   *   @option {Boolean} [options] `regex` If `true` a regular expression is returned instead of a string.
   * @return {String}
   * @api public
   */

  function extglob(str, opts) {
    opts = opts || {};
    var o = {},
        i = 0; // fix common character reversals
    // '*!(.js)' => '*.!(js)'

    str = str.replace(/!\(([^\w*()])/g, '$1!('); // support file extension negation

    str = str.replace(/([*\/])\.!\([*]\)/g, function (m, ch) {
      if (ch === '/') {
        return escape('\\/[^.]+');
      }

      return escape('[^.]+');
    }); // create a unique key for caching by
    // combining the string and options

    var key = str + String(!!opts.regex) + String(!!opts.contains) + String(!!opts.escape);

    if (cache.hasOwnProperty(key)) {
      return cache[key];
    }

    if (!(re instanceof RegExp)) {
      re = regex();
    }

    opts.negate = false;
    var m;

    while (m = re.exec(str)) {
      var prefix = m[1];
      var inner = m[3];

      if (prefix === '!') {
        opts.negate = true;
      }

      var id = '__EXTGLOB_' + i++ + '__'; // use the prefix of the _last_ (outtermost) pattern

      o[id] = wrap(inner, prefix, opts.escape);
      str = str.split(m[0]).join(id);
    }

    var keys = Object.keys(o);
    var len = keys.length; // we have to loop again to allow us to convert
    // patterns in reverse order (starting with the
    // innermost/last pattern first)

    while (len--) {
      var prop = keys[len];
      str = str.split(prop).join(o[prop]);
    }

    var result = opts.regex ? toRegex(str, opts.contains, opts.negate) : str;
    result = result.split('.').join('\\.'); // cache the result and return it

    return cache[key] = result;
  }
  /**
   * Convert `string` to a regex string.
   *
   * @param  {String} `str`
   * @param  {String} `prefix` Character that determines how to wrap the string.
   * @param  {Boolean} `esc` If `false` special characters will not be escaped. Defaults to `true`.
   * @return {String}
   */


  function wrap(inner, prefix, esc) {
    if (esc) inner = escape(inner);

    switch (prefix) {
      case '!':
        return '(?!' + inner + ')[^/]' + (esc ? '%%%~' : '*?');

      case '@':
        return '(?:' + inner + ')';

      case '+':
        return '(?:' + inner + ')+';

      case '*':
        return '(?:' + inner + ')' + (esc ? '%%' : '*');

      case '?':
        return '(?:' + inner + '|)';

      default:
        return inner;
    }
  }

  function escape(str) {
    str = str.split('*').join('[^/]%%%~');
    str = str.split('.').join('\\.');
    return str;
  }
  /**
   * extglob regex.
   */


  function regex() {
    return /(\\?[@?!+*$]\\?)(\(([^()]*?)\))/;
  }
  /**
   * Negation regex
   */


  function negate(str) {
    return '(?!^' + str + ').*$';
  }
  /**
   * Create the regex to do the matching. If
   * the leading character in the `pattern` is `!`
   * a negation regex is returned.
   *
   * @param {String} `pattern`
   * @param {Boolean} `contains` Allow loose matching.
   * @param {Boolean} `isNegated` True if the pattern is a negation pattern.
   */


  function toRegex(pattern, contains, isNegated) {
    var prefix = contains ? '^' : '';
    var after = contains ? '$' : '';
    pattern = '(?:' + pattern + ')' + after;

    if (isNegated) {
      pattern = prefix + negate(pattern);
    }

    return new RegExp(prefix + pattern);
  }
  /***/

},
/* 93 */

/***/
function (module, exports, __webpack_require__) {
  var isBuffer = __webpack_require__(2);

  var toString = Object.prototype.toString;
  /**
   * Get the native `typeof` a value.
   *
   * @param  {*} `val`
   * @return {*} Native javascript type
   */

  module.exports = function kindOf(val) {
    // primitivies
    if (typeof val === 'undefined') {
      return 'undefined';
    }

    if (val === null) {
      return 'null';
    }

    if (val === true || val === false || val instanceof Boolean) {
      return 'boolean';
    }

    if (typeof val === 'string' || val instanceof String) {
      return 'string';
    }

    if (typeof val === 'number' || val instanceof Number) {
      return 'number';
    } // functions


    if (typeof val === 'function' || val instanceof Function) {
      return 'function';
    } // array


    if (typeof Array.isArray !== 'undefined' && Array.isArray(val)) {
      return 'array';
    } // check for instances of RegExp and Date before calling `toString`


    if (val instanceof RegExp) {
      return 'regexp';
    }

    if (val instanceof Date) {
      return 'date';
    } // other objects


    var type = toString.call(val);

    if (type === '[object RegExp]') {
      return 'regexp';
    }

    if (type === '[object Date]') {
      return 'date';
    }

    if (type === '[object Arguments]') {
      return 'arguments';
    }

    if (type === '[object Error]') {
      return 'error';
    } // buffer


    if (isBuffer(val)) {
      return 'buffer';
    } // es6: Map, WeakMap, Set, WeakSet


    if (type === '[object Set]') {
      return 'set';
    }

    if (type === '[object WeakSet]') {
      return 'weakset';
    }

    if (type === '[object Map]') {
      return 'map';
    }

    if (type === '[object WeakMap]') {
      return 'weakmap';
    }

    if (type === '[object Symbol]') {
      return 'symbol';
    } // typed arrays


    if (type === '[object Int8Array]') {
      return 'int8array';
    }

    if (type === '[object Uint8Array]') {
      return 'uint8array';
    }

    if (type === '[object Uint8ClampedArray]') {
      return 'uint8clampedarray';
    }

    if (type === '[object Int16Array]') {
      return 'int16array';
    }

    if (type === '[object Uint16Array]') {
      return 'uint16array';
    }

    if (type === '[object Int32Array]') {
      return 'int32array';
    }

    if (type === '[object Uint32Array]') {
      return 'uint32array';
    }

    if (type === '[object Float32Array]') {
      return 'float32array';
    }

    if (type === '[object Float64Array]') {
      return 'float64array';
    } // must be a plain object


    return 'object';
  };
  /***/

},
/* 94 */

/***/
function (module, exports, __webpack_require__) {
  /*!
   * normalize-path <https://github.com/jonschlinkert/normalize-path>
   *
   * Copyright (c) 2014-2017, Jon Schlinkert.
   * Released under the MIT License.
   */
  var removeTrailingSeparator = __webpack_require__(95);

  module.exports = function normalizePath(str, stripTrailing) {
    if (typeof str !== 'string') {
      throw new TypeError('expected a string');
    }

    str = str.replace(/[\\\/]+/g, '/');

    if (stripTrailing !== false) {
      str = removeTrailingSeparator(str);
    }

    return str;
  };
  /***/

},
/* 95 */

/***/
function (module, exports, __webpack_require__) {
  /* WEBPACK VAR INJECTION */
  (function (process) {
    var isWin = process.platform === 'win32';

    module.exports = function (str) {
      var i = str.length - 1;

      if (i < 2) {
        return str;
      }

      while (isSeparator(str, i)) {
        i--;
      }

      return str.substr(0, i + 1);
    };

    function isSeparator(str, i) {
      var char = str[i];
      return i > 0 && (char === '/' || isWin && char === '\\');
    }
    /* WEBPACK VAR INJECTION */

  }).call(exports, __webpack_require__(6));
  /***/
},
/* 96 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * object.omit <https://github.com/jonschlinkert/object.omit>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var isObject = __webpack_require__(97);

  var forOwn = __webpack_require__(20);

  module.exports = function omit(obj, keys) {
    if (!isObject(obj)) return {};
    keys = [].concat.apply([], [].slice.call(arguments, 1));
    var last = keys[keys.length - 1];
    var res = {},
        fn;

    if (typeof last === 'function') {
      fn = keys.pop();
    }

    var isFunction = typeof fn === 'function';

    if (!keys.length && !isFunction) {
      return obj;
    }

    forOwn(obj, function (value, key) {
      if (keys.indexOf(key) === -1) {
        if (!isFunction) {
          res[key] = value;
        } else if (fn(value, key, obj)) {
          res[key] = value;
        }
      }
    });
    return res;
  };
  /***/

},
/* 97 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * is-extendable <https://github.com/jonschlinkert/is-extendable>
   *
   * Copyright (c) 2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  module.exports = function isExtendable(val) {
    return typeof val !== 'undefined' && val !== null && (_typeof2(val) === 'object' || typeof val === 'function');
  };
  /***/

},
/* 98 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * for-in <https://github.com/jonschlinkert/for-in>
   *
   * Copyright (c) 2014-2017, Jon Schlinkert.
   * Released under the MIT License.
   */

  module.exports = function forIn(obj, fn, thisArg) {
    for (var key in obj) {
      if (fn.call(thisArg, obj[key], key, obj) === false) {
        break;
      }
    }
  };
  /***/

},
/* 99 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * parse-glob <https://github.com/jonschlinkert/parse-glob>
   *
   * Copyright (c) 2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var isGlob = __webpack_require__(4);

  var findBase = __webpack_require__(100);

  var extglob = __webpack_require__(3);

  var dotfile = __webpack_require__(102);
  /**
   * Expose `cache`
   */


  var cache = module.exports.cache = {};
  /**
   * Parse a glob pattern into tokens.
   *
   * When no paths or '**' are in the glob, we use a
   * different strategy for parsing the filename, since
   * file names can contain braces and other difficult
   * patterns. such as:
   *
   *  - `*.{a,b}`
   *  - `(**|*.js)`
   */

  module.exports = function parseGlob(glob) {
    if (cache.hasOwnProperty(glob)) {
      return cache[glob];
    }

    var tok = {};
    tok.orig = glob;
    tok.is = {}; // unescape dots and slashes in braces/brackets

    glob = escape(glob);
    var parsed = findBase(glob);
    tok.is.glob = parsed.isGlob;
    tok.glob = parsed.glob;
    tok.base = parsed.base;
    var segs = /([^\/]*)$/.exec(glob);
    tok.path = {};
    tok.path.dirname = '';
    tok.path.basename = segs[1] || '';
    tok.path.dirname = glob.split(tok.path.basename).join('') || '';
    var basename = (tok.path.basename || '').split('.') || '';
    tok.path.filename = basename[0] || '';
    tok.path.extname = basename.slice(1).join('.') || '';
    tok.path.ext = '';

    if (isGlob(tok.path.dirname) && !tok.path.basename) {
      if (!/\/$/.test(tok.glob)) {
        tok.path.basename = tok.glob;
      }

      tok.path.dirname = tok.base;
    }

    if (glob.indexOf('/') === -1 && !tok.is.globstar) {
      tok.path.dirname = '';
      tok.path.basename = tok.orig;
    }

    var dot = tok.path.basename.indexOf('.');

    if (dot !== -1) {
      tok.path.filename = tok.path.basename.slice(0, dot);
      tok.path.extname = tok.path.basename.slice(dot);
    }

    if (tok.path.extname.charAt(0) === '.') {
      var exts = tok.path.extname.split('.');
      tok.path.ext = exts[exts.length - 1];
    } // unescape dots and slashes in braces/brackets


    tok.glob = unescape(tok.glob);
    tok.path.dirname = unescape(tok.path.dirname);
    tok.path.basename = unescape(tok.path.basename);
    tok.path.filename = unescape(tok.path.filename);
    tok.path.extname = unescape(tok.path.extname); // Booleans

    var is = glob && tok.is.glob;
    tok.is.negated = glob && glob.charAt(0) === '!';
    tok.is.extglob = glob && extglob(glob);
    tok.is.braces = has(is, glob, '{');
    tok.is.brackets = has(is, glob, '[:');
    tok.is.globstar = has(is, glob, '**');
    tok.is.dotfile = dotfile(tok.path.basename) || dotfile(tok.path.filename);
    tok.is.dotdir = dotdir(tok.path.dirname);
    return cache[glob] = tok;
  };
  /**
   * Returns true if the glob matches dot-directories.
   *
   * @param  {Object} `tok` The tokens object
   * @param  {Object} `path` The path object
   * @return {Object}
   */


  function dotdir(base) {
    if (base.indexOf('/.') !== -1) {
      return true;
    }

    if (base.charAt(0) === '.' && base.charAt(1) !== '/') {
      return true;
    }

    return false;
  }
  /**
   * Returns true if the pattern has the given `ch`aracter(s)
   *
   * @param  {Object} `glob` The glob pattern.
   * @param  {Object} `ch` The character to test for
   * @return {Object}
   */


  function has(is, glob, ch) {
    return is && glob.indexOf(ch) !== -1;
  }
  /**
   * Escape/unescape utils
   */


  function escape(str) {
    var re = /\{([^{}]*?)}|\(([^()]*?)\)|\[([^\[\]]*?)\]/g;
    return str.replace(re, function (outter, braces, parens, brackets) {
      var inner = braces || parens || brackets;

      if (!inner) {
        return outter;
      }

      return outter.split(inner).join(esc(inner));
    });
  }

  function esc(str) {
    str = str.split('/').join('__SLASH__');
    str = str.split('.').join('__DOT__');
    return str;
  }

  function unescape(str) {
    str = str.split('__SLASH__').join('/');
    str = str.split('__DOT__').join('.');
    return str;
  }
  /***/

},
/* 100 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * glob-base <https://github.com/jonschlinkert/glob-base>
   *
   * Copyright (c) 2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var path = __webpack_require__(7);

  var parent = __webpack_require__(101);

  var isGlob = __webpack_require__(4);

  module.exports = function globBase(pattern) {
    if (typeof pattern !== 'string') {
      throw new TypeError('glob-base expects a string.');
    }

    var res = {};
    res.base = parent(pattern);
    res.isGlob = isGlob(pattern);

    if (res.base !== '.') {
      res.glob = pattern.substr(res.base.length);

      if (res.glob.charAt(0) === '/') {
        res.glob = res.glob.substr(1);
      }
    } else {
      res.glob = pattern;
    }

    if (!res.isGlob) {
      res.base = dirname(pattern);
      res.glob = res.base !== '.' ? pattern.substr(res.base.length) : pattern;
    }

    if (res.glob.substr(0, 2) === './') {
      res.glob = res.glob.substr(2);
    }

    if (res.glob.charAt(0) === '/') {
      res.glob = res.glob.substr(1);
    }

    return res;
  };

  function dirname(glob) {
    if (glob.slice(-1) === '/') return glob;
    return path.dirname(glob);
  }
  /***/

},
/* 101 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var path = __webpack_require__(7);

  var isglob = __webpack_require__(4);

  module.exports = function globParent(str) {
    str += 'a'; // preserves full path in case of trailing path separator

    do {
      str = path.dirname(str);
    } while (isglob(str));

    return str;
  };
  /***/

},
/* 102 */

/***/
function (module, exports) {
  /*!
   * is-dotfile <https://github.com/jonschlinkert/is-dotfile>
   *
   * Copyright (c) 2015-2017, Jon Schlinkert.
   * Released under the MIT License.
   */
  module.exports = function (str) {
    if (str.charCodeAt(0) === 46
    /* . */
    && str.indexOf('/', 1) === -1) {
      return true;
    }

    var slash = str.lastIndexOf('/');
    return slash !== -1 ? str.charCodeAt(slash + 1) === 46
    /* . */
    : false;
  };
  /***/

},
/* 103 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * regex-cache <https://github.com/jonschlinkert/regex-cache>
   *
   * Copyright (c) 2015-2017, Jon Schlinkert.
   * Released under the MIT License.
   */

  var equal = __webpack_require__(104);

  var basic = {};
  var cache = {};
  /**
   * Expose `regexCache`
   */

  module.exports = regexCache;
  /**
   * Memoize the results of a call to the new RegExp constructor.
   *
   * @param  {Function} fn [description]
   * @param  {String} str [description]
   * @param  {Options} options [description]
   * @param  {Boolean} nocompare [description]
   * @return {RegExp}
   */

  function regexCache(fn, str, opts) {
    var key = '_default_',
        regex,
        cached;

    if (!str && !opts) {
      if (typeof fn !== 'function') {
        return fn;
      }

      return basic[key] || (basic[key] = fn(str));
    }

    var isString = typeof str === 'string';

    if (isString) {
      if (!opts) {
        return basic[str] || (basic[str] = fn(str));
      }

      key = str;
    } else {
      opts = str;
    }

    cached = cache[key];

    if (cached && equal(cached.opts, opts)) {
      return cached.regex;
    }

    memo(key, opts, regex = fn(str, opts));
    return regex;
  }

  function memo(key, opts, regex) {
    cache[key] = {
      regex: regex,
      opts: opts
    };
  }
  /**
   * Expose `cache`
   */


  module.exports.cache = cache;
  module.exports.basic = basic;
  /***/
},
/* 104 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * is-equal-shallow <https://github.com/jonschlinkert/is-equal-shallow>
   *
   * Copyright (c) 2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  var isPrimitive = __webpack_require__(105);

  module.exports = function isEqual(a, b) {
    if (!a && !b) {
      return true;
    }

    if (!a && b || a && !b) {
      return false;
    }

    var numKeysA = 0,
        numKeysB = 0,
        key;

    for (key in b) {
      numKeysB++;

      if (!isPrimitive(b[key]) || !a.hasOwnProperty(key) || a[key] !== b[key]) {
        return false;
      }
    }

    for (key in a) {
      numKeysA++;
    }

    return numKeysA === numKeysB;
  };
  /***/

},
/* 105 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * is-primitive <https://github.com/jonschlinkert/is-primitive>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */
  // see http://jsperf.com/testing-value-is-primitive/7

  module.exports = function isPrimitive(value) {
    return value == null || typeof value !== 'function' && _typeof2(value) !== 'object';
  };
  /***/

},
/* 106 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var chars = __webpack_require__(107);

  var utils = __webpack_require__(5);
  /**
   * Expose `Glob`
   */


  var Glob = module.exports = function Glob(pattern, options) {
    if (!(this instanceof Glob)) {
      return new Glob(pattern, options);
    }

    this.options = options || {};
    this.pattern = pattern;
    this.history = [];
    this.tokens = {};
    this.init(pattern);
  };
  /**
   * Initialize defaults
   */


  Glob.prototype.init = function (pattern) {
    this.orig = pattern;
    this.negated = this.isNegated();
    this.options.track = this.options.track || false;
    this.options.makeRe = true;
  };
  /**
   * Push a change into `glob.history`. Useful
   * for debugging.
   */


  Glob.prototype.track = function (msg) {
    if (this.options.track) {
      this.history.push({
        msg: msg,
        pattern: this.pattern
      });
    }
  };
  /**
   * Return true if `glob.pattern` was negated
   * with `!`, also remove the `!` from the pattern.
   *
   * @return {Boolean}
   */


  Glob.prototype.isNegated = function () {
    if (this.pattern.charCodeAt(0) === 33
    /* '!' */
    ) {
        this.pattern = this.pattern.slice(1);
        return true;
      }

    return false;
  };
  /**
   * Expand braces in the given glob pattern.
   *
   * We only need to use the [braces] lib when
   * patterns are nested.
   */


  Glob.prototype.braces = function () {
    if (this.options.nobraces !== true && this.options.nobrace !== true) {
      // naive/fast check for imbalanced characters
      var a = this.pattern.match(/[\{\(\[]/g);
      var b = this.pattern.match(/[\}\)\]]/g); // if imbalanced, don't optimize the pattern

      if (a && b && a.length !== b.length) {
        this.options.makeRe = false;
      } // expand brace patterns and join the resulting array


      var expanded = utils.braces(this.pattern, this.options);
      this.pattern = expanded.join('|');
    }
  };
  /**
   * Expand bracket expressions in `glob.pattern`
   */


  Glob.prototype.brackets = function () {
    if (this.options.nobrackets !== true) {
      this.pattern = utils.brackets(this.pattern);
    }
  };
  /**
   * Expand bracket expressions in `glob.pattern`
   */


  Glob.prototype.extglob = function () {
    if (this.options.noextglob === true) return;

    if (utils.isExtglob(this.pattern)) {
      this.pattern = utils.extglob(this.pattern, {
        escape: true
      });
    }
  };
  /**
   * Parse the given pattern
   */


  Glob.prototype.parse = function (pattern) {
    this.tokens = utils.parseGlob(pattern || this.pattern, true);
    return this.tokens;
  };
  /**
   * Replace `a` with `b`. Also tracks the change before and
   * after each replacement. This is disabled by default, but
   * can be enabled by setting `options.track` to true.
   *
   * Also, when the pattern is a string, `.split()` is used,
   * because it's much faster than replace.
   *
   * @param  {RegExp|String} `a`
   * @param  {String} `b`
   * @param  {Boolean} `escape` When `true`, escapes `*` and `?` in the replacement.
   * @return {String}
   */


  Glob.prototype._replace = function (a, b, escape) {
    this.track('before (find): "' + a + '" (replace with): "' + b + '"');
    if (escape) b = esc(b);

    if (a && b && typeof a === 'string') {
      this.pattern = this.pattern.split(a).join(b);
    } else {
      this.pattern = this.pattern.replace(a, b);
    }

    this.track('after');
  };
  /**
   * Escape special characters in the given string.
   *
   * @param  {String} `str` Glob pattern
   * @return {String}
   */


  Glob.prototype.escape = function (str) {
    this.track('before escape: ');
    var re = /["\\](['"]?[^"'\\]['"]?)/g;
    this.pattern = str.replace(re, function ($0, $1) {
      var o = chars.ESC;
      var ch = o && o[$1];

      if (ch) {
        return ch;
      }

      if (/[a-z]/i.test($0)) {
        return $0.split('\\').join('');
      }

      return $0;
    });
    this.track('after escape: ');
  };
  /**
   * Unescape special characters in the given string.
   *
   * @param  {String} `str`
   * @return {String}
   */


  Glob.prototype.unescape = function (str) {
    var re = /__([A-Z]+)_([A-Z]+)__/g;
    this.pattern = str.replace(re, function ($0, $1) {
      return chars[$1][$0];
    });
    this.pattern = unesc(this.pattern);
  };
  /**
   * Escape/unescape utils
   */


  function esc(str) {
    str = str.split('?').join('%~');
    str = str.split('*').join('%%');
    return str;
  }

  function unesc(str) {
    str = str.split('%~').join('?');
    str = str.split('%%').join('*');
    return str;
  }
  /***/

},
/* 107 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var chars = {},
      unesc,
      temp;

  function reverse(object, prepender) {
    return Object.keys(object).reduce(function (reversed, key) {
      var newKey = prepender ? prepender + key : key; // Optionally prepend a string to key.

      reversed[object[key]] = newKey; // Swap key and value.

      return reversed; // Return the result.
    }, {});
  }
  /**
   * Regex for common characters
   */


  chars.escapeRegex = {
    '?': /\?/g,
    '@': /\@/g,
    '!': /\!/g,
    '+': /\+/g,
    '*': /\*/g,
    '(': /\(/g,
    ')': /\)/g,
    '[': /\[/g,
    ']': /\]/g
  };
  /**
   * Escape characters
   */

  chars.ESC = {
    '?': '__UNESC_QMRK__',
    '@': '__UNESC_AMPE__',
    '!': '__UNESC_EXCL__',
    '+': '__UNESC_PLUS__',
    '*': '__UNESC_STAR__',
    ',': '__UNESC_COMMA__',
    '(': '__UNESC_LTPAREN__',
    ')': '__UNESC_RTPAREN__',
    '[': '__UNESC_LTBRACK__',
    ']': '__UNESC_RTBRACK__'
  };
  /**
   * Unescape characters
   */

  chars.UNESC = unesc || (unesc = reverse(chars.ESC, '\\'));
  chars.ESC_TEMP = {
    '?': '__TEMP_QMRK__',
    '@': '__TEMP_AMPE__',
    '!': '__TEMP_EXCL__',
    '*': '__TEMP_STAR__',
    '+': '__TEMP_PLUS__',
    ',': '__TEMP_COMMA__',
    '(': '__TEMP_LTPAREN__',
    ')': '__TEMP_RTPAREN__',
    '[': '__TEMP_LTBRACK__',
    ']': '__TEMP_RTBRACK__'
  };
  chars.TEMP = temp || (temp = reverse(chars.ESC_TEMP));
  module.exports = chars;
  /***/
},
/* 108 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * array-every <https://github.com/jonschlinkert/array-every>
   *
   * Copyright (c) 2014 Jon Schlinkert, contributors.
   * Licensed under the MIT license.
   */

  var iterator = __webpack_require__(109);

  module.exports = function every(arr, cb, thisArg) {
    cb = iterator(cb, thisArg);
    var res = true;
    if (arr == null) return res;
    var len = arr.length;
    var i = 0;

    while (len--) {
      if (!cb(arr[i++], i, arr)) {
        res = false;
        break;
      }
    }

    return res;
  };
  /***/

},
/* 109 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * make-iterator <https://github.com/jonschlinkert/make-iterator>
   *
   * Copyright (c) 2014 Jon Schlinkert, contributors.
   * Copyright (c) 2012, 2013 moutjs team and contributors (http://moutjs.com)
   * Licensed under the MIT License
   */

  var forOwn = __webpack_require__(20);
  /**
   * Convert an argument into a valid iterator.
   * Used internally on most array/object/collection methods that receives a
   * callback/iterator providing a shortcut syntax.
   */


  module.exports = function makeIterator(src, thisArg) {
    if (src == null) {
      return noop;
    }

    switch (_typeof2(src)) {
      // function is the first to improve perf (most common case)
      // also avoid using `Function#call` if not needed, which boosts
      // perf a lot in some cases
      case 'function':
        return typeof thisArg !== 'undefined' ? function (val, i, arr) {
          return src.call(thisArg, val, i, arr);
        } : src;

      case 'object':
        return function (val) {
          return deepMatches(val, src);
        };

      case 'string':
      case 'number':
        return prop(src);
    }
  };

  function containsMatch(array, value) {
    var len = array.length;
    var i = -1;

    while (++i < len) {
      if (deepMatches(array[i], value)) {
        return true;
      }
    }

    return false;
  }

  function matchArray(o, value) {
    var len = value.length;
    var i = -1;

    while (++i < len) {
      if (!containsMatch(o, value[i])) {
        return false;
      }
    }

    return true;
  }

  function matchObject(o, value) {
    var res = true;
    forOwn(value, function (val, key) {
      if (!deepMatches(o[key], val)) {
        // Return false to break out of forOwn early
        return res = false;
      }
    });
    return res;
  }
  /**
   * Recursively compare objects
   */


  function deepMatches(o, value) {
    if (o && _typeof2(o) === 'object') {
      if (Array.isArray(o) && Array.isArray(value)) {
        return matchArray(o, value);
      } else {
        return matchObject(o, value);
      }
    } else {
      return o === value;
    }
  }

  function prop(name) {
    return function (obj) {
      return obj[name];
    };
  }

  function noop(val) {
    return val;
  }
  /***/

},
/* 110 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * array-slice <https://github.com/jonschlinkert/array-slice>
   *
   * Copyright (c) 2014-2015, Jon Schlinkert.
   * Licensed under the MIT License.
   */

  module.exports = function slice(arr, start, end) {
    var len = arr.length >>> 0;
    var range = [];
    start = idx(arr, start);
    end = idx(arr, end, len);

    while (start < end) {
      range.push(arr[start++]);
    }

    return range;
  };

  function idx(arr, pos, end) {
    var len = arr.length >>> 0;

    if (pos == null) {
      pos = end || 0;
    } else if (pos < 0) {
      pos = Math.max(len + pos, 0);
    } else {
      pos = Math.min(pos, len);
    }

    return pos;
  }
  /***/

},
/* 111 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";
  /*!
   * index-of <https://github.com/jonschlinkert/index-of>
   *
   * Copyright (c) 2014-2015 Jon Schlinkert.
   * Licensed under the MIT license.
   */

  module.exports = function indexOf(arr, ele, start) {
    start = start || 0;
    var idx = -1;
    if (arr == null) return idx;
    var len = arr.length;
    var i = start < 0 ? len + start : start;

    while (len--) {
      if (arr[i++] === ele) {
        idx = i - 1;
        break;
      }
    }

    return idx;
  };
  /***/

},
/* 112 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function (data) {
    var _this = this;

    try {
      return data.map(function (row) {
        for (var column in row) {
          if (_this.source === 'client') row[column] = _this.formatDate(row[column], _this.dateFormat(column));

          if (_this.isListFilter(column) && !_this.opts.templates[column] && !_this.$scopedSlots[column]) {
            row[column] = _this.optionText(row[column], column);
          }
        }

        return row;
      });
    } catch (e) {
      console.error('vue-tables-2: non-iterable data property. Expected array, got ' + (typeof data === 'undefined' ? 'undefined' : _typeof(data)) + '. Make sure that your response conforms to the expected format, or use the \'responseAdapter\' option to match the currently returned format');
      console.error('Data equals', data);
      throw new Error();
    }
  };
  /***/

},
/* 113 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (value, column) {
    var list = this.listColumnsObject[column];
    if (typeof list[value] == 'undefined') return value;
    return list[value];
  };
  /***/

},
/* 114 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (row, column, index, h) {
    var value = this._getValue(row, column);

    if (this.templatesKeys.indexOf(column) == -1) {
      if (typeof value === 'undefined' || !this.opts.highlightMatches || this.filterableColumns.indexOf(column) === -1) {
        return value;
      }

      return this.highlightMatch(value, column, h);
    }

    var template = this.opts.templates[column];
    template = typeof template == 'function' ? template.apply(this.$root, [h, row, index, column]) : h(template, {
      attrs: {
        data: row,
        column: column,
        index: index
      }
    });
    return template;
  };
  /***/

},
/* 115 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (row, event) {
    var data;
    var id = this.opts.uniqueKey;

    if (this.source == 'client' && typeof row[id] !== 'undefined') {
      data = this.tableData.filter(function (r) {
        return row[id] === r[id];
      })[0];
    } else {
      data = row;
    }

    this.dispatch('row-click', {
      row: data,
      event: event
    });
  };
  /***/

},
/* 116 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function (e) {
    this.limit = (typeof e === 'undefined' ? 'undefined' : _typeof(e)) === 'object' ? e.target.value : e;
    this.updateState('perPage', this.limit);
    this.dispatch('limit', parseInt(this.limit));
    this.setPage(1);
  };
  /***/

},
/* 117 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var _this = this;

    var rows = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

    if (!this.opts.childRow || typeof this.opts.childRow === 'function') {
      throw new Error('vue-tables-2: Child row undefined or not a component');
    }

    var Rows = rows ? this.openChildRows.filter(function (row) {
      return rows.includes(row);
    }) : this.openChildRows;
    if (!Rows.length) return [];
    var components = this.$children.filter(function (child) {
      return child.$options.name === 'ChildRow' && Rows.includes(child.data[_this.opts.uniqueKey]);
    });
    return components;
  };
  /***/

},
/* 118 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _bus = __webpack_require__(1);

  var _bus2 = _interopRequireDefault(_bus);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function (event, payload) {
    if (this.vuex) {
      if (event.split('::').length > 1) return;
      this.commit(event.toUpperCase().replace('-', '_'), payload);
    }

    this.$emit(event, payload);

    _bus2.default.$emit('vue-tables.' + event, payload);

    if (this.name) {
      _bus2.default.$emit('vue-tables.' + this.name + '.' + event, payload);
    }
  };
  /***/

},
/* 119 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (rowId, e) {
    if (e) e.stopPropagation();

    if (this.openChildRows.includes(rowId)) {
      var index = this.openChildRows.indexOf(rowId);
      this.openChildRows.splice(index, 1);
    } else {
      this.openChildRows.push(rowId);
    }
  };
  /***/

},
/* 120 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (rowId) {
    return this.openChildRows.includes(rowId) ? 'VueTables__child-row-toggler--open' : 'VueTables__child-row-toggler--closed';
  };
  /***/

},
/* 121 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (data) {
    if (typeof this.opts.requestFunction === 'function') {
      return this.opts.requestFunction.call(this, data);
    }

    if (typeof axios !== 'undefined') return axios.get(this.url, {
      params: data
    }).catch(function (e) {
      this.dispatch('error', e);
    }.bind(this));
    if (typeof this.$http !== 'undefined') return this.$http.get(this.url, {
      params: data
    }).then(function (data) {
      return data.json();
    }.bind(this), function (e) {
      this.dispatch('error', e);
    }.bind(this));
    if (typeof $ != 'undefined') return $.getJSON(this.url, data).fail(function (e) {
      this.dispatch('error', e);
    }.bind(this));
    throw "vue-tables: No supported ajax library was found. (jQuery, axios or vue-resource). To use a different library you can write your own request function (see the `requestFunction` option)";
  };
  /***/

},
/* 122 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (response) {
    if (typeof axios !== 'undefined') return response.data;
    return response;
  };
  /***/

},
/* 123 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var ascending = this.orderBy.ascending;
    this.currentlySorting = {
      column: column,
      ascending: ascending
    };
    if (typeof this.opts.customSorting[column] == 'undefined') return this.defaultSort(column, ascending);
    return this.opts.customSorting[column](ascending);
  };
  /***/

},
/* 124 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var state = {
      page: 1,
      query: this.query,
      orderBy: this.orderBy,
      perPage: this.opts.perPage,
      customQueries: this.customQueries
    };
    this.storage.setItem(this.stateKey, JSON.stringify(state));
    return state;
  };
  /***/

},
/* 125 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (key, value) {
    if (!this.opts.saveState || !this.activeState) return;

    try {
      var currentState = JSON.parse(this.storage.getItem(this.stateKey));
    } catch (e) {
      var currentState = this.initState();
    }

    currentState[key] = value;
    this.storage.setItem(this.stateKey, JSON.stringify(currentState));
  };
  /***/

},
/* 126 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var c = this.opts.columnsClasses;
    return c.hasOwnProperty(column) ? c[column] : '';
  };
  /***/

},
/* 127 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (name) {
    if (!name) return name;
    name = name.split('__');
    name.shift();
    return name.join('__').split('@@@').join('.');
  };
  /***/

},
/* 128 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var _this = this;

    if (!this.userControlsColumns) {
      this.userColumnsDisplay = JSON.parse(JSON.stringify(this.allColumns));
      this.userControlsColumns = true;
    }

    if (this.userColumnsDisplay.includes(column)) {
      // can't have no columns
      if (this.userColumnsDisplay.length === 1) return;
      var index = this.userColumnsDisplay.indexOf(column);
      this.userColumnsDisplay.splice(index, 1);
    } else {
      this.userColumnsDisplay.push(column);
    }

    this.updateState('userControlsColumns', true);
    this.updateState('userColumnsDisplay', this.userColumnsDisplay);
    this.$nextTick(function () {
      _this._setFiltersDOM(_this.query);
    });
  };
  /***/

},
/* 129 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (secondaryCol) {
    var primaryCol = this.orderBy.column;
    var primaryAsc = this.orderBy.ascending;
    if (!this.userMultiSorting[primaryCol]) this.userMultiSorting[primaryCol] = [];
    var multi = this.userMultiSorting[primaryCol];

    if (primaryCol === secondaryCol) {
      if (!multi.length || primaryAsc) {
        // primary is the only sorted column or is ascending
        this.orderBy.ascending = !this.orderBy.ascending;
      } else {
        // remove primary column and make secondary primary
        this.orderBy = multi.shift();
        this.userMultiSorting = {};
        this.userMultiSorting[this.orderBy.column] = multi;
      }
    } else {
      var secondary = multi.filter(function (col) {
        return col.column == secondaryCol;
      })[0];

      if (secondary) {
        if (!secondary.ascending) {
          // remove sort
          this.userMultiSorting[primaryCol] = multi.filter(function (col) {
            return col.column != secondaryCol;
          });
          if (!this.userMultiSorting[primaryCol].length) this.userMultiSorting = {};
        } else {
          // change direction
          secondary.ascending = !secondary.ascending;
        }
      } else {
        // add sort
        multi.push({
          column: secondaryCol,
          ascending: true
        });
      }
    } // force re-compilation of the filteredData computed property


    this.time = Date.now();
    this.dispatch('sorted', getMultiSortData(this.orderBy, this.userMultiSorting));
  };

  function getMultiSortData(main, secondary) {
    var cols = [JSON.parse(JSON.stringify(main))];
    cols = cols.concat(secondary[main.column]);
    return cols;
  }

  ;
  /***/
},
/* 130 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function (query) {
    var el;

    if (this.opts.filterByColumn) {
      for (var column in query) {
        var columnName = this._getColumnName(column);

        if (this.isDateFilter(column)) {
          if (query[column] && _typeof(query[column]) === 'object') {
            var start = typeof query[column].start === 'string' ? moment(query[column].start, 'YYYY-MM-DD') : query[column].start;
            var end = typeof query[column].end === 'string' ? moment(query[column].end, 'YYYY-MM-DD') : query[column].end;

            this._setDatepickerText(column, start, end);
          } else {
            $(this.$el).find('#VueTables__' + column + '-filter').html("<span class='VueTables__filter-placeholder'>" + this.display('filterBy', {
              column: this.getHeading(column)
            }) + "</span>");
          }

          continue;
        }

        el = this.$el.querySelector('[name=\'' + columnName + '\']');

        if (el) {
          el.value = query[column];
        } else if (this.columns.indexOf(column) === -1) {
          console.error('vue-tables-2: Error in setting filter value. Column \'' + column + '\' does not exist.');
        }
      }
    } else {
      this.$el.querySelector('.VueTables__search input').value = query;
    }
  };
  /***/

},
/* 131 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    var userMultiSort = Object.keys(this.userMultiSorting);
    if (!userMultiSort.length || this.orderBy.column === column) return this.orderBy.column === column;
    return !!this.userMultiSorting[userMultiSort[0]].filter(function (col) {
      return col.column == column;
    }).length;
  };
  /***/

},
/* 132 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h, row) {
    // scoped slot
    if (this.$scopedSlots.child_row) return this.$scopedSlots.child_row({
      row: row
    });
    var childRow = this.opts.childRow; // function

    if (typeof childRow === 'function') return childRow.apply(this, [h, row]); // component

    return h(childRow, {
      attrs: {
        data: row
      }
    });
  };
  /***/

},
/* 133 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    this.displayColumnsDropdown = !this.displayColumnsDropdown;
  };
  /***/

},
/* 134 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    return this.userColumnsDisplay.length === 1 && this.userColumnsDisplay[0] === column;
  };
  /***/

},
/* 135 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (page) {
    if (this.vuex) return;
    this.setPage(page);
    this.dispatch('pagination', page);
  };
  /***/

},
/* 136 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    if (this.orderBy.column != this.opts.groupBy) {
      this.setOrder(this.opts.groupBy, true);
    } else {
      this.setOrder(this.opts.groupBy, !this.orderBy.ascending);
    }
  };
  /***/

},
/* 137 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    if (typeof this.opts.initFilters[column] !== 'undefined') {
      return this.opts.initFilters[column];
    }

    if (typeof this.query[column] !== 'undefined' && this.query[column].start) {
      return {
        start: moment(this.query[column].start, 'YYYY-MM-DD HH:mm:ss'),
        end: moment(this.query[column].end, 'YYYY-MM-DD HH:mm:ss')
      };
    }

    return false;
  };
  /***/

},
/* 138 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column, start, end) {
    var dateFormat = this.dateFormat(column);
    var el = typeof column === 'string' ? $(this.$el).find("#VueTables__" + column + "-filter") : column;
    el.text(start.format(dateFormat) + " - " + end.format(dateFormat));
  };
  /***/

},
/* 139 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    return !this.opts.descOrderColumns.includes(column);
  };
  /***/

},
/* 140 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    if (this.opts.dateFormatPerColumn.hasOwnProperty(column)) {
      return this.opts.dateFormatPerColumn[column];
    }

    return this.opts.dateFormat;
  };
  /***/

},
/* 141 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var _this = this;

    if (this.opts.columnsDropdown) {
      var stopProp = function stopProp(e) {
        return e.stopPropagation();
      };

      var handler = function handler() {
        if (_this.displayColumnsDropdown) {
          _this.displayColumnsDropdown = false;
        }
      };

      this.$refs.columnsdropdown.addEventListener('click', stopProp);
      document.addEventListener('click', handler);
      this.$once('hook:beforeDestroy', function () {
        document.removeEventListener('click', handler);

        _this.$refs.columnsdropdown.removeEventListener('click', stopProp);
      });
    }
  };
  /***/

},
/* 142 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (row, column) {
    if (column.indexOf('.') === -1) return row[column];
    var p = column.split('.');
    var value = row[p[0]];
    if (!value) return '';

    for (var i = 1; i < p.length; i++) {
      value = value[p[i]]; // If the nested structure doesn't exist return an empty string

      if (typeof value === 'undefined') return '';
    }

    return value;
  };
  /***/

},
/* 143 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column) {
    return 'vf__' + column.split('.').join('@@@');
  };
  /***/

},
/* 144 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    listColumnsObject: __webpack_require__(145),
    allColumns: __webpack_require__(146),
    templatesKeys: __webpack_require__(147),
    opts: __webpack_require__(148),
    tableData: __webpack_require__(150),
    storage: __webpack_require__(151),
    filterableColumns: __webpack_require__(152),
    hasChildRow: __webpack_require__(153),
    colspan: __webpack_require__(154),
    hasGenericFilter: __webpack_require__(155),
    stateKey: function stateKey() {
      var key = this.name ? this.name : this.id;
      return 'vuetables_' + key;
    },
    Page: function Page() {
      return this.page;
    }
  };
  /***/
},
/* 145 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var columns = Object.keys(this.opts.listColumns);
    var res = {};
    columns.forEach(function (column) {
      res[column] = {};
      this.opts.listColumns[column].forEach(function (item) {
        res[column][item.id] = item.text;
      });
    }.bind(this));
    return res;
  };
  /***/

},
/* 146 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var _this = this;

    var display = this.columnsDisplay; // default - return all columns

    if (!display && !this.userControlsColumns) {
      return this.Columns;
    } // user toggled columns - return user selected columns


    if (this.userControlsColumns) {
      return this.columns.filter(function (column) {
        return _this.userColumnsDisplay.includes(column);
      });
    }

    if (this.opts.ssr) return this.Columns; // developer defined columns display

    return this.Columns.filter(function (column) {
      if (!display[column]) return true;
      var range = display[column];
      var operator = range[2];
      var inRange = (!range[0] || _this.windowWidth >= range[0]) && (!range[1] || _this.windowWidth < range[1]);
      return operator == 'not' ? !inRange : inRange;
    });
  };
  /***/

},
/* 147 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return Object.keys(this.opts.templates);
  };
  /***/

},
/* 148 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    var defaults = __webpack_require__(149)();

    return this.initOptions(defaults, this.globalOptions, this.options);
  };
  /***/

},
/* 149 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return {
      dateColumns: [],
      listColumns: {},
      datepickerOptions: {
        locale: {
          cancelLabel: 'Clear'
        }
      },
      datepickerPerColumnOptions: {},
      initialPage: 1,
      perPage: 10,
      perPageValues: [10, 25, 50, 100],
      groupBy: false,
      collapseGroups: false,
      destroyEventBus: false,
      sendEmptyFilters: false,
      params: {},
      sortable: true,
      filterable: true,
      groupMeta: [],
      initFilters: {},
      customFilters: [],
      templates: {},
      debounce: 250,
      dateFormat: "DD/MM/YYYY",
      dateFormatPerColumn: {},
      toMomentFormat: false,
      skin: false,
      columnsDisplay: {},
      columnsDropdown: false,
      texts: {
        count: "Showing {from} to {to} of {count} records|{count} records|One record",
        first: 'First',
        last: 'Last',
        filter: "Filter:",
        filterPlaceholder: "Search query",
        limit: "Records:",
        page: "Page:",
        noResults: "No matching records",
        filterBy: "Filter by {column}",
        loading: 'Loading...',
        defaultOption: 'Select {column}',
        columns: 'Columns'
      },
      sortIcon: {
        is: 'glyphicon-sort',
        base: 'glyphicon',
        up: 'glyphicon-chevron-up',
        down: 'glyphicon-chevron-down'
      },
      sortingAlgorithm: function sortingAlgorithm(data, column) {
        return data.sort(this.getSortFn(column));
      },
      customSorting: {},
      multiSorting: {},
      clientMultiSorting: true,
      serverMultiSorting: false,
      filterByColumn: false,
      highlightMatches: false,
      orderBy: false,
      descOrderColumns: [],
      footerHeadings: false,
      headings: {},
      headingsTooltips: {},
      pagination: {
        dropdown: false,
        chunk: 10,
        edge: false,
        align: 'center',
        nav: 'fixed'
      },
      childRow: false,
      childRowTogglerFirst: true,
      uniqueKey: 'id',
      requestFunction: false,
      requestAdapter: function requestAdapter(data) {
        return data;
      },
      responseAdapter: function responseAdapter(resp) {
        var data = this.getResponseData(resp);
        return {
          data: data.data,
          count: data.count
        };
      },
      requestKeys: {
        query: 'query',
        limit: 'limit',
        orderBy: 'orderBy',
        ascending: 'ascending',
        page: 'page',
        byColumn: 'byColumn'
      },
      rowClassCallback: false,
      preserveState: false,
      saveState: false,
      storage: 'local',
      columnsClasses: {}
    };
  };
  /***/

},
/* 150 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return this.data;
  };
  /***/

},
/* 151 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    if (typeof localStorage === 'undefined') return {};
    return this.opts.storage === 'local' ? localStorage : sessionStorage;
  };
  /***/

},
/* 152 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return this.opts.filterable && this.opts.filterable.length ? this.opts.filterable : this.Columns;
  };
  /***/

},
/* 153 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return this.opts.childRow || this.$scopedSlots.child_row;
  };
  /***/

},
/* 154 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return this.hasChildRow ? this.allColumns.length + 1 : this.allColumns.length;
  };
  /***/

},
/* 155 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function () {
    return !this.opts.filterByColumn && (typeof this.opts.filterable === 'boolean' && this.opts.filterable || _typeof(this.opts.filterable) === 'object' && this.opts.filterable.length);
  };
  /***/

},
/* 156 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    input: __webpack_require__(157),
    select: __webpack_require__(158)
  };
  /***/
},
/* 157 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    twoWay: true,
    bind: function bind(el, binding, vnode) {
      el.addEventListener('keydown', function (e) {
        vnode.context[binding.value] = e.target.value;
      });
    },
    update: function update(el, binding, vnode, oldVnode) {}
  };
  /***/
},
/* 158 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = {
    twoWay: true,
    bind: function bind(el, binding, vnode) {
      el.addEventListener('change', function (e) {
        console.log("SELECT CHANGE");
        vnode.context[binding.value.name] = e.target.value;
        binding.value.cb.call(this, binding.value.params);
      });
    },
    update: function update(el, binding, vnode, oldVnode) {// el.value = vnode.context[binding.value];
      // console.log(binding.value + " was updated");
      //  vnode.context[binding.value] = el.value;
    }
  };
  /***/
},
/* 159 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _bus = __webpack_require__(1);

  var _bus2 = _interopRequireDefault(_bus);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function () {
    var _this = this;

    var el;

    if (this.opts.destroyEventBus) {
      _bus2.default.$off();

      _bus2.default.$destroy();
    }

    if (this.vuex && !this.opts.preserveState) {
      this.$store.unregisterModule(this.name);
    }

    if (this.opts.filterByColumn) {
      this.opts.dateColumns.forEach(function (column) {
        el = $(_this.$el).find("#VueTables__" + column + "-filter").data('daterangepicker');
        if (el) el.remove();
      });
    }
  };
  /***/

},
/* 160 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (obj) {
    // null and undefined are "empty"
    if (obj == null) return true; // Assume if it has a length property with a non-zero value
    // that that property is correct.

    if (obj.length > 0) return false;
    if (obj.length === 0) return true; // Otherwise, does it have any properties of its own?

    for (var key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) return false;
    }

    return true;
  };
  /***/

},
/* 161 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _state = __webpack_require__(162);

  var _state2 = _interopRequireDefault(_state);

  var _mutations = __webpack_require__(163);

  var _mutations2 = _interopRequireDefault(_mutations);

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function (self) {
    var Module = {
      state: (0, _state2.default)(self),
      mutations: (0, _mutations2.default)(self)
    };

    if (self.$store && self.$store.state && self.$store.state[self.name]) {
      Module.state = _merge2.default.recursive(Module.state, self.$store.state[self.name]);
      self.$store.unregisterModule(self.name);
    }

    self.$store.registerModule(self.name, Module);
  };
  /***/

},
/* 162 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function (self) {
    var state = {
      page: self.opts.initialPage ? self.opts.initialPage : 1,
      limit: self.opts.perPage,
      count: self.source == 'server' ? 0 : self.data.length,
      columns: self.columns,
      data: self.source == 'client' ? self.data : [],
      query: self.initQuery(),
      customQueries: self.initCustomFilters(),
      sortBy: self.opts.orderBy && self.opts.orderBy.column ? self.opts.orderBy.column : false,
      ascending: self.opts.orderBy && self.opts.orderBy.hasOwnProperty('ascending') ? self.opts.orderBy.ascending : true
    };

    if (typeof self.$store.state[self.name] !== 'undefined') {
      return (0, _merge2.default)(true, self.$store.state[self.name], state);
    }

    return state;
  };

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }
  /***/

},
/* 163 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  Object.defineProperty(exports, "__esModule", {
    value: true
  });

  exports.default = function (self) {
    var _ref, _merge$recursive;

    var extra = self.source == 'server' ? (_ref = {}, _defineProperty(_ref, self.name + '/SET_DATA', function undefined(state, response) {
      var data = self.opts.responseAdapter.call(self, response);
      state.data = data.data;
      state.count = parseInt(data.count);
    }), _defineProperty(_ref, self.name + '/LOADING', function undefined(state, payload) {}), _defineProperty(_ref, self.name + '/LOADED', function undefined(state, payload) {}), _defineProperty(_ref, self.name + '/ERROR', function undefined(state, payload) {}), _ref) : _defineProperty({}, self.name + '/SET_COUNT', function undefined(state, count) {
      state.count = count;
    });
    return _merge2.default.recursive(true, (_merge$recursive = {}, _defineProperty(_merge$recursive, self.name + '/PAGINATE', function undefined(state, page) {
      state.page = page;
      self.updateState('page', page);
      if (self.source == 'server') self.getData();
      self.commit('PAGINATION', page);
    }), _defineProperty(_merge$recursive, self.name + '/SET_FILTER', function undefined(state, filter) {
      state.page = 1;
      self.updateState('page', 1);
      state.query = filter;

      if (self.source == 'server') {
        self.getData();
      }
    }), _defineProperty(_merge$recursive, self.name + '/PAGINATION', function undefined(state, page) {}), _defineProperty(_merge$recursive, self.name + '/SET_CUSTOM_FILTER', function undefined(state, _ref3) {
      var filter = _ref3.filter,
          value = _ref3.value;
      state.customQueries[filter] = value;
      state.page = 1;
      self.updateState('page', 1);
      self.updateState('customQueries', state.customQueries);

      if (self.source == 'server') {
        self.getData();
      }
    }), _defineProperty(_merge$recursive, self.name + '/SET_STATE', function undefined(state, _ref4) {
      var page = _ref4.page,
          query = _ref4.query,
          customQueries = _ref4.customQueries,
          limit = _ref4.limit,
          orderBy = _ref4.orderBy;
      state.customQueries = customQueries;
      state.query = query;
      state.page = page;
      state.limit = limit;
      state.ascending = orderBy.ascending;
      state.sortBy = orderBy.column;
    }), _defineProperty(_merge$recursive, self.name + '/SET_LIMIT', function undefined(state, limit) {
      state.page = 1;
      self.updateState('page', 1);
      state.limit = limit;
      if (self.source == 'server') self.getData();
    }), _defineProperty(_merge$recursive, self.name + '/SORT', function undefined(state, _ref5) {
      var column = _ref5.column,
          ascending = _ref5.ascending;
      state.ascending = ascending;
      state.sortBy = column;
      if (self.source == 'server') self.getData();
    }), _defineProperty(_merge$recursive, self.name + '/SORTED', function undefined(state, data) {}), _defineProperty(_merge$recursive, self.name + '/ROW_CLICK', function undefined(state, row) {}), _defineProperty(_merge$recursive, self.name + '/FILTER', function undefined(state, row) {}), _defineProperty(_merge$recursive, self.name + '/LIMIT', function undefined(state, limit) {}), _merge$recursive), extra);
  };

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }
  /***/

},
/* 164 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return {
      framework: 'bootstrap3',
      table: 'table table-striped table-bordered table-hover',
      row: 'row',
      column: 'col-md-12',
      label: '',
      input: 'form-control',
      select: 'form-control',
      field: 'form-group',
      inline: 'form-inline',
      right: 'pull-right',
      left: 'pull-left',
      center: 'text-center',
      contentCenter: '',
      small: '',
      nomargin: '',
      groupTr: 'info',
      button: 'btn btn-secondary',
      dropdown: {
        container: 'dropdown',
        trigger: 'dropdown-toggle',
        menu: 'dropdown-menu',
        content: '',
        item: '',
        caret: 'caret'
      },
      pagination: {
        nav: '',
        count: '',
        wrapper: '',
        list: 'pagination',
        item: 'page-item',
        link: 'page-link',
        next: '',
        prev: '',
        active: 'active',
        disabled: 'disabled'
      }
    };
  };
  /***/

},
/* 165 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return {
      framework: 'bootstrap4',
      table: 'table table-striped table-bordered table-hover',
      row: 'row',
      column: 'col-md-12',
      label: '',
      input: 'form-control',
      select: 'form-control',
      field: 'form-group',
      inline: 'form-inline',
      right: 'float-right',
      left: 'float-left',
      center: 'text-center',
      contentCenter: 'justify-content-center',
      nomargin: 'm-0',
      groupTr: 'table-info',
      small: '',
      button: 'btn btn-secondary',
      dropdown: {
        container: 'dropdown',
        trigger: 'dropdown-toggle',
        menu: 'dropdown-menu',
        content: '',
        item: 'dropdown-item',
        caret: 'caret'
      },
      pagination: {
        nav: '',
        count: '',
        wrapper: '',
        list: 'pagination',
        item: 'page-item',
        link: 'page-link',
        next: '',
        prev: '',
        active: 'active',
        disabled: 'disabled'
      }
    };
  };
  /***/

},
/* 166 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return {
      framework: 'bulma',
      table: 'table is-bordered is-striped is-hoverable is-fullwidth',
      row: 'columns',
      column: 'column is-12',
      label: 'label',
      input: 'input',
      select: 'select',
      field: 'field',
      inline: 'is-horizontal',
      right: 'is-pulled-right',
      left: 'is-pulled-left',
      center: 'has-text-centered',
      contentCenter: 'is-centered',
      icon: 'icon',
      small: 'is-small',
      nomargin: 'marginless',
      button: 'button',
      groupTr: 'is-selected',
      dropdown: {
        container: 'dropdown',
        trigger: 'dropdown-trigger',
        menu: 'dropdown-menu',
        content: 'dropdown-content',
        item: 'dropdown-item',
        caret: 'fa fa-angle-down'
      },
      pagination: {
        nav: '',
        count: '',
        wrapper: 'pagination',
        list: 'pagination-list',
        item: '',
        link: 'pagination-link',
        next: '',
        prev: '',
        active: 'is-current',
        disabled: ''
      }
    };
  };
  /***/

},
/* 167 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function (h, modules, classes, slots) {
    var filterId = 'VueTables__search_' + this.id;
    var ddpId = 'VueTables__dropdown-pagination_' + this.id;
    var perpageId = 'VueTables__limit_' + this.id;

    var perpageValues = __webpack_require__(25).call(this, h);

    var genericFilter = this.hasGenericFilter ? h('div', {
      'class': 'VueTables__search-field'
    }, [h('label', {
      attrs: {
        'for': filterId
      },
      'class': classes.label
    }, [this.display('filter')]), modules.normalFilter(classes, filterId)]) : '';
    var perpage = perpageValues.length > 1 ? h('div', {
      'class': 'VueTables__limit-field'
    }, [h('label', {
      'class': classes.label,
      attrs: {
        'for': perpageId
      }
    }, [this.display('limit')]), modules.perPage(perpageValues, classes.select, perpageId)]) : '';
    var dropdownPagination = this.opts.pagination && this.opts.pagination.dropdown ? h('div', {
      'class': 'VueTables__pagination-wrapper'
    }, [h('div', {
      'class': classes.field + ' ' + classes.inline + ' ' + classes.right + ' VueTables__dropdown-pagination',
      directives: [{
        name: 'show',
        value: this.totalPages > 1
      }]
    }, [h('label', {
      attrs: {
        'for': ddpId
      }
    }, [this.display('page')]), modules.dropdownPagination(classes.select, ddpId)])]) : '';
    var columnsDropdown = this.opts.columnsDropdown ? h('div', {
      'class': 'VueTables__columns-dropdown-wrapper'
    }, [modules.columnsDropdown(classes)]) : '';
    var footerHeadings = this.opts.footerHeadings ? h('tfoot', [h('tr', [modules.headings(classes.right)])]) : '';
    var shouldShowTop = genericFilter || perpage || dropdownPagination || columnsDropdown || slots.beforeFilter || slots.afterFilter || slots.beforeLimit || slots.afterLimit;
    var tableTop = h('div', {
      'class': classes.row,
      directives: [{
        name: 'show',
        value: shouldShowTop
      }]
    }, [h('div', {
      'class': classes.column
    }, [h('div', {
      'class': classes.field + ' ' + classes.inline + ' ' + classes.left + ' VueTables__search'
    }, [slots.beforeFilter, genericFilter, slots.afterFilter]), h('div', {
      'class': classes.field + ' ' + classes.inline + ' ' + classes.right + ' VueTables__limit'
    }, [slots.beforeLimit, perpage, slots.afterLimit]), dropdownPagination, columnsDropdown])]);
    return h('div', {
      'class': "VueTables VueTables--" + this.source
    }, [tableTop, slots.beforeTable, h('div', {
      'class': 'table-responsive'
    }, [h('table', {
      'class': 'VueTables__table ' + (this.opts.skin ? this.opts.skin : classes.table)
    }, [h('thead', [h('tr', [modules.headings(classes.right)]), slots.beforeFilters, modules.columnFilters(classes), slots.afterFilters]), footerHeadings, slots.beforeBody, h('tbody', [slots.prependBody, modules.rows(classes), slots.appendBody]), slots.afterBody])]), slots.afterTable, modules.pagination((0, _merge2.default)(classes.pagination, {
      wrapper: classes.row + ' ' + classes.column + ' ' + classes.contentCenter,
      nav: classes.center,
      count: classes.center + ' ' + classes.column
    })), modules.dropdownPaginationCount()]);
  };
  /***/

},
/* 168 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function (h, modules, classes, slots) {
    var filterId = 'VueTables__search_' + this.id;
    var perpageId = 'VueTables__limit_' + this.id;

    var perpageValues = __webpack_require__(25).call(this, h);

    var genericFilter = this.hasGenericFilter ? h('div', {
      'class': 'VueTables__search-field'
    }, [h('label', {
      attrs: {
        'for': filterId
      },
      'class': classes.label
    }, [this.display('filter')]), modules.normalFilter(classes, filterId)]) : '';
    var perpage = perpageValues.length > 1 ? h('div', {
      'class': 'VueTables__limit-field'
    }, [h('label', {
      'class': classes.label,
      attrs: {
        'for': perpageId
      }
    }, [this.display('limit')]), modules.perPage(perpageValues, classes.select, perpageId)]) : '';
    var columnsDropdown = this.opts.columnsDropdown ? h('div', {
      'class': 'VueTables__columns-dropdown-wrapper'
    }, [modules.columnsDropdown(classes)]) : '';
    var shouldShowTop = genericFilter || perpage || columnsDropdown || slots.beforeFilter || slots.afterFilter || slots.beforeLimit || slots.afterLimit;
    var tableTop = h('div', {
      'class': classes.row,
      directives: [{
        name: 'show',
        value: shouldShowTop
      }]
    }, [h('div', {
      'class': classes.column
    }, [h('div', {
      'class': classes.field + ' ' + classes.inline + ' ' + classes.left + ' VueTables__search'
    }, [slots.beforeFilter, genericFilter, slots.afterFilter]), h('div', {
      'class': classes.field + ' ' + classes.inline + ' ' + classes.right + ' VueTables__limit'
    }, [slots.beforeLimit, perpage, slots.afterLimit]), columnsDropdown])]);
    return h('div', {
      'class': "VueTables VueTables--" + this.source
    }, [tableTop, slots.beforeTable, h('div', {
      'class': 'table-responsive'
    }, [h('table', {
      'class': 'VueTables__table ' + (this.opts.skin ? this.opts.skin : classes.table)
    }, [h('thead', [h('tr', [modules.headings(classes.right)]), slots.beforeFilters, modules.columnFilters(classes), slots.afterFilters]), h('tfoot', [h('tr', [h('td', {
      attrs: {
        colspan: this.colspan
      }
    }, [modules.pagination((0, _merge2.default)(classes.pagination, {
      list: classes.pagination.list + ' ' + classes.right + ' ' + classes.nomargin,
      count: '' + classes.left
    }))])])]), slots.beforeBody, h('tbody', [slots.prependBody, modules.rows(classes), slots.appendBody]), slots.afterBody])]), slots.afterTable]);
  };
  /***/

},
/* 169 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    return function (classes) {
      var data;

      if (_this.source === 'client') {
        data = _this.filteredData;

        if (!data.length && _this.source === 'client' && _this.page !== 1) {
          // data was dynamically removed go to last page
          _this.setPage(_this.totalPages ? _this.totalPages : 1);
        }
      } else {
        data = _this.tableData;
      }

      if (_this.count === 0) {
        var colspan = _this.allColumns.length;
        if (_this.hasChildRow) colspan++;
        return h('tr', {
          'class': 'VueTables__no-results'
        }, [h('td', {
          'class': 'text-center',
          attrs: {
            colspan: _this.colspan
          }
        }, [_this.display(_this.loading ? 'loading' : 'noResults')])]);
      }

      var rows = [];
      var columns;
      var rowKey = _this.opts.uniqueKey;
      var rowClass;
      var recordCount = (_this.Page - 1) * _this.limit;
      var currentGroup;
      var groupSlot;
      var groupValue;
      var groupByContent;
      data.map(function (row, index) {
        if (_this.opts.groupBy && _this.source === 'client' && row[_this.opts.groupBy] !== currentGroup) {
          groupSlot = _this.getGroupSlot(row[_this.opts.groupBy]);
          groupValue = row[_this.opts.groupBy];
          groupByContent = _this.opts.toggleGroups ? h('button', {
            'class': classes.button,
            on: {
              'click': _this.toggleGroup.bind(_this, groupValue)
            }
          }, [groupValue, h('span', {
            'class': _this.groupToggleIcon(groupValue)
          })]) : groupValue;
          rows.push(h('tr', {
            'class': classes.groupTr,
            on: {
              'click': _this._toggleGroupDirection.bind(_this)
            }
          }, [h('td', {
            attrs: {
              colspan: _this.colspan
            }
          }, [groupByContent, groupSlot])]));
          currentGroup = row[_this.opts.groupBy];
        }

        if (_this.opts.toggleGroups && _this.collapsedGroups.includes(currentGroup)) {
          return;
        }

        index = recordCount + index + 1;
        columns = [];

        if (_this.hasChildRow) {
          var childRowToggler = h('td', [h('span', {
            on: {
              'click': _this.toggleChildRow.bind(_this, row[rowKey])
            },
            'class': 'VueTables__child-row-toggler ' + _this.childRowTogglerClass(row[rowKey])
          })]);
          if (_this.opts.childRowTogglerFirst) columns.push(childRowToggler);
        }

        _this.allColumns.map(function (column) {
          var rowTemplate = _this.$scopedSlots && _this.$scopedSlots[column];
          columns.push(h('td', {
            'class': _this.columnClass(column)
          }, [rowTemplate ? rowTemplate({
            row: row,
            column: column,
            index: index
          }) : _this.render(row, column, index, h)]));
        });

        if (_this.hasChildRow && !_this.opts.childRowTogglerFirst) columns.push(childRowToggler);
        rowClass = _this.opts.rowClassCallback ? _this.opts.rowClassCallback(row) : '';
        rows.push(h('tr', {
          'class': rowClass,
          on: {
            'click': _this.rowWasClicked.bind(_this, row),
            'dblclick': _this.rowWasClicked.bind(_this, row)
          }
        }, [columns, ' ']));
        rows.push(_this.hasChildRow && _this.openChildRows.includes(row[rowKey]) ? h('tr', {
          'class': 'VueTables__child-row'
        }, [h('td', {
          attrs: {
            colspan: _this.colspan
          }
        }, [_this._getChildRowTemplate(h, row)])]) : h());
      });
      return rows;
    };
  };
  /***/

},
/* 170 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var debounce = __webpack_require__(8);

  module.exports = function (h) {
    var _this = this;

    return function (classes, id) {
      var search = _this.source == 'client' ? _this.search.bind(_this, _this.data) : _this.serverSearch.bind(_this);
      return h('input', {
        'class': classes.input + ' ' + classes.small,
        attrs: {
          type: 'text',
          value: _this.query,
          placeholder: _this.display('filterPlaceholder'),
          id: id
        },
        on: {
          'keyup': _this.opts.debounce ? debounce(search, _this.opts.debounce) : search
        }
      });
    };
  };
  /***/

},
/* 171 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var debounce = __webpack_require__(8);

  module.exports = function (h) {
    var _this = this;

    return function (selectClass, id) {
      var pages = [];
      var selected;

      for (var pag = 1; pag <= _this.totalPages; pag++) {
        var selected = _this.page == pag;
        pages.push(h("option", {
          attrs: {
            value: pag
          },
          domProps: {
            "selected": selected
          }
        }, [pag]));
      }

      return h("select", {
        "class": selectClass + " dropdown-pagination",
        directives: [{
          name: "show",
          value: _this.totalPages > 1
        }],
        attrs: {
          name: "page",
          value: _this.page,
          id: id
        },
        ref: "page",
        on: {
          "change": _this.setPage.bind(_this, null, false)
        }
      }, [pages]);
    };
  };
  /***/

},
/* 172 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    return function () {
      if (_this.count > 0 && _this.opts.pagination.dropdown) {
        var perPage = parseInt(_this.limit);
        var from = (_this.Page - 1) * perPage + 1;
        var to = _this.Page == _this.totalPages ? _this.count : from + perPage - 1;

        var parts = _this.opts.texts.count.split('|');

        var i = Math.min(_this.count == 1 ? 2 : _this.totalPages == 1 ? 1 : 0, parts.length - 1);
        var count = parts[i].replace('{count}', _this.count).replace('{from}', from).replace('{to}', to);
        return h('div', {
          'class': 'VuePagination'
        }, [h('p', {
          'class': 'VuePagination__count'
        }, [count])]);
      }

      return '';
    };
  };
  /***/

},
/* 173 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  module.exports = function (h) {
    var _this = this;

    return function (classes) {
      if (!_this.opts.filterByColumn || !_this.opts.filterable) return '';

      var textFilter = __webpack_require__(174).call(_this, h, classes.input);

      var dateFilter = __webpack_require__(175).call(_this, h);

      var listFilter = __webpack_require__(176).call(_this, h, classes.select);

      var filters = [];
      var filter;
      if (_this.hasChildRow && _this.opts.childRowTogglerFirst) filters.push(h('th'));

      _this.allColumns.map(function (column) {
        var filter = '';

        if (_this.filterable(column)) {
          switch (true) {
            case _this.isTextFilter(column):
              filter = textFilter(column);
              break;

            case _this.isDateFilter(column):
              filter = dateFilter(column);
              break;

            case _this.isListFilter(column):
              filter = listFilter(column);
              break;
          }
        }

        if (typeof _this.$slots['filter__' + column] !== 'undefined') {
          filter = filter ? h('div', [filter, _this.$slots['filter__' + column]]) : _this.$slots['filter__' + column];
        }

        filters.push(h('th', {
          'class': _this.columnClass(column)
        }, [h('div', _defineProperty({
          'class': 'VueTables__column-filter'
        }, 'class', 'VueTables__' + column + '-filter-wrapper'), [filter])]));
      });

      if (_this.hasChildRow && !_this.opts.childRowTogglerFirst) filters.push(h('th'));
      return h('tr', {
        'class': 'VueTables__filters-row'
      }, [filters]);
    };
  };
  /***/

},
/* 174 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var debounce = __webpack_require__(8);

  module.exports = function (h, inputClass) {
    var _this = this;

    var search = this.source == 'client' ? this.search.bind(this, this.data) : this.serverSearch.bind(this);

    if (this.opts.debounce) {
      var debouncedSearch = debounce(search, this.opts.debounce);

      var onKeyUp = function onKeyUp(e) {
        if (e.keyCode === 13) {
          debouncedSearch.clear();
          search.apply(undefined, arguments);
        } else {
          debouncedSearch.apply(undefined, arguments);
        }
      };
    }

    return function (column) {
      return h('input', {
        on: {
          'keyup': _this.opts.debounce ? onKeyUp : search
        },
        'class': inputClass,
        attrs: {
          name: _this._getColumnName(column),
          type: 'text',
          placeholder: _this.display('filterBy', {
            column: _this.getHeading(column)
          }),
          value: _this.query[column]
        }
      });
    };
  };
  /***/

},
/* 175 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    return function (column) {
      return h('div', {
        'class': 'VueTables__date-filter',
        attrs: {
          id: 'VueTables__' + column + '-filter'
        }
      }, [h('span', {
        'class': 'VueTables__filter-placeholder'
      }, [_this.display('filterBy', {
        column: _this.getHeading(column)
      })])]);
    };
  };
  /***/

},
/* 176 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h, selectClass) {
    var _this = this;

    return function (column) {
      var options = [];
      var selected = void 0;
      var search = _this.source == 'client' ? _this.search.bind(_this, _this.data) : _this.serverSearch.bind(_this);

      var displayable = _this.opts.listColumns[column].filter(function (item) {
        return !item.hide;
      });

      displayable.map(function (option) {
        selected = option.id == _this.query[column] && _this.query[column] !== '';
        options.push(h('option', {
          attrs: {
            value: option.id
          },
          domProps: {
            'selected': selected
          }
        }, [option.text]));
      });
      return h('div', {
        'class': 'VueTables__list-filter',
        attrs: {
          id: 'VueTables__' + column + '-filter'
        }
      }, [h('select', {
        'class': selectClass,
        on: {
          'change': search
        },
        attrs: {
          name: _this._getColumnName(column),
          value: _this.query[column]
        }
      }, [h('option', {
        attrs: {
          value: ''
        }
      }, [_this.display('defaultOption', {
        column: _this.opts.headings[column] ? _this.opts.headings[column] : column
      })]), options])]);
    };
  };
  /***/

},
/* 177 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    return function (theme) {
      if (_this.opts.pagination && _this.opts.pagination.dropdown) return '';
      var options = {
        theme: theme,
        chunk: _this.opts.pagination.chunk,
        chunksNavigation: _this.opts.pagination.nav,
        edgeNavigation: _this.opts.pagination.edge,
        texts: {
          count: _this.opts.texts.count,
          first: _this.opts.texts.first,
          last: _this.opts.texts.last
        }
      };
      var name = _this.vuex ? _this.name : _this.id;
      return h("pagination", {
        ref: "pagination",
        attrs: {
          options: options,
          "for": name,
          vuex: _this.vuex,
          records: _this.count,
          "per-page": parseInt(_this.limit)
        },
        on: {
          "paginate": _this._onPagination.bind(_this)
        }
      });
    };
  };
  /***/

},
/* 178 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    return function (right) {
      var sortControl = __webpack_require__(179)(h, right);

      var headings = [];
      if (_this.hasChildRow && _this.opts.childRowTogglerFirst) headings.push(h("th"));

      _this.allColumns.map(function (column) {
        headings.push(h("th", {
          on: {
            "click": this.orderByColumn.bind(this, column)
          },
          "class": this.sortableClass(column)
        }, [h("span", {
          "class": "VueTables__heading",
          attrs: {
            title: this.getHeadingTooltip(column, h)
          }
        }, [this.getHeading(column, h)]), sortControl.call(this, column)]));
      }.bind(_this));

      if (_this.hasChildRow && !_this.opts.childRowTogglerFirst) headings.push(h("th"));
      return headings;
    };
  };
  /***/

},
/* 179 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h, right) {
    return function (column) {
      if (!this.sortable(column)) return '';
      return h('span', {
        'class': 'VueTables__sort-icon ' + right + ' ' + this.sortableChevronClass(column)
      });
    };
  };
  /***/

},
/* 180 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h) {
    var _this = this;

    return function (perpageValues, cls, id) {
      return perpageValues.length > 1 ? h("select", {
        "class": cls,
        attrs: {
          name: "limit",
          value: _this.limit,
          id: id
        },
        on: {
          "change": _this.setLimit.bind(_this)
        }
      }, [perpageValues]) : '';
    };
  };
  /***/

},
/* 181 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var dropdownWrapper = __webpack_require__(182);

  var dropdownItemWrapper = __webpack_require__(183);

  module.exports = function (h) {
    var _this = this;

    return function (classes) {
      var cols = _this.columns.map(function (column) {
        return dropdownItemWrapper(h, classes, h('a', {
          'class': classes.dropdown.item,
          attrs: {
            href: '#'
          },
          on: {
            'click': function click() {
              return _this.toggleColumn(column);
            }
          }
        }, [h('input', {
          attrs: {
            type: 'checkbox',
            value: column,
            disabled: _this._onlyColumn(column)
          },
          domProps: {
            'checked': _this.allColumns.includes(column)
          }
        }), _this.getHeading(column)]));
      });

      return h('div', {
        ref: 'columnsdropdown',
        'class': classes.dropdown.container + ' ' + classes.right + ' VueTables__columns-dropdown'
      }, [h('button', {
        attrs: {
          type: 'button'
        },
        'class': classes.button + ' ' + classes.dropdown.trigger,
        on: {
          'click': _this._toggleColumnsDropdown.bind(_this)
        }
      }, [_this.display('columns'), h('span', {
        'class': classes.icon + ' ' + classes.small
      }, [h('i', {
        'class': classes.dropdown.caret
      })])]), dropdownWrapper.call(_this, h, classes, cols)]);
    };
  };
  /***/

},
/* 182 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h, classes, columns) {
    if (classes.framework === 'bulma') {
      return h('div', {
        'class': classes.dropdown.menu,
        style: this.displayColumnsDropdown ? 'display:block' : 'display:none'
      }, [h('div', {
        'class': classes.dropdown.content
      }, [columns])]);
    }

    if (classes.framework === 'bootstrap4') {
      return h('div', {
        'class': classes.dropdown.menu,
        style: this.displayColumnsDropdown ? 'display:block' : 'display:none'
      }, [columns]);
    }

    return h('ul', {
      'class': classes.dropdown.menu,
      style: this.displayColumnsDropdown ? 'display:block' : 'display:none'
    }, [columns]);
  };
  /***/

},
/* 183 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (h, classes, item) {
    if (classes.framework === 'bulma') {
      return item;
    }

    return h('li', [item]);
  };
  /***/

},
/* 184 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return {
      beforeFilters: this.$slots.beforeFilters ? this.$slots.beforeFilters : '',
      afterFilters: this.$slots.afterFilters ? this.$slots.afterFilters : '',
      beforeBody: this.$slots.beforeBody ? this.$slots.beforeBody : '',
      prependBody: this.$slots.prependBody ? this.$slots.prependBody : '',
      appendBody: this.$slots.appendBody ? this.$slots.appendBody : '',
      afterBody: this.$slots.afterBody ? this.$slots.afterBody : '',
      beforeFilter: this.$slots.beforeFilter ? this.$slots.beforeFilter : '',
      afterFilter: this.$slots.afterFilter ? this.$slots.afterFilter : '',
      beforeLimit: this.$slots.beforeLimit ? this.$slots.beforeLimit : '',
      afterLimit: this.$slots.afterLimit ? this.$slots.afterLimit : '',
      beforeTable: this.$slots.beforeTable ? this.$slots.beforeTable : '',
      afterTable: this.$slots.afterTable ? this.$slots.afterTable : ''
    };
  };
  /***/

},
/* 185 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return this.opts.filterByColumn ? JSON.stringify(this.query) : this.query;
  };
  /***/

},
/* 186 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    return JSON.stringify(this.customQueries);
  };
  /***/

},
/* 187 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var search = __webpack_require__(27);

  var clone = __webpack_require__(190);

  module.exports = function () {
    var data = clone(this.tableData);
    var column = this.orderBy.column;
    data = this.search(data);

    if (column) {
      // dummy var to force compilation
      if (this.time) this.time = this.time;
      data = this.opts.sortingAlgorithm.call(this, data, column);
    } else if (this.opts.groupBy) {
      data = this.opts.sortingAlgorithm.call(this, data, this.opts.groupBy);
    }

    if (this.vuex) {
      if (this.count != data.length) this.commit('SET_COUNT', data.length);
    } else {
      this.count = data.length;
    }

    var offset = (this.page - 1) * this.limit;
    this.allFilteredData = JSON.parse(JSON.stringify(data));
    data = data.splice(offset, this.limit);
    return this.applyFilters(data);
  };
  /***/

},
/* 188 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function (obj) {
    var count = 0;

    for (var prop in obj) {
      var isDateRange = _typeof(obj[prop]) == 'object';
      if (isDateRange || obj[prop] && (!isNaN(obj[prop]) || obj[prop].trim())) count++;
    }

    return count;
  };
  /***/

},
/* 189 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (data, customFilters, customQueries) {
    var passing;
    return data.filter(function (row) {
      passing = true;
      customFilters.forEach(function (filter) {
        var value = customQueries[filter.name];
        if (value && !filter.callback(row, value)) passing = false;
      });
      return passing;
    });
  };
  /***/

},
/* 190 */

/***/
function (module, exports, __webpack_require__) {
  /* WEBPACK VAR INJECTION */
  (function (Buffer) {
    var clone = function () {
      'use strict';

      function _instanceof(obj, type) {
        return type != null && obj instanceof type;
      }

      var nativeMap;

      try {
        nativeMap = Map;
      } catch (_) {
        // maybe a reference error because no `Map`. Give it a dummy value that no
        // value will ever be an instanceof.
        nativeMap = function nativeMap() {};
      }

      var nativeSet;

      try {
        nativeSet = Set;
      } catch (_) {
        nativeSet = function nativeSet() {};
      }

      var nativePromise;

      try {
        nativePromise = Promise;
      } catch (_) {
        nativePromise = function nativePromise() {};
      }
      /**
       * Clones (copies) an Object using deep copying.
       *
       * This function supports circular references by default, but if you are certain
       * there are no circular references in your object, you can save some CPU time
       * by calling clone(obj, false).
       *
       * Caution: if `circular` is false and `parent` contains circular references,
       * your program may enter an infinite loop and crash.
       *
       * @param `parent` - the object to be cloned
       * @param `circular` - set to true if the object to be cloned may contain
       *    circular references. (optional - true by default)
       * @param `depth` - set to a number if the object is only to be cloned to
       *    a particular depth. (optional - defaults to Infinity)
       * @param `prototype` - sets the prototype to be used when cloning an object.
       *    (optional - defaults to parent prototype).
       * @param `includeNonEnumerable` - set to true if the non-enumerable properties
       *    should be cloned as well. Non-enumerable properties on the prototype
       *    chain will be ignored. (optional - false by default)
      */


      function clone(parent, circular, depth, prototype, includeNonEnumerable) {
        if (_typeof2(circular) === 'object') {
          depth = circular.depth;
          prototype = circular.prototype;
          includeNonEnumerable = circular.includeNonEnumerable;
          circular = circular.circular;
        } // maintain two arrays for circular references, where corresponding parents
        // and children have the same index


        var allParents = [];
        var allChildren = [];
        var useBuffer = typeof Buffer != 'undefined';
        if (typeof circular == 'undefined') circular = true;
        if (typeof depth == 'undefined') depth = Infinity; // recurse this function so we don't reset allParents and allChildren

        function _clone(parent, depth) {
          // cloning null always returns null
          if (parent === null) return null;
          if (depth === 0) return parent;
          var child;
          var proto;

          if (_typeof2(parent) != 'object') {
            return parent;
          }

          if (_instanceof(parent, nativeMap)) {
            child = new nativeMap();
          } else if (_instanceof(parent, nativeSet)) {
            child = new nativeSet();
          } else if (_instanceof(parent, nativePromise)) {
            child = new nativePromise(function (resolve, reject) {
              parent.then(function (value) {
                resolve(_clone(value, depth - 1));
              }, function (err) {
                reject(_clone(err, depth - 1));
              });
            });
          } else if (clone.__isArray(parent)) {
            child = [];
          } else if (clone.__isRegExp(parent)) {
            child = new RegExp(parent.source, __getRegExpFlags(parent));
            if (parent.lastIndex) child.lastIndex = parent.lastIndex;
          } else if (clone.__isDate(parent)) {
            child = new Date(parent.getTime());
          } else if (useBuffer && Buffer.isBuffer(parent)) {
            child = new Buffer(parent.length);
            parent.copy(child);
            return child;
          } else if (_instanceof(parent, Error)) {
            child = Object.create(parent);
          } else {
            if (typeof prototype == 'undefined') {
              proto = Object.getPrototypeOf(parent);
              child = Object.create(proto);
            } else {
              child = Object.create(prototype);
              proto = prototype;
            }
          }

          if (circular) {
            var index = allParents.indexOf(parent);

            if (index != -1) {
              return allChildren[index];
            }

            allParents.push(parent);
            allChildren.push(child);
          }

          if (_instanceof(parent, nativeMap)) {
            parent.forEach(function (value, key) {
              var keyChild = _clone(key, depth - 1);

              var valueChild = _clone(value, depth - 1);

              child.set(keyChild, valueChild);
            });
          }

          if (_instanceof(parent, nativeSet)) {
            parent.forEach(function (value) {
              var entryChild = _clone(value, depth - 1);

              child.add(entryChild);
            });
          }

          for (var i in parent) {
            var attrs;

            if (proto) {
              attrs = Object.getOwnPropertyDescriptor(proto, i);
            }

            if (attrs && attrs.set == null) {
              continue;
            }

            child[i] = _clone(parent[i], depth - 1);
          }

          if (Object.getOwnPropertySymbols) {
            var symbols = Object.getOwnPropertySymbols(parent);

            for (var i = 0; i < symbols.length; i++) {
              // Don't need to worry about cloning a symbol because it is a primitive,
              // like a number or string.
              var symbol = symbols[i];
              var descriptor = Object.getOwnPropertyDescriptor(parent, symbol);

              if (descriptor && !descriptor.enumerable && !includeNonEnumerable) {
                continue;
              }

              child[symbol] = _clone(parent[symbol], depth - 1);

              if (!descriptor.enumerable) {
                Object.defineProperty(child, symbol, {
                  enumerable: false
                });
              }
            }
          }

          if (includeNonEnumerable) {
            var allPropertyNames = Object.getOwnPropertyNames(parent);

            for (var i = 0; i < allPropertyNames.length; i++) {
              var propertyName = allPropertyNames[i];
              var descriptor = Object.getOwnPropertyDescriptor(parent, propertyName);

              if (descriptor && descriptor.enumerable) {
                continue;
              }

              child[propertyName] = _clone(parent[propertyName], depth - 1);
              Object.defineProperty(child, propertyName, {
                enumerable: false
              });
            }
          }

          return child;
        }

        return _clone(parent, depth);
      }
      /**
       * Simple flat clone using prototype, accepts only objects, usefull for property
       * override on FLAT configuration object (no nested props).
       *
       * USE WITH CAUTION! This may not behave as you wish if you do not know how this
       * works.
       */


      clone.clonePrototype = function clonePrototype(parent) {
        if (parent === null) return null;

        var c = function c() {};

        c.prototype = parent;
        return new c();
      }; // private utility functions


      function __objToStr(o) {
        return Object.prototype.toString.call(o);
      }

      clone.__objToStr = __objToStr;

      function __isDate(o) {
        return _typeof2(o) === 'object' && __objToStr(o) === '[object Date]';
      }

      clone.__isDate = __isDate;

      function __isArray(o) {
        return _typeof2(o) === 'object' && __objToStr(o) === '[object Array]';
      }

      clone.__isArray = __isArray;

      function __isRegExp(o) {
        return _typeof2(o) === 'object' && __objToStr(o) === '[object RegExp]';
      }

      clone.__isRegExp = __isRegExp;

      function __getRegExpFlags(re) {
        var flags = '';
        if (re.global) flags += 'g';
        if (re.ignoreCase) flags += 'i';
        if (re.multiline) flags += 'm';
        return flags;
      }

      clone.__getRegExpFlags = __getRegExpFlags;
      return clone;
    }();

    if (_typeof2(module) === 'object' && module.exports) {
      module.exports = clone;
    }
    /* WEBPACK VAR INJECTION */

  }).call(exports, __webpack_require__(17).Buffer);
  /***/
},
/* 191 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    this.data.forEach(function (row, index) {
      this.opts.dateColumns.forEach(function (column) {
        row[column] = row[column] ? moment(row[column], this.opts.toMomentFormat) : '';
      }.bind(this));
    }.bind(this));
  };
  /***/

},
/* 192 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _bus = __webpack_require__(1);

  var _bus2 = _interopRequireDefault(_bus);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function () {
    var _this = this;

    var event = 'vue-tables';
    if (this.name) event += '.' + this.name;
    this.opts.customFilters.forEach(function (filter) {
      _bus2.default.$off(event + '.filter::' + filter.name);

      _bus2.default.$on(event + '.filter::' + filter.name, function (value) {
        _this.customQueries[filter.name] = value;

        _this.updateState('customQueries', _this.customQueries);
      });
    });
  };
  /***/

},
/* 193 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (column, ascending) {
    var multiIndex = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : -1;
    var sort = this.defaultSort;
    var multiSort = this.userMultiSorting[this.currentlySorting.column] ? this.userMultiSorting[this.currentlySorting.column] : this.opts.multiSorting[this.currentlySorting.column];
    var asc = this.currentlySorting.ascending;
    var self = this;
    return function (a, b) {
      var aVal = self._getValue(a, column) || '';
      var bVal = self._getValue(b, column) || '';
      var dir = ascending ? 1 : -1;
      var secondaryAsc;
      if (typeof aVal === 'string') aVal = aVal.toLowerCase();
      if (typeof bVal === 'string') bVal = bVal.toLowerCase();

      if (aVal === bVal && multiSort && multiSort[multiIndex + 1]) {
        var sortData = multiSort[multiIndex + 1];

        if (typeof sortData.ascending !== 'undefined') {
          secondaryAsc = sortData.ascending;
        } else {
          secondaryAsc = sortData.matchDir ? asc : !asc;
        }

        return sort(sortData.column, secondaryAsc, multiIndex + 1)(a, b);
      }

      return aVal > bVal ? dir : -dir;
    };
  };
  /***/

},
/* 194 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function (value) {
    if (this.$scopedSlots && this.$scopedSlots['__group_meta']) {
      var data = this.opts.groupMeta.find(function (val) {
        return val.value === value;
      });
      if (!data) return '';
      return this.$scopedSlots['__group_meta'](data);
    }

    return '';
  };
  /***/

},
/* 195 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _merge = __webpack_require__(0);

  var _merge2 = _interopRequireDefault(_merge);

  var _data2 = __webpack_require__(21);

  var _data3 = _interopRequireDefault(_data2);

  var _vuex = __webpack_require__(12);

  var _vuex2 = _interopRequireDefault(_vuex);

  var _normal = __webpack_require__(13);

  var _normal2 = _interopRequireDefault(_normal);

  var _table = __webpack_require__(14);

  var _table2 = _interopRequireDefault(_table);

  var _vuePagination = __webpack_require__(10);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  var _data = __webpack_require__(22);

  var _created = __webpack_require__(23);

  var templateCompiler = __webpack_require__(24);

  exports.install = function (Vue, globalOptions, useVuex) {
    var theme = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 'bootstrap3';
    var template = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : 'default';
    var state = useVuex ? (0, _vuex2.default)('server') : (0, _normal2.default)();

    var server = _merge2.default.recursive(true, (0, _table2.default)(), {
      name: 'server-table',
      components: {
        Pagination: _vuePagination.Pagination
      },
      render: templateCompiler.call(this, template, theme),
      props: {
        columns: {
          type: Array,
          required: true
        },
        url: {
          type: String
        },
        name: {
          type: String,
          required: false
        },
        options: {
          type: Object,
          required: false,
          default: function _default() {
            return {};
          }
        }
      },
      created: function created() {
        if (!this.opts.requestFunction && !this.url) {
          throw 'vue-tables-2: you must provide either a "url" prop or a custom request function. Aborting';
        }

        _created(this);

        if (!this.vuex) {
          this.query = this.initQuery();
          this.initOrderBy();
          this.customQueries = this.initCustomFilters();
        }

        this.loadState();
        this.getData(true).then(function (response) {
          this.setData(response);
          this.loading = false;

          if (this.hasDateFilters()) {
            setTimeout(function () {
              this.initDateFilters();
            }.bind(this), 0);
          }
        }.bind(this));
      },
      mounted: function mounted() {
        this._setColumnsDropdownCloseListener();

        if (this.vuex) return;
        this.registerServerFilters();
        if (this.options.initialPage) this.setPage(this.options.initialPage, true);
      },
      data: function data() {
        return _merge2.default.recursive(_data(), {
          source: 'server',
          loading: true,
          lastKeyStrokeAt: false,
          globalOptions: globalOptions
        }, (0, _data3.default)(useVuex, 'server', this.options.initialPage));
      },
      methods: {
        refresh: __webpack_require__(196),
        getData: __webpack_require__(197),
        setData: __webpack_require__(198),
        serverSearch: __webpack_require__(15),
        registerServerFilters: __webpack_require__(199),
        loadState: function loadState() {
          var _this = this;

          if (!this.opts.saveState) return;

          if (!this.storage.getItem(this.stateKey)) {
            this.initState();
            this.activeState = true;
            return;
          }

          var state = JSON.parse(this.storage.getItem(this.stateKey));

          if (this.vuex) {
            this.commit('SET_STATE', {
              query: state.query,
              customQueries: state.customQueries,
              page: state.page,
              limit: state.perPage,
              orderBy: state.orderBy
            });
          } else {
            this.page = state.page;
            this.query = state.query;
            this.customQueries = state.customQueries;
            this.limit = state.perPage;
            this.orderBy = state.orderBy;
          }

          if (!this.opts.pagination.dropdown) {
            setTimeout(function () {
              _this.$refs.pagination.Page = state.page;
            }, 0);
          }

          this.activeState = true;
        }
      },
      watch: {
        url: function url() {
          this.refresh();
        }
      },
      computed: {
        totalPages: __webpack_require__(26),
        filteredQuery: __webpack_require__(200),
        hasMultiSort: function hasMultiSort() {
          return this.opts.serverMultiSorting;
        }
      }
    }, state);

    Vue.component('v-server-table', server);
    return server;
  };
  /***/

},
/* 196 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  module.exports = function () {
    this.serverSearch();
  };
  /***/

},
/* 197 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  var merge = __webpack_require__(0);

  module.exports = function (promiseOnly) {
    var _data;

    var additionalData = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    var emitLoading = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
    var keys = this.opts.requestKeys;
    var data = (_data = {}, _defineProperty(_data, keys.query, this.filteredQuery), _defineProperty(_data, keys.limit, this.limit), _defineProperty(_data, keys.ascending, this.orderBy.ascending ? 1 : 0), _defineProperty(_data, keys.page, this.page), _defineProperty(_data, keys.byColumn, this.opts.filterByColumn ? 1 : 0), _data);
    if (this.orderBy.hasOwnProperty('column') && this.orderBy.column) data[keys.orderBy] = this.orderBy.column;
    data = merge(data, this.opts.params, this.customQueries, additionalData);

    if (this.hasMultiSort && this.orderBy.column && this.userMultiSorting[this.orderBy.column]) {
      data.multiSort = this.userMultiSorting[this.orderBy.column];
    }

    data = this.opts.requestAdapter(data);

    if (emitLoading) {
      this.dispatch('loading', data);
    }

    var promise = this.sendRequest(data);
    if (promiseOnly) return promise;
    return promise.then(function (response) {
      return this.setData(response);
    }.bind(this));
  };
  /***/

},
/* 198 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function (response) {
    var data = this.opts.responseAdapter.call(this, response);
    this.data = this.applyFilters(data.data);

    if (isNaN(data.count)) {
      console.error('vue-tables-2: invalid \'count\' property. Expected number, got ' + _typeof(data.count));
      console.error('count equals', data.count);
      throw new Error();
    }

    this.count = parseInt(data.count);
    setTimeout(function () {
      this.dispatch('loaded', response);
    }.bind(this), 0);
  };
  /***/

},
/* 199 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _bus = __webpack_require__(1);

  var _bus2 = _interopRequireDefault(_bus);

  function _interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  }

  module.exports = function () {
    var event = 'vue-tables';
    if (this.name) event += '.' + this.name;
    this.opts.customFilters.forEach(function (filter) {
      _bus2.default.$off(event + '.filter::' + filter);

      _bus2.default.$on(event + '.filter::' + filter, function (value) {
        this.customQueries[filter] = value;
        this.updateState('customQueries', this.customQueries);
        this.refresh();
      }.bind(this));
    }.bind(this));
  };
  /***/

},
/* 200 */

/***/
function (module, exports, __webpack_require__) {
  "use strict";

  var _typeof = typeof Symbol === "function" && _typeof2(Symbol.iterator) === "symbol" ? function (obj) {
    return _typeof2(obj);
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : _typeof2(obj);
  };

  module.exports = function () {
    if (_typeof(this.query) !== 'object' || this.opts.sendEmptyFilters) {
      return this.query;
    }

    var result = {};

    for (var key in this.query) {
      if (this.query[key] !== '') {
        result[key] = this.query[key];
      }
    }

    return result;
  };
  /***/

}]);
"use strict";

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/*!
 * Vue.js v2.5.17
 * (c) 2014-2018 Evan You
 * Released under the MIT License.
 */
(function (global, factory) {
  (typeof exports === "undefined" ? "undefined" : _typeof(exports)) === 'object' && typeof module !== 'undefined' ? module.exports = factory() : typeof define === 'function' && define.amd ? define(factory) : global.Vue = factory();
})(void 0, function () {
  'use strict';
  /*  */

  var emptyObject = Object.freeze({}); // these helpers produces better vm code in JS engines due to their
  // explicitness and function inlining

  function isUndef(v) {
    return v === undefined || v === null;
  }

  function isDef(v) {
    return v !== undefined && v !== null;
  }

  function isTrue(v) {
    return v === true;
  }

  function isFalse(v) {
    return v === false;
  }
  /**
   * Check if value is primitive
   */


  function isPrimitive(value) {
    return typeof value === 'string' || typeof value === 'number' || // $flow-disable-line
    _typeof(value) === 'symbol' || typeof value === 'boolean';
  }
  /**
   * Quick object check - this is primarily used to tell
   * Objects from primitive values when we know the value
   * is a JSON-compliant type.
   */


  function isObject(obj) {
    return obj !== null && _typeof(obj) === 'object';
  }
  /**
   * Get the raw type string of a value e.g. [object Object]
   */


  var _toString = Object.prototype.toString;

  function toRawType(value) {
    return _toString.call(value).slice(8, -1);
  }
  /**
   * Strict object type check. Only returns true
   * for plain JavaScript objects.
   */


  function isPlainObject(obj) {
    return _toString.call(obj) === '[object Object]';
  }

  function isRegExp(v) {
    return _toString.call(v) === '[object RegExp]';
  }
  /**
   * Check if val is a valid array index.
   */


  function isValidArrayIndex(val) {
    var n = parseFloat(String(val));
    return n >= 0 && Math.floor(n) === n && isFinite(val);
  }
  /**
   * Convert a value to a string that is actually rendered.
   */


  function toString(val) {
    return val == null ? '' : _typeof(val) === 'object' ? JSON.stringify(val, null, 2) : String(val);
  }
  /**
   * Convert a input value to a number for persistence.
   * If the conversion fails, return original string.
   */


  function toNumber(val) {
    var n = parseFloat(val);
    return isNaN(n) ? val : n;
  }
  /**
   * Make a map and return a function for checking if a key
   * is in that map.
   */


  function makeMap(str, expectsLowerCase) {
    var map = Object.create(null);
    var list = str.split(',');

    for (var i = 0; i < list.length; i++) {
      map[list[i]] = true;
    }

    return expectsLowerCase ? function (val) {
      return map[val.toLowerCase()];
    } : function (val) {
      return map[val];
    };
  }
  /**
   * Check if a tag is a built-in tag.
   */


  var isBuiltInTag = makeMap('slot,component', true);
  /**
   * Check if a attribute is a reserved attribute.
   */

  var isReservedAttribute = makeMap('key,ref,slot,slot-scope,is');
  /**
   * Remove an item from an array
   */

  function remove(arr, item) {
    if (arr.length) {
      var index = arr.indexOf(item);

      if (index > -1) {
        return arr.splice(index, 1);
      }
    }
  }
  /**
   * Check whether the object has the property.
   */


  var hasOwnProperty = Object.prototype.hasOwnProperty;

  function hasOwn(obj, key) {
    return hasOwnProperty.call(obj, key);
  }
  /**
   * Create a cached version of a pure function.
   */


  function cached(fn) {
    var cache = Object.create(null);
    return function cachedFn(str) {
      var hit = cache[str];
      return hit || (cache[str] = fn(str));
    };
  }
  /**
   * Camelize a hyphen-delimited string.
   */


  var camelizeRE = /-(\w)/g;
  var camelize = cached(function (str) {
    return str.replace(camelizeRE, function (_, c) {
      return c ? c.toUpperCase() : '';
    });
  });
  /**
   * Capitalize a string.
   */

  var capitalize = cached(function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  });
  /**
   * Hyphenate a camelCase string.
   */

  var hyphenateRE = /\B([A-Z])/g;
  var hyphenate = cached(function (str) {
    return str.replace(hyphenateRE, '-$1').toLowerCase();
  });
  /**
   * Simple bind polyfill for environments that do not support it... e.g.
   * PhantomJS 1.x. Technically we don't need this anymore since native bind is
   * now more performant in most browsers, but removing it would be breaking for
   * code that was able to run in PhantomJS 1.x, so this must be kept for
   * backwards compatibility.
   */

  /* istanbul ignore next */

  function polyfillBind(fn, ctx) {
    function boundFn(a) {
      var l = arguments.length;
      return l ? l > 1 ? fn.apply(ctx, arguments) : fn.call(ctx, a) : fn.call(ctx);
    }

    boundFn._length = fn.length;
    return boundFn;
  }

  function nativeBind(fn, ctx) {
    return fn.bind(ctx);
  }

  var bind = Function.prototype.bind ? nativeBind : polyfillBind;
  /**
   * Convert an Array-like object to a real Array.
   */

  function toArray(list, start) {
    start = start || 0;
    var i = list.length - start;
    var ret = new Array(i);

    while (i--) {
      ret[i] = list[i + start];
    }

    return ret;
  }
  /**
   * Mix properties into target object.
   */


  function extend(to, _from) {
    for (var key in _from) {
      to[key] = _from[key];
    }

    return to;
  }
  /**
   * Merge an Array of Objects into a single Object.
   */


  function toObject(arr) {
    var res = {};

    for (var i = 0; i < arr.length; i++) {
      if (arr[i]) {
        extend(res, arr[i]);
      }
    }

    return res;
  }
  /**
   * Perform no operation.
   * Stubbing args to make Flow happy without leaving useless transpiled code
   * with ...rest (https://flow.org/blog/2017/05/07/Strict-Function-Call-Arity/)
   */


  function noop(a, b, c) {}
  /**
   * Always return false.
   */


  var no = function no(a, b, c) {
    return false;
  };
  /**
   * Return same value
   */


  var identity = function identity(_) {
    return _;
  };
  /**
   * Generate a static keys string from compiler modules.
   */


  function genStaticKeys(modules) {
    return modules.reduce(function (keys, m) {
      return keys.concat(m.staticKeys || []);
    }, []).join(',');
  }
  /**
   * Check if two values are loosely equal - that is,
   * if they are plain objects, do they have the same shape?
   */


  function looseEqual(a, b) {
    if (a === b) {
      return true;
    }

    var isObjectA = isObject(a);
    var isObjectB = isObject(b);

    if (isObjectA && isObjectB) {
      try {
        var isArrayA = Array.isArray(a);
        var isArrayB = Array.isArray(b);

        if (isArrayA && isArrayB) {
          return a.length === b.length && a.every(function (e, i) {
            return looseEqual(e, b[i]);
          });
        } else if (!isArrayA && !isArrayB) {
          var keysA = Object.keys(a);
          var keysB = Object.keys(b);
          return keysA.length === keysB.length && keysA.every(function (key) {
            return looseEqual(a[key], b[key]);
          });
        } else {
          /* istanbul ignore next */
          return false;
        }
      } catch (e) {
        /* istanbul ignore next */
        return false;
      }
    } else if (!isObjectA && !isObjectB) {
      return String(a) === String(b);
    } else {
      return false;
    }
  }

  function looseIndexOf(arr, val) {
    for (var i = 0; i < arr.length; i++) {
      if (looseEqual(arr[i], val)) {
        return i;
      }
    }

    return -1;
  }
  /**
   * Ensure a function is called only once.
   */


  function once(fn) {
    var called = false;
    return function () {
      if (!called) {
        called = true;
        fn.apply(this, arguments);
      }
    };
  }

  var SSR_ATTR = 'data-server-rendered';
  var ASSET_TYPES = ['component', 'directive', 'filter'];
  var LIFECYCLE_HOOKS = ['beforeCreate', 'created', 'beforeMount', 'mounted', 'beforeUpdate', 'updated', 'beforeDestroy', 'destroyed', 'activated', 'deactivated', 'errorCaptured'];
  /*  */

  var config = {
    /**
     * Option merge strategies (used in core/util/options)
     */
    // $flow-disable-line
    optionMergeStrategies: Object.create(null),

    /**
     * Whether to suppress warnings.
     */
    silent: false,

    /**
     * Show production mode tip message on boot?
     */
    productionTip: "development" !== 'production',

    /**
     * Whether to enable devtools
     */
    devtools: "development" !== 'production',

    /**
     * Whether to record perf
     */
    performance: false,

    /**
     * Error handler for watcher errors
     */
    errorHandler: null,

    /**
     * Warn handler for watcher warns
     */
    warnHandler: null,

    /**
     * Ignore certain custom elements
     */
    ignoredElements: [],

    /**
     * Custom user key aliases for v-on
     */
    // $flow-disable-line
    keyCodes: Object.create(null),

    /**
     * Check if a tag is reserved so that it cannot be registered as a
     * component. This is platform-dependent and may be overwritten.
     */
    isReservedTag: no,

    /**
     * Check if an attribute is reserved so that it cannot be used as a component
     * prop. This is platform-dependent and may be overwritten.
     */
    isReservedAttr: no,

    /**
     * Check if a tag is an unknown element.
     * Platform-dependent.
     */
    isUnknownElement: no,

    /**
     * Get the namespace of an element
     */
    getTagNamespace: noop,

    /**
     * Parse the real tag name for the specific platform.
     */
    parsePlatformTagName: identity,

    /**
     * Check if an attribute must be bound using property, e.g. value
     * Platform-dependent.
     */
    mustUseProp: no,

    /**
     * Exposed for legacy reasons
     */
    _lifecycleHooks: LIFECYCLE_HOOKS
  };
  /*  */

  /**
   * Check if a string starts with $ or _
   */

  function isReserved(str) {
    var c = (str + '').charCodeAt(0);
    return c === 0x24 || c === 0x5F;
  }
  /**
   * Define a property.
   */


  function def(obj, key, val, enumerable) {
    Object.defineProperty(obj, key, {
      value: val,
      enumerable: !!enumerable,
      writable: true,
      configurable: true
    });
  }
  /**
   * Parse simple path.
   */


  var bailRE = /[^\w.$]/;

  function parsePath(path) {
    if (bailRE.test(path)) {
      return;
    }

    var segments = path.split('.');
    return function (obj) {
      for (var i = 0; i < segments.length; i++) {
        if (!obj) {
          return;
        }

        obj = obj[segments[i]];
      }

      return obj;
    };
  }
  /*  */
  // can we use __proto__?


  var hasProto = '__proto__' in {}; // Browser environment sniffing

  var inBrowser = typeof window !== 'undefined';
  var inWeex = typeof WXEnvironment !== 'undefined' && !!WXEnvironment.platform;
  var weexPlatform = inWeex && WXEnvironment.platform.toLowerCase();
  var UA = inBrowser && window.navigator.userAgent.toLowerCase();
  var isIE = UA && /msie|trident/.test(UA);
  var isIE9 = UA && UA.indexOf('msie 9.0') > 0;
  var isEdge = UA && UA.indexOf('edge/') > 0;
  var isAndroid = UA && UA.indexOf('android') > 0 || weexPlatform === 'android';
  var isIOS = UA && /iphone|ipad|ipod|ios/.test(UA) || weexPlatform === 'ios';
  var isChrome = UA && /chrome\/\d+/.test(UA) && !isEdge; // Firefox has a "watch" function on Object.prototype...

  var nativeWatch = {}.watch;
  var supportsPassive = false;

  if (inBrowser) {
    try {
      var opts = {};
      Object.defineProperty(opts, 'passive', {
        get: function get() {
          /* istanbul ignore next */
          supportsPassive = true;
        }
      }); // https://github.com/facebook/flow/issues/285

      window.addEventListener('test-passive', null, opts);
    } catch (e) {}
  } // this needs to be lazy-evaled because vue may be required before
  // vue-server-renderer can set VUE_ENV


  var _isServer;

  var isServerRendering = function isServerRendering() {
    if (_isServer === undefined) {
      /* istanbul ignore if */
      if (!inBrowser && !inWeex && typeof global !== 'undefined') {
        // detect presence of vue-server-renderer and avoid
        // Webpack shimming the process
        _isServer = global['process'].env.VUE_ENV === 'server';
      } else {
        _isServer = false;
      }
    }

    return _isServer;
  }; // detect devtools


  var devtools = inBrowser && window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
  /* istanbul ignore next */

  function isNative(Ctor) {
    return typeof Ctor === 'function' && /native code/.test(Ctor.toString());
  }

  var hasSymbol = typeof Symbol !== 'undefined' && isNative(Symbol) && typeof Reflect !== 'undefined' && isNative(Reflect.ownKeys);

  var _Set;
  /* istanbul ignore if */
  // $flow-disable-line


  if (typeof Set !== 'undefined' && isNative(Set)) {
    // use native Set when available.
    _Set = Set;
  } else {
    // a non-standard Set polyfill that only works with primitive keys.
    _Set = function () {
      function Set() {
        this.set = Object.create(null);
      }

      Set.prototype.has = function has(key) {
        return this.set[key] === true;
      };

      Set.prototype.add = function add(key) {
        this.set[key] = true;
      };

      Set.prototype.clear = function clear() {
        this.set = Object.create(null);
      };

      return Set;
    }();
  }
  /*  */


  var warn = noop;
  var tip = noop;
  var generateComponentTrace = noop; // work around flow check

  var formatComponentName = noop;
  {
    var hasConsole = typeof console !== 'undefined';
    var classifyRE = /(?:^|[-_])(\w)/g;

    var classify = function classify(str) {
      return str.replace(classifyRE, function (c) {
        return c.toUpperCase();
      }).replace(/[-_]/g, '');
    };

    warn = function warn(msg, vm) {
      var trace = vm ? generateComponentTrace(vm) : '';

      if (config.warnHandler) {
        config.warnHandler.call(null, msg, vm, trace);
      } else if (hasConsole && !config.silent) {
        console.error("[Vue warn]: " + msg + trace);
      }
    };

    tip = function tip(msg, vm) {
      if (hasConsole && !config.silent) {
        console.warn("[Vue tip]: " + msg + (vm ? generateComponentTrace(vm) : ''));
      }
    };

    formatComponentName = function formatComponentName(vm, includeFile) {
      if (vm.$root === vm) {
        return '<Root>';
      }

      var options = typeof vm === 'function' && vm.cid != null ? vm.options : vm._isVue ? vm.$options || vm.constructor.options : vm || {};
      var name = options.name || options._componentTag;
      var file = options.__file;

      if (!name && file) {
        var match = file.match(/([^/\\]+)\.vue$/);
        name = match && match[1];
      }

      return (name ? "<" + classify(name) + ">" : "<Anonymous>") + (file && includeFile !== false ? " at " + file : '');
    };

    var repeat = function repeat(str, n) {
      var res = '';

      while (n) {
        if (n % 2 === 1) {
          res += str;
        }

        if (n > 1) {
          str += str;
        }

        n >>= 1;
      }

      return res;
    };

    generateComponentTrace = function generateComponentTrace(vm) {
      if (vm._isVue && vm.$parent) {
        var tree = [];
        var currentRecursiveSequence = 0;

        while (vm) {
          if (tree.length > 0) {
            var last = tree[tree.length - 1];

            if (last.constructor === vm.constructor) {
              currentRecursiveSequence++;
              vm = vm.$parent;
              continue;
            } else if (currentRecursiveSequence > 0) {
              tree[tree.length - 1] = [last, currentRecursiveSequence];
              currentRecursiveSequence = 0;
            }
          }

          tree.push(vm);
          vm = vm.$parent;
        }

        return '\n\nfound in\n\n' + tree.map(function (vm, i) {
          return "" + (i === 0 ? '---> ' : repeat(' ', 5 + i * 2)) + (Array.isArray(vm) ? formatComponentName(vm[0]) + "... (" + vm[1] + " recursive calls)" : formatComponentName(vm));
        }).join('\n');
      } else {
        return "\n\n(found in " + formatComponentName(vm) + ")";
      }
    };
  }
  /*  */

  var uid = 0;
  /**
   * A dep is an observable that can have multiple
   * directives subscribing to it.
   */

  var Dep = function Dep() {
    this.id = uid++;
    this.subs = [];
  };

  Dep.prototype.addSub = function addSub(sub) {
    this.subs.push(sub);
  };

  Dep.prototype.removeSub = function removeSub(sub) {
    remove(this.subs, sub);
  };

  Dep.prototype.depend = function depend() {
    if (Dep.target) {
      Dep.target.addDep(this);
    }
  };

  Dep.prototype.notify = function notify() {
    // stabilize the subscriber list first
    var subs = this.subs.slice();

    for (var i = 0, l = subs.length; i < l; i++) {
      subs[i].update();
    }
  }; // the current target watcher being evaluated.
  // this is globally unique because there could be only one
  // watcher being evaluated at any time.


  Dep.target = null;
  var targetStack = [];

  function pushTarget(_target) {
    if (Dep.target) {
      targetStack.push(Dep.target);
    }

    Dep.target = _target;
  }

  function popTarget() {
    Dep.target = targetStack.pop();
  }
  /*  */


  var VNode = function VNode(tag, data, children, text, elm, context, componentOptions, asyncFactory) {
    this.tag = tag;
    this.data = data;
    this.children = children;
    this.text = text;
    this.elm = elm;
    this.ns = undefined;
    this.context = context;
    this.fnContext = undefined;
    this.fnOptions = undefined;
    this.fnScopeId = undefined;
    this.key = data && data.key;
    this.componentOptions = componentOptions;
    this.componentInstance = undefined;
    this.parent = undefined;
    this.raw = false;
    this.isStatic = false;
    this.isRootInsert = true;
    this.isComment = false;
    this.isCloned = false;
    this.isOnce = false;
    this.asyncFactory = asyncFactory;
    this.asyncMeta = undefined;
    this.isAsyncPlaceholder = false;
  };

  var prototypeAccessors = {
    child: {
      configurable: true
    }
  }; // DEPRECATED: alias for componentInstance for backwards compat.

  /* istanbul ignore next */

  prototypeAccessors.child.get = function () {
    return this.componentInstance;
  };

  Object.defineProperties(VNode.prototype, prototypeAccessors);

  var createEmptyVNode = function createEmptyVNode(text) {
    if (text === void 0) text = '';
    var node = new VNode();
    node.text = text;
    node.isComment = true;
    return node;
  };

  function createTextVNode(val) {
    return new VNode(undefined, undefined, undefined, String(val));
  } // optimized shallow clone
  // used for static nodes and slot nodes because they may be reused across
  // multiple renders, cloning them avoids errors when DOM manipulations rely
  // on their elm reference.


  function cloneVNode(vnode) {
    var cloned = new VNode(vnode.tag, vnode.data, vnode.children, vnode.text, vnode.elm, vnode.context, vnode.componentOptions, vnode.asyncFactory);
    cloned.ns = vnode.ns;
    cloned.isStatic = vnode.isStatic;
    cloned.key = vnode.key;
    cloned.isComment = vnode.isComment;
    cloned.fnContext = vnode.fnContext;
    cloned.fnOptions = vnode.fnOptions;
    cloned.fnScopeId = vnode.fnScopeId;
    cloned.isCloned = true;
    return cloned;
  }
  /*
   * not type checking this file because flow doesn't play well with
   * dynamically accessing methods on Array prototype
   */


  var arrayProto = Array.prototype;
  var arrayMethods = Object.create(arrayProto);
  var methodsToPatch = ['push', 'pop', 'shift', 'unshift', 'splice', 'sort', 'reverse'];
  /**
   * Intercept mutating methods and emit events
   */

  methodsToPatch.forEach(function (method) {
    // cache original method
    var original = arrayProto[method];
    def(arrayMethods, method, function mutator() {
      var args = [],
          len = arguments.length;

      while (len--) {
        args[len] = arguments[len];
      }

      var result = original.apply(this, args);
      var ob = this.__ob__;
      var inserted;

      switch (method) {
        case 'push':
        case 'unshift':
          inserted = args;
          break;

        case 'splice':
          inserted = args.slice(2);
          break;
      }

      if (inserted) {
        ob.observeArray(inserted);
      } // notify change


      ob.dep.notify();
      return result;
    });
  });
  /*  */

  var arrayKeys = Object.getOwnPropertyNames(arrayMethods);
  /**
   * In some cases we may want to disable observation inside a component's
   * update computation.
   */

  var shouldObserve = true;

  function toggleObserving(value) {
    shouldObserve = value;
  }
  /**
   * Observer class that is attached to each observed
   * object. Once attached, the observer converts the target
   * object's property keys into getter/setters that
   * collect dependencies and dispatch updates.
   */


  var Observer = function Observer(value) {
    this.value = value;
    this.dep = new Dep();
    this.vmCount = 0;
    def(value, '__ob__', this);

    if (Array.isArray(value)) {
      var augment = hasProto ? protoAugment : copyAugment;
      augment(value, arrayMethods, arrayKeys);
      this.observeArray(value);
    } else {
      this.walk(value);
    }
  };
  /**
   * Walk through each property and convert them into
   * getter/setters. This method should only be called when
   * value type is Object.
   */


  Observer.prototype.walk = function walk(obj) {
    var keys = Object.keys(obj);

    for (var i = 0; i < keys.length; i++) {
      defineReactive(obj, keys[i]);
    }
  };
  /**
   * Observe a list of Array items.
   */


  Observer.prototype.observeArray = function observeArray(items) {
    for (var i = 0, l = items.length; i < l; i++) {
      observe(items[i]);
    }
  }; // helpers

  /**
   * Augment an target Object or Array by intercepting
   * the prototype chain using __proto__
   */


  function protoAugment(target, src, keys) {
    /* eslint-disable no-proto */
    target.__proto__ = src;
    /* eslint-enable no-proto */
  }
  /**
   * Augment an target Object or Array by defining
   * hidden properties.
   */

  /* istanbul ignore next */


  function copyAugment(target, src, keys) {
    for (var i = 0, l = keys.length; i < l; i++) {
      var key = keys[i];
      def(target, key, src[key]);
    }
  }
  /**
   * Attempt to create an observer instance for a value,
   * returns the new observer if successfully observed,
   * or the existing observer if the value already has one.
   */


  function observe(value, asRootData) {
    if (!isObject(value) || value instanceof VNode) {
      return;
    }

    var ob;

    if (hasOwn(value, '__ob__') && value.__ob__ instanceof Observer) {
      ob = value.__ob__;
    } else if (shouldObserve && !isServerRendering() && (Array.isArray(value) || isPlainObject(value)) && Object.isExtensible(value) && !value._isVue) {
      ob = new Observer(value);
    }

    if (asRootData && ob) {
      ob.vmCount++;
    }

    return ob;
  }
  /**
   * Define a reactive property on an Object.
   */


  function defineReactive(obj, key, val, customSetter, shallow) {
    var dep = new Dep();
    var property = Object.getOwnPropertyDescriptor(obj, key);

    if (property && property.configurable === false) {
      return;
    } // cater for pre-defined getter/setters


    var getter = property && property.get;

    if (!getter && arguments.length === 2) {
      val = obj[key];
    }

    var setter = property && property.set;
    var childOb = !shallow && observe(val);
    Object.defineProperty(obj, key, {
      enumerable: true,
      configurable: true,
      get: function reactiveGetter() {
        var value = getter ? getter.call(obj) : val;

        if (Dep.target) {
          dep.depend();

          if (childOb) {
            childOb.dep.depend();

            if (Array.isArray(value)) {
              dependArray(value);
            }
          }
        }

        return value;
      },
      set: function reactiveSetter(newVal) {
        var value = getter ? getter.call(obj) : val;
        /* eslint-disable no-self-compare */

        if (newVal === value || newVal !== newVal && value !== value) {
          return;
        }
        /* eslint-enable no-self-compare */


        if ("development" !== 'production' && customSetter) {
          customSetter();
        }

        if (setter) {
          setter.call(obj, newVal);
        } else {
          val = newVal;
        }

        childOb = !shallow && observe(newVal);
        dep.notify();
      }
    });
  }
  /**
   * Set a property on an object. Adds the new property and
   * triggers change notification if the property doesn't
   * already exist.
   */


  function set(target, key, val) {
    if ("development" !== 'production' && (isUndef(target) || isPrimitive(target))) {
      warn("Cannot set reactive property on undefined, null, or primitive value: " + target);
    }

    if (Array.isArray(target) && isValidArrayIndex(key)) {
      target.length = Math.max(target.length, key);
      target.splice(key, 1, val);
      return val;
    }

    if (key in target && !(key in Object.prototype)) {
      target[key] = val;
      return val;
    }

    var ob = target.__ob__;

    if (target._isVue || ob && ob.vmCount) {
      "development" !== 'production' && warn('Avoid adding reactive properties to a Vue instance or its root $data ' + 'at runtime - declare it upfront in the data option.');
      return val;
    }

    if (!ob) {
      target[key] = val;
      return val;
    }

    defineReactive(ob.value, key, val);
    ob.dep.notify();
    return val;
  }
  /**
   * Delete a property and trigger change if necessary.
   */


  function del(target, key) {
    if ("development" !== 'production' && (isUndef(target) || isPrimitive(target))) {
      warn("Cannot delete reactive property on undefined, null, or primitive value: " + target);
    }

    if (Array.isArray(target) && isValidArrayIndex(key)) {
      target.splice(key, 1);
      return;
    }

    var ob = target.__ob__;

    if (target._isVue || ob && ob.vmCount) {
      "development" !== 'production' && warn('Avoid deleting properties on a Vue instance or its root $data ' + '- just set it to null.');
      return;
    }

    if (!hasOwn(target, key)) {
      return;
    }

    delete target[key];

    if (!ob) {
      return;
    }

    ob.dep.notify();
  }
  /**
   * Collect dependencies on array elements when the array is touched, since
   * we cannot intercept array element access like property getters.
   */


  function dependArray(value) {
    for (var e = void 0, i = 0, l = value.length; i < l; i++) {
      e = value[i];
      e && e.__ob__ && e.__ob__.dep.depend();

      if (Array.isArray(e)) {
        dependArray(e);
      }
    }
  }
  /*  */

  /**
   * Option overwriting strategies are functions that handle
   * how to merge a parent option value and a child option
   * value into the final value.
   */


  var strats = config.optionMergeStrategies;
  /**
   * Options with restrictions
   */

  {
    strats.el = strats.propsData = function (parent, child, vm, key) {
      if (!vm) {
        warn("option \"" + key + "\" can only be used during instance " + 'creation with the `new` keyword.');
      }

      return defaultStrat(parent, child);
    };
  }
  /**
   * Helper that recursively merges two data objects together.
   */

  function mergeData(to, from) {
    if (!from) {
      return to;
    }

    var key, toVal, fromVal;
    var keys = Object.keys(from);

    for (var i = 0; i < keys.length; i++) {
      key = keys[i];
      toVal = to[key];
      fromVal = from[key];

      if (!hasOwn(to, key)) {
        set(to, key, fromVal);
      } else if (isPlainObject(toVal) && isPlainObject(fromVal)) {
        mergeData(toVal, fromVal);
      }
    }

    return to;
  }
  /**
   * Data
   */


  function mergeDataOrFn(parentVal, childVal, vm) {
    if (!vm) {
      // in a Vue.extend merge, both should be functions
      if (!childVal) {
        return parentVal;
      }

      if (!parentVal) {
        return childVal;
      } // when parentVal & childVal are both present,
      // we need to return a function that returns the
      // merged result of both functions... no need to
      // check if parentVal is a function here because
      // it has to be a function to pass previous merges.


      return function mergedDataFn() {
        return mergeData(typeof childVal === 'function' ? childVal.call(this, this) : childVal, typeof parentVal === 'function' ? parentVal.call(this, this) : parentVal);
      };
    } else {
      return function mergedInstanceDataFn() {
        // instance merge
        var instanceData = typeof childVal === 'function' ? childVal.call(vm, vm) : childVal;
        var defaultData = typeof parentVal === 'function' ? parentVal.call(vm, vm) : parentVal;

        if (instanceData) {
          return mergeData(instanceData, defaultData);
        } else {
          return defaultData;
        }
      };
    }
  }

  strats.data = function (parentVal, childVal, vm) {
    if (!vm) {
      if (childVal && typeof childVal !== 'function') {
        "development" !== 'production' && warn('The "data" option should be a function ' + 'that returns a per-instance value in component ' + 'definitions.', vm);
        return parentVal;
      }

      return mergeDataOrFn(parentVal, childVal);
    }

    return mergeDataOrFn(parentVal, childVal, vm);
  };
  /**
   * Hooks and props are merged as arrays.
   */


  function mergeHook(parentVal, childVal) {
    return childVal ? parentVal ? parentVal.concat(childVal) : Array.isArray(childVal) ? childVal : [childVal] : parentVal;
  }

  LIFECYCLE_HOOKS.forEach(function (hook) {
    strats[hook] = mergeHook;
  });
  /**
   * Assets
   *
   * When a vm is present (instance creation), we need to do
   * a three-way merge between constructor options, instance
   * options and parent options.
   */

  function mergeAssets(parentVal, childVal, vm, key) {
    var res = Object.create(parentVal || null);

    if (childVal) {
      "development" !== 'production' && assertObjectType(key, childVal, vm);
      return extend(res, childVal);
    } else {
      return res;
    }
  }

  ASSET_TYPES.forEach(function (type) {
    strats[type + 's'] = mergeAssets;
  });
  /**
   * Watchers.
   *
   * Watchers hashes should not overwrite one
   * another, so we merge them as arrays.
   */

  strats.watch = function (parentVal, childVal, vm, key) {
    // work around Firefox's Object.prototype.watch...
    if (parentVal === nativeWatch) {
      parentVal = undefined;
    }

    if (childVal === nativeWatch) {
      childVal = undefined;
    }
    /* istanbul ignore if */


    if (!childVal) {
      return Object.create(parentVal || null);
    }

    {
      assertObjectType(key, childVal, vm);
    }

    if (!parentVal) {
      return childVal;
    }

    var ret = {};
    extend(ret, parentVal);

    for (var key$1 in childVal) {
      var parent = ret[key$1];
      var child = childVal[key$1];

      if (parent && !Array.isArray(parent)) {
        parent = [parent];
      }

      ret[key$1] = parent ? parent.concat(child) : Array.isArray(child) ? child : [child];
    }

    return ret;
  };
  /**
   * Other object hashes.
   */


  strats.props = strats.methods = strats.inject = strats.computed = function (parentVal, childVal, vm, key) {
    if (childVal && "development" !== 'production') {
      assertObjectType(key, childVal, vm);
    }

    if (!parentVal) {
      return childVal;
    }

    var ret = Object.create(null);
    extend(ret, parentVal);

    if (childVal) {
      extend(ret, childVal);
    }

    return ret;
  };

  strats.provide = mergeDataOrFn;
  /**
   * Default strategy.
   */

  var defaultStrat = function defaultStrat(parentVal, childVal) {
    return childVal === undefined ? parentVal : childVal;
  };
  /**
   * Validate component names
   */


  function checkComponents(options) {
    for (var key in options.components) {
      validateComponentName(key);
    }
  }

  function validateComponentName(name) {
    if (!/^[a-zA-Z][\w-]*$/.test(name)) {
      warn('Invalid component name: "' + name + '". Component names ' + 'can only contain alphanumeric characters and the hyphen, ' + 'and must start with a letter.');
    }

    if (isBuiltInTag(name) || config.isReservedTag(name)) {
      warn('Do not use built-in or reserved HTML elements as component ' + 'id: ' + name);
    }
  }
  /**
   * Ensure all props option syntax are normalized into the
   * Object-based format.
   */


  function normalizeProps(options, vm) {
    var props = options.props;

    if (!props) {
      return;
    }

    var res = {};
    var i, val, name;

    if (Array.isArray(props)) {
      i = props.length;

      while (i--) {
        val = props[i];

        if (typeof val === 'string') {
          name = camelize(val);
          res[name] = {
            type: null
          };
        } else {
          warn('props must be strings when using array syntax.');
        }
      }
    } else if (isPlainObject(props)) {
      for (var key in props) {
        val = props[key];
        name = camelize(key);
        res[name] = isPlainObject(val) ? val : {
          type: val
        };
      }
    } else {
      warn("Invalid value for option \"props\": expected an Array or an Object, " + "but got " + toRawType(props) + ".", vm);
    }

    options.props = res;
  }
  /**
   * Normalize all injections into Object-based format
   */


  function normalizeInject(options, vm) {
    var inject = options.inject;

    if (!inject) {
      return;
    }

    var normalized = options.inject = {};

    if (Array.isArray(inject)) {
      for (var i = 0; i < inject.length; i++) {
        normalized[inject[i]] = {
          from: inject[i]
        };
      }
    } else if (isPlainObject(inject)) {
      for (var key in inject) {
        var val = inject[key];
        normalized[key] = isPlainObject(val) ? extend({
          from: key
        }, val) : {
          from: val
        };
      }
    } else {
      warn("Invalid value for option \"inject\": expected an Array or an Object, " + "but got " + toRawType(inject) + ".", vm);
    }
  }
  /**
   * Normalize raw function directives into object format.
   */


  function normalizeDirectives(options) {
    var dirs = options.directives;

    if (dirs) {
      for (var key in dirs) {
        var def = dirs[key];

        if (typeof def === 'function') {
          dirs[key] = {
            bind: def,
            update: def
          };
        }
      }
    }
  }

  function assertObjectType(name, value, vm) {
    if (!isPlainObject(value)) {
      warn("Invalid value for option \"" + name + "\": expected an Object, " + "but got " + toRawType(value) + ".", vm);
    }
  }
  /**
   * Merge two option objects into a new one.
   * Core utility used in both instantiation and inheritance.
   */


  function mergeOptions(parent, child, vm) {
    {
      checkComponents(child);
    }

    if (typeof child === 'function') {
      child = child.options;
    }

    normalizeProps(child, vm);
    normalizeInject(child, vm);
    normalizeDirectives(child);
    var extendsFrom = child.extends;

    if (extendsFrom) {
      parent = mergeOptions(parent, extendsFrom, vm);
    }

    if (child.mixins) {
      for (var i = 0, l = child.mixins.length; i < l; i++) {
        parent = mergeOptions(parent, child.mixins[i], vm);
      }
    }

    var options = {};
    var key;

    for (key in parent) {
      mergeField(key);
    }

    for (key in child) {
      if (!hasOwn(parent, key)) {
        mergeField(key);
      }
    }

    function mergeField(key) {
      var strat = strats[key] || defaultStrat;
      options[key] = strat(parent[key], child[key], vm, key);
    }

    return options;
  }
  /**
   * Resolve an asset.
   * This function is used because child instances need access
   * to assets defined in its ancestor chain.
   */


  function resolveAsset(options, type, id, warnMissing) {
    /* istanbul ignore if */
    if (typeof id !== 'string') {
      return;
    }

    var assets = options[type]; // check local registration variations first

    if (hasOwn(assets, id)) {
      return assets[id];
    }

    var camelizedId = camelize(id);

    if (hasOwn(assets, camelizedId)) {
      return assets[camelizedId];
    }

    var PascalCaseId = capitalize(camelizedId);

    if (hasOwn(assets, PascalCaseId)) {
      return assets[PascalCaseId];
    } // fallback to prototype chain


    var res = assets[id] || assets[camelizedId] || assets[PascalCaseId];

    if ("development" !== 'production' && warnMissing && !res) {
      warn('Failed to resolve ' + type.slice(0, -1) + ': ' + id, options);
    }

    return res;
  }
  /*  */


  function validateProp(key, propOptions, propsData, vm) {
    var prop = propOptions[key];
    var absent = !hasOwn(propsData, key);
    var value = propsData[key]; // boolean casting

    var booleanIndex = getTypeIndex(Boolean, prop.type);

    if (booleanIndex > -1) {
      if (absent && !hasOwn(prop, 'default')) {
        value = false;
      } else if (value === '' || value === hyphenate(key)) {
        // only cast empty string / same name to boolean if
        // boolean has higher priority
        var stringIndex = getTypeIndex(String, prop.type);

        if (stringIndex < 0 || booleanIndex < stringIndex) {
          value = true;
        }
      }
    } // check default value


    if (value === undefined) {
      value = getPropDefaultValue(vm, prop, key); // since the default value is a fresh copy,
      // make sure to observe it.

      var prevShouldObserve = shouldObserve;
      toggleObserving(true);
      observe(value);
      toggleObserving(prevShouldObserve);
    }

    {
      assertProp(prop, key, value, vm, absent);
    }
    return value;
  }
  /**
   * Get the default value of a prop.
   */


  function getPropDefaultValue(vm, prop, key) {
    // no default, return undefined
    if (!hasOwn(prop, 'default')) {
      return undefined;
    }

    var def = prop.default; // warn against non-factory defaults for Object & Array

    if ("development" !== 'production' && isObject(def)) {
      warn('Invalid default value for prop "' + key + '": ' + 'Props with type Object/Array must use a factory function ' + 'to return the default value.', vm);
    } // the raw prop value was also undefined from previous render,
    // return previous default value to avoid unnecessary watcher trigger


    if (vm && vm.$options.propsData && vm.$options.propsData[key] === undefined && vm._props[key] !== undefined) {
      return vm._props[key];
    } // call factory function for non-Function types
    // a value is Function if its prototype is function even across different execution context


    return typeof def === 'function' && getType(prop.type) !== 'Function' ? def.call(vm) : def;
  }
  /**
   * Assert whether a prop is valid.
   */


  function assertProp(prop, name, value, vm, absent) {
    if (prop.required && absent) {
      warn('Missing required prop: "' + name + '"', vm);
      return;
    }

    if (value == null && !prop.required) {
      return;
    }

    var type = prop.type;
    var valid = !type || type === true;
    var expectedTypes = [];

    if (type) {
      if (!Array.isArray(type)) {
        type = [type];
      }

      for (var i = 0; i < type.length && !valid; i++) {
        var assertedType = assertType(value, type[i]);
        expectedTypes.push(assertedType.expectedType || '');
        valid = assertedType.valid;
      }
    }

    if (!valid) {
      warn("Invalid prop: type check failed for prop \"" + name + "\"." + " Expected " + expectedTypes.map(capitalize).join(', ') + ", got " + toRawType(value) + ".", vm);
      return;
    }

    var validator = prop.validator;

    if (validator) {
      if (!validator(value)) {
        warn('Invalid prop: custom validator check failed for prop "' + name + '".', vm);
      }
    }
  }

  var simpleCheckRE = /^(String|Number|Boolean|Function|Symbol)$/;

  function assertType(value, type) {
    var valid;
    var expectedType = getType(type);

    if (simpleCheckRE.test(expectedType)) {
      var t = _typeof(value);

      valid = t === expectedType.toLowerCase(); // for primitive wrapper objects

      if (!valid && t === 'object') {
        valid = value instanceof type;
      }
    } else if (expectedType === 'Object') {
      valid = isPlainObject(value);
    } else if (expectedType === 'Array') {
      valid = Array.isArray(value);
    } else {
      valid = value instanceof type;
    }

    return {
      valid: valid,
      expectedType: expectedType
    };
  }
  /**
   * Use function string name to check built-in types,
   * because a simple equality check will fail when running
   * across different vms / iframes.
   */


  function getType(fn) {
    var match = fn && fn.toString().match(/^\s*function (\w+)/);
    return match ? match[1] : '';
  }

  function isSameType(a, b) {
    return getType(a) === getType(b);
  }

  function getTypeIndex(type, expectedTypes) {
    if (!Array.isArray(expectedTypes)) {
      return isSameType(expectedTypes, type) ? 0 : -1;
    }

    for (var i = 0, len = expectedTypes.length; i < len; i++) {
      if (isSameType(expectedTypes[i], type)) {
        return i;
      }
    }

    return -1;
  }
  /*  */


  function handleError(err, vm, info) {
    if (vm) {
      var cur = vm;

      while (cur = cur.$parent) {
        var hooks = cur.$options.errorCaptured;

        if (hooks) {
          for (var i = 0; i < hooks.length; i++) {
            try {
              var capture = hooks[i].call(cur, err, vm, info) === false;

              if (capture) {
                return;
              }
            } catch (e) {
              globalHandleError(e, cur, 'errorCaptured hook');
            }
          }
        }
      }
    }

    globalHandleError(err, vm, info);
  }

  function globalHandleError(err, vm, info) {
    if (config.errorHandler) {
      try {
        return config.errorHandler.call(null, err, vm, info);
      } catch (e) {
        logError(e, null, 'config.errorHandler');
      }
    }

    logError(err, vm, info);
  }

  function logError(err, vm, info) {
    {
      warn("Error in " + info + ": \"" + err.toString() + "\"", vm);
    }
    /* istanbul ignore else */

    if ((inBrowser || inWeex) && typeof console !== 'undefined') {
      console.error(err);
    } else {
      throw err;
    }
  }
  /*  */

  /* globals MessageChannel */


  var callbacks = [];
  var pending = false;

  function flushCallbacks() {
    pending = false;
    var copies = callbacks.slice(0);
    callbacks.length = 0;

    for (var i = 0; i < copies.length; i++) {
      copies[i]();
    }
  } // Here we have async deferring wrappers using both microtasks and (macro) tasks.
  // In < 2.4 we used microtasks everywhere, but there are some scenarios where
  // microtasks have too high a priority and fire in between supposedly
  // sequential events (e.g. #4521, #6690) or even between bubbling of the same
  // event (#6566). However, using (macro) tasks everywhere also has subtle problems
  // when state is changed right before repaint (e.g. #6813, out-in transitions).
  // Here we use microtask by default, but expose a way to force (macro) task when
  // needed (e.g. in event handlers attached by v-on).


  var microTimerFunc;
  var macroTimerFunc;
  var useMacroTask = false; // Determine (macro) task defer implementation.
  // Technically setImmediate should be the ideal choice, but it's only available
  // in IE. The only polyfill that consistently queues the callback after all DOM
  // events triggered in the same loop is by using MessageChannel.

  /* istanbul ignore if */

  if (typeof setImmediate !== 'undefined' && isNative(setImmediate)) {
    macroTimerFunc = function macroTimerFunc() {
      setImmediate(flushCallbacks);
    };
  } else if (typeof MessageChannel !== 'undefined' && (isNative(MessageChannel) || // PhantomJS
  MessageChannel.toString() === '[object MessageChannelConstructor]')) {
    var channel = new MessageChannel();
    var port = channel.port2;
    channel.port1.onmessage = flushCallbacks;

    macroTimerFunc = function macroTimerFunc() {
      port.postMessage(1);
    };
  } else {
    /* istanbul ignore next */
    macroTimerFunc = function macroTimerFunc() {
      setTimeout(flushCallbacks, 0);
    };
  } // Determine microtask defer implementation.

  /* istanbul ignore next, $flow-disable-line */


  if (typeof Promise !== 'undefined' && isNative(Promise)) {
    var p = Promise.resolve();

    microTimerFunc = function microTimerFunc() {
      p.then(flushCallbacks); // in problematic UIWebViews, Promise.then doesn't completely break, but
      // it can get stuck in a weird state where callbacks are pushed into the
      // microtask queue but the queue isn't being flushed, until the browser
      // needs to do some other work, e.g. handle a timer. Therefore we can
      // "force" the microtask queue to be flushed by adding an empty timer.

      if (isIOS) {
        setTimeout(noop);
      }
    };
  } else {
    // fallback to macro
    microTimerFunc = macroTimerFunc;
  }
  /**
   * Wrap a function so that if any code inside triggers state change,
   * the changes are queued using a (macro) task instead of a microtask.
   */


  function withMacroTask(fn) {
    return fn._withTask || (fn._withTask = function () {
      useMacroTask = true;
      var res = fn.apply(null, arguments);
      useMacroTask = false;
      return res;
    });
  }

  function nextTick(cb, ctx) {
    var _resolve;

    callbacks.push(function () {
      if (cb) {
        try {
          cb.call(ctx);
        } catch (e) {
          handleError(e, ctx, 'nextTick');
        }
      } else if (_resolve) {
        _resolve(ctx);
      }
    });

    if (!pending) {
      pending = true;

      if (useMacroTask) {
        macroTimerFunc();
      } else {
        microTimerFunc();
      }
    } // $flow-disable-line


    if (!cb && typeof Promise !== 'undefined') {
      return new Promise(function (resolve) {
        _resolve = resolve;
      });
    }
  }
  /*  */


  var mark;
  var measure;
  {
    var perf = inBrowser && window.performance;
    /* istanbul ignore if */

    if (perf && perf.mark && perf.measure && perf.clearMarks && perf.clearMeasures) {
      mark = function mark(tag) {
        return perf.mark(tag);
      };

      measure = function measure(name, startTag, endTag) {
        perf.measure(name, startTag, endTag);
        perf.clearMarks(startTag);
        perf.clearMarks(endTag);
        perf.clearMeasures(name);
      };
    }
  }
  /* not type checking this file because flow doesn't play well with Proxy */

  var initProxy;
  {
    var allowedGlobals = makeMap('Infinity,undefined,NaN,isFinite,isNaN,' + 'parseFloat,parseInt,decodeURI,decodeURIComponent,encodeURI,encodeURIComponent,' + 'Math,Number,Date,Array,Object,Boolean,String,RegExp,Map,Set,JSON,Intl,' + 'require' // for Webpack/Browserify
    );

    var warnNonPresent = function warnNonPresent(target, key) {
      warn("Property or method \"" + key + "\" is not defined on the instance but " + 'referenced during render. Make sure that this property is reactive, ' + 'either in the data option, or for class-based components, by ' + 'initializing the property. ' + 'See: https://vuejs.org/v2/guide/reactivity.html#Declaring-Reactive-Properties.', target);
    };

    var hasProxy = typeof Proxy !== 'undefined' && isNative(Proxy);

    if (hasProxy) {
      var isBuiltInModifier = makeMap('stop,prevent,self,ctrl,shift,alt,meta,exact');
      config.keyCodes = new Proxy(config.keyCodes, {
        set: function set(target, key, value) {
          if (isBuiltInModifier(key)) {
            warn("Avoid overwriting built-in modifier in config.keyCodes: ." + key);
            return false;
          } else {
            target[key] = value;
            return true;
          }
        }
      });
    }

    var hasHandler = {
      has: function has(target, key) {
        var has = key in target;
        var isAllowed = allowedGlobals(key) || key.charAt(0) === '_';

        if (!has && !isAllowed) {
          warnNonPresent(target, key);
        }

        return has || !isAllowed;
      }
    };
    var getHandler = {
      get: function get(target, key) {
        if (typeof key === 'string' && !(key in target)) {
          warnNonPresent(target, key);
        }

        return target[key];
      }
    };

    initProxy = function initProxy(vm) {
      if (hasProxy) {
        // determine which proxy handler to use
        var options = vm.$options;
        var handlers = options.render && options.render._withStripped ? getHandler : hasHandler;
        vm._renderProxy = new Proxy(vm, handlers);
      } else {
        vm._renderProxy = vm;
      }
    };
  }
  /*  */

  var seenObjects = new _Set();
  /**
   * Recursively traverse an object to evoke all converted
   * getters, so that every nested property inside the object
   * is collected as a "deep" dependency.
   */

  function traverse(val) {
    _traverse(val, seenObjects);

    seenObjects.clear();
  }

  function _traverse(val, seen) {
    var i, keys;
    var isA = Array.isArray(val);

    if (!isA && !isObject(val) || Object.isFrozen(val) || val instanceof VNode) {
      return;
    }

    if (val.__ob__) {
      var depId = val.__ob__.dep.id;

      if (seen.has(depId)) {
        return;
      }

      seen.add(depId);
    }

    if (isA) {
      i = val.length;

      while (i--) {
        _traverse(val[i], seen);
      }
    } else {
      keys = Object.keys(val);
      i = keys.length;

      while (i--) {
        _traverse(val[keys[i]], seen);
      }
    }
  }
  /*  */


  var normalizeEvent = cached(function (name) {
    var passive = name.charAt(0) === '&';
    name = passive ? name.slice(1) : name;
    var once$$1 = name.charAt(0) === '~'; // Prefixed last, checked first

    name = once$$1 ? name.slice(1) : name;
    var capture = name.charAt(0) === '!';
    name = capture ? name.slice(1) : name;
    return {
      name: name,
      once: once$$1,
      capture: capture,
      passive: passive
    };
  });

  function createFnInvoker(fns) {
    function invoker() {
      var arguments$1 = arguments;
      var fns = invoker.fns;

      if (Array.isArray(fns)) {
        var cloned = fns.slice();

        for (var i = 0; i < cloned.length; i++) {
          cloned[i].apply(null, arguments$1);
        }
      } else {
        // return handler return value for single handlers
        return fns.apply(null, arguments);
      }
    }

    invoker.fns = fns;
    return invoker;
  }

  function updateListeners(on, oldOn, add, remove$$1, vm) {
    var name, def, cur, old, event;

    for (name in on) {
      def = cur = on[name];
      old = oldOn[name];
      event = normalizeEvent(name);
      /* istanbul ignore if */

      if (isUndef(cur)) {
        "development" !== 'production' && warn("Invalid handler for event \"" + event.name + "\": got " + String(cur), vm);
      } else if (isUndef(old)) {
        if (isUndef(cur.fns)) {
          cur = on[name] = createFnInvoker(cur);
        }

        add(event.name, cur, event.once, event.capture, event.passive, event.params);
      } else if (cur !== old) {
        old.fns = cur;
        on[name] = old;
      }
    }

    for (name in oldOn) {
      if (isUndef(on[name])) {
        event = normalizeEvent(name);
        remove$$1(event.name, oldOn[name], event.capture);
      }
    }
  }
  /*  */


  function mergeVNodeHook(def, hookKey, hook) {
    if (def instanceof VNode) {
      def = def.data.hook || (def.data.hook = {});
    }

    var invoker;
    var oldHook = def[hookKey];

    function wrappedHook() {
      hook.apply(this, arguments); // important: remove merged hook to ensure it's called only once
      // and prevent memory leak

      remove(invoker.fns, wrappedHook);
    }

    if (isUndef(oldHook)) {
      // no existing hook
      invoker = createFnInvoker([wrappedHook]);
    } else {
      /* istanbul ignore if */
      if (isDef(oldHook.fns) && isTrue(oldHook.merged)) {
        // already a merged invoker
        invoker = oldHook;
        invoker.fns.push(wrappedHook);
      } else {
        // existing plain hook
        invoker = createFnInvoker([oldHook, wrappedHook]);
      }
    }

    invoker.merged = true;
    def[hookKey] = invoker;
  }
  /*  */


  function extractPropsFromVNodeData(data, Ctor, tag) {
    // we are only extracting raw values here.
    // validation and default values are handled in the child
    // component itself.
    var propOptions = Ctor.options.props;

    if (isUndef(propOptions)) {
      return;
    }

    var res = {};
    var attrs = data.attrs;
    var props = data.props;

    if (isDef(attrs) || isDef(props)) {
      for (var key in propOptions) {
        var altKey = hyphenate(key);
        {
          var keyInLowerCase = key.toLowerCase();

          if (key !== keyInLowerCase && attrs && hasOwn(attrs, keyInLowerCase)) {
            tip("Prop \"" + keyInLowerCase + "\" is passed to component " + formatComponentName(tag || Ctor) + ", but the declared prop name is" + " \"" + key + "\". " + "Note that HTML attributes are case-insensitive and camelCased " + "props need to use their kebab-case equivalents when using in-DOM " + "templates. You should probably use \"" + altKey + "\" instead of \"" + key + "\".");
          }
        }
        checkProp(res, props, key, altKey, true) || checkProp(res, attrs, key, altKey, false);
      }
    }

    return res;
  }

  function checkProp(res, hash, key, altKey, preserve) {
    if (isDef(hash)) {
      if (hasOwn(hash, key)) {
        res[key] = hash[key];

        if (!preserve) {
          delete hash[key];
        }

        return true;
      } else if (hasOwn(hash, altKey)) {
        res[key] = hash[altKey];

        if (!preserve) {
          delete hash[altKey];
        }

        return true;
      }
    }

    return false;
  }
  /*  */
  // The template compiler attempts to minimize the need for normalization by
  // statically analyzing the template at compile time.
  //
  // For plain HTML markup, normalization can be completely skipped because the
  // generated render function is guaranteed to return Array<VNode>. There are
  // two cases where extra normalization is needed:
  // 1. When the children contains components - because a functional component
  // may return an Array instead of a single root. In this case, just a simple
  // normalization is needed - if any child is an Array, we flatten the whole
  // thing with Array.prototype.concat. It is guaranteed to be only 1-level deep
  // because functional components already normalize their own children.


  function simpleNormalizeChildren(children) {
    for (var i = 0; i < children.length; i++) {
      if (Array.isArray(children[i])) {
        return Array.prototype.concat.apply([], children);
      }
    }

    return children;
  } // 2. When the children contains constructs that always generated nested Arrays,
  // e.g. <template>, <slot>, v-for, or when the children is provided by user
  // with hand-written render functions / JSX. In such cases a full normalization
  // is needed to cater to all possible types of children values.


  function normalizeChildren(children) {
    return isPrimitive(children) ? [createTextVNode(children)] : Array.isArray(children) ? normalizeArrayChildren(children) : undefined;
  }

  function isTextNode(node) {
    return isDef(node) && isDef(node.text) && isFalse(node.isComment);
  }

  function normalizeArrayChildren(children, nestedIndex) {
    var res = [];
    var i, c, lastIndex, last;

    for (i = 0; i < children.length; i++) {
      c = children[i];

      if (isUndef(c) || typeof c === 'boolean') {
        continue;
      }

      lastIndex = res.length - 1;
      last = res[lastIndex]; //  nested

      if (Array.isArray(c)) {
        if (c.length > 0) {
          c = normalizeArrayChildren(c, (nestedIndex || '') + "_" + i); // merge adjacent text nodes

          if (isTextNode(c[0]) && isTextNode(last)) {
            res[lastIndex] = createTextVNode(last.text + c[0].text);
            c.shift();
          }

          res.push.apply(res, c);
        }
      } else if (isPrimitive(c)) {
        if (isTextNode(last)) {
          // merge adjacent text nodes
          // this is necessary for SSR hydration because text nodes are
          // essentially merged when rendered to HTML strings
          res[lastIndex] = createTextVNode(last.text + c);
        } else if (c !== '') {
          // convert primitive to vnode
          res.push(createTextVNode(c));
        }
      } else {
        if (isTextNode(c) && isTextNode(last)) {
          // merge adjacent text nodes
          res[lastIndex] = createTextVNode(last.text + c.text);
        } else {
          // default key for nested array children (likely generated by v-for)
          if (isTrue(children._isVList) && isDef(c.tag) && isUndef(c.key) && isDef(nestedIndex)) {
            c.key = "__vlist" + nestedIndex + "_" + i + "__";
          }

          res.push(c);
        }
      }
    }

    return res;
  }
  /*  */


  function ensureCtor(comp, base) {
    if (comp.__esModule || hasSymbol && comp[Symbol.toStringTag] === 'Module') {
      comp = comp.default;
    }

    return isObject(comp) ? base.extend(comp) : comp;
  }

  function createAsyncPlaceholder(factory, data, context, children, tag) {
    var node = createEmptyVNode();
    node.asyncFactory = factory;
    node.asyncMeta = {
      data: data,
      context: context,
      children: children,
      tag: tag
    };
    return node;
  }

  function resolveAsyncComponent(factory, baseCtor, context) {
    if (isTrue(factory.error) && isDef(factory.errorComp)) {
      return factory.errorComp;
    }

    if (isDef(factory.resolved)) {
      return factory.resolved;
    }

    if (isTrue(factory.loading) && isDef(factory.loadingComp)) {
      return factory.loadingComp;
    }

    if (isDef(factory.contexts)) {
      // already pending
      factory.contexts.push(context);
    } else {
      var contexts = factory.contexts = [context];
      var sync = true;

      var forceRender = function forceRender() {
        for (var i = 0, l = contexts.length; i < l; i++) {
          contexts[i].$forceUpdate();
        }
      };

      var resolve = once(function (res) {
        // cache resolved
        factory.resolved = ensureCtor(res, baseCtor); // invoke callbacks only if this is not a synchronous resolve
        // (async resolves are shimmed as synchronous during SSR)

        if (!sync) {
          forceRender();
        }
      });
      var reject = once(function (reason) {
        "development" !== 'production' && warn("Failed to resolve async component: " + String(factory) + (reason ? "\nReason: " + reason : ''));

        if (isDef(factory.errorComp)) {
          factory.error = true;
          forceRender();
        }
      });
      var res = factory(resolve, reject);

      if (isObject(res)) {
        if (typeof res.then === 'function') {
          // () => Promise
          if (isUndef(factory.resolved)) {
            res.then(resolve, reject);
          }
        } else if (isDef(res.component) && typeof res.component.then === 'function') {
          res.component.then(resolve, reject);

          if (isDef(res.error)) {
            factory.errorComp = ensureCtor(res.error, baseCtor);
          }

          if (isDef(res.loading)) {
            factory.loadingComp = ensureCtor(res.loading, baseCtor);

            if (res.delay === 0) {
              factory.loading = true;
            } else {
              setTimeout(function () {
                if (isUndef(factory.resolved) && isUndef(factory.error)) {
                  factory.loading = true;
                  forceRender();
                }
              }, res.delay || 200);
            }
          }

          if (isDef(res.timeout)) {
            setTimeout(function () {
              if (isUndef(factory.resolved)) {
                reject("timeout (" + res.timeout + "ms)");
              }
            }, res.timeout);
          }
        }
      }

      sync = false; // return in case resolved synchronously

      return factory.loading ? factory.loadingComp : factory.resolved;
    }
  }
  /*  */


  function isAsyncPlaceholder(node) {
    return node.isComment && node.asyncFactory;
  }
  /*  */


  function getFirstComponentChild(children) {
    if (Array.isArray(children)) {
      for (var i = 0; i < children.length; i++) {
        var c = children[i];

        if (isDef(c) && (isDef(c.componentOptions) || isAsyncPlaceholder(c))) {
          return c;
        }
      }
    }
  }
  /*  */

  /*  */


  function initEvents(vm) {
    vm._events = Object.create(null);
    vm._hasHookEvent = false; // init parent attached events

    var listeners = vm.$options._parentListeners;

    if (listeners) {
      updateComponentListeners(vm, listeners);
    }
  }

  var target;

  function add(event, fn, once) {
    if (once) {
      target.$once(event, fn);
    } else {
      target.$on(event, fn);
    }
  }

  function remove$1(event, fn) {
    target.$off(event, fn);
  }

  function updateComponentListeners(vm, listeners, oldListeners) {
    target = vm;
    updateListeners(listeners, oldListeners || {}, add, remove$1, vm);
    target = undefined;
  }

  function eventsMixin(Vue) {
    var hookRE = /^hook:/;

    Vue.prototype.$on = function (event, fn) {
      var this$1 = this;
      var vm = this;

      if (Array.isArray(event)) {
        for (var i = 0, l = event.length; i < l; i++) {
          this$1.$on(event[i], fn);
        }
      } else {
        (vm._events[event] || (vm._events[event] = [])).push(fn); // optimize hook:event cost by using a boolean flag marked at registration
        // instead of a hash lookup

        if (hookRE.test(event)) {
          vm._hasHookEvent = true;
        }
      }

      return vm;
    };

    Vue.prototype.$once = function (event, fn) {
      var vm = this;

      function on() {
        vm.$off(event, on);
        fn.apply(vm, arguments);
      }

      on.fn = fn;
      vm.$on(event, on);
      return vm;
    };

    Vue.prototype.$off = function (event, fn) {
      var this$1 = this;
      var vm = this; // all

      if (!arguments.length) {
        vm._events = Object.create(null);
        return vm;
      } // array of events


      if (Array.isArray(event)) {
        for (var i = 0, l = event.length; i < l; i++) {
          this$1.$off(event[i], fn);
        }

        return vm;
      } // specific event


      var cbs = vm._events[event];

      if (!cbs) {
        return vm;
      }

      if (!fn) {
        vm._events[event] = null;
        return vm;
      }

      if (fn) {
        // specific handler
        var cb;
        var i$1 = cbs.length;

        while (i$1--) {
          cb = cbs[i$1];

          if (cb === fn || cb.fn === fn) {
            cbs.splice(i$1, 1);
            break;
          }
        }
      }

      return vm;
    };

    Vue.prototype.$emit = function (event) {
      var vm = this;
      {
        var lowerCaseEvent = event.toLowerCase();

        if (lowerCaseEvent !== event && vm._events[lowerCaseEvent]) {
          tip("Event \"" + lowerCaseEvent + "\" is emitted in component " + formatComponentName(vm) + " but the handler is registered for \"" + event + "\". " + "Note that HTML attributes are case-insensitive and you cannot use " + "v-on to listen to camelCase events when using in-DOM templates. " + "You should probably use \"" + hyphenate(event) + "\" instead of \"" + event + "\".");
        }
      }
      var cbs = vm._events[event];

      if (cbs) {
        cbs = cbs.length > 1 ? toArray(cbs) : cbs;
        var args = toArray(arguments, 1);

        for (var i = 0, l = cbs.length; i < l; i++) {
          try {
            cbs[i].apply(vm, args);
          } catch (e) {
            handleError(e, vm, "event handler for \"" + event + "\"");
          }
        }
      }

      return vm;
    };
  }
  /*  */

  /**
   * Runtime helper for resolving raw children VNodes into a slot object.
   */


  function resolveSlots(children, context) {
    var slots = {};

    if (!children) {
      return slots;
    }

    for (var i = 0, l = children.length; i < l; i++) {
      var child = children[i];
      var data = child.data; // remove slot attribute if the node is resolved as a Vue slot node

      if (data && data.attrs && data.attrs.slot) {
        delete data.attrs.slot;
      } // named slots should only be respected if the vnode was rendered in the
      // same context.


      if ((child.context === context || child.fnContext === context) && data && data.slot != null) {
        var name = data.slot;
        var slot = slots[name] || (slots[name] = []);

        if (child.tag === 'template') {
          slot.push.apply(slot, child.children || []);
        } else {
          slot.push(child);
        }
      } else {
        (slots.default || (slots.default = [])).push(child);
      }
    } // ignore slots that contains only whitespace


    for (var name$1 in slots) {
      if (slots[name$1].every(isWhitespace)) {
        delete slots[name$1];
      }
    }

    return slots;
  }

  function isWhitespace(node) {
    return node.isComment && !node.asyncFactory || node.text === ' ';
  }

  function resolveScopedSlots(fns, // see flow/vnode
  res) {
    res = res || {};

    for (var i = 0; i < fns.length; i++) {
      if (Array.isArray(fns[i])) {
        resolveScopedSlots(fns[i], res);
      } else {
        res[fns[i].key] = fns[i].fn;
      }
    }

    return res;
  }
  /*  */


  var activeInstance = null;
  var isUpdatingChildComponent = false;

  function initLifecycle(vm) {
    var options = vm.$options; // locate first non-abstract parent

    var parent = options.parent;

    if (parent && !options.abstract) {
      while (parent.$options.abstract && parent.$parent) {
        parent = parent.$parent;
      }

      parent.$children.push(vm);
    }

    vm.$parent = parent;
    vm.$root = parent ? parent.$root : vm;
    vm.$children = [];
    vm.$refs = {};
    vm._watcher = null;
    vm._inactive = null;
    vm._directInactive = false;
    vm._isMounted = false;
    vm._isDestroyed = false;
    vm._isBeingDestroyed = false;
  }

  function lifecycleMixin(Vue) {
    Vue.prototype._update = function (vnode, hydrating) {
      var vm = this;

      if (vm._isMounted) {
        callHook(vm, 'beforeUpdate');
      }

      var prevEl = vm.$el;
      var prevVnode = vm._vnode;
      var prevActiveInstance = activeInstance;
      activeInstance = vm;
      vm._vnode = vnode; // Vue.prototype.__patch__ is injected in entry points
      // based on the rendering backend used.

      if (!prevVnode) {
        // initial render
        vm.$el = vm.__patch__(vm.$el, vnode, hydrating, false
        /* removeOnly */
        , vm.$options._parentElm, vm.$options._refElm); // no need for the ref nodes after initial patch
        // this prevents keeping a detached DOM tree in memory (#5851)

        vm.$options._parentElm = vm.$options._refElm = null;
      } else {
        // updates
        vm.$el = vm.__patch__(prevVnode, vnode);
      }

      activeInstance = prevActiveInstance; // update __vue__ reference

      if (prevEl) {
        prevEl.__vue__ = null;
      }

      if (vm.$el) {
        vm.$el.__vue__ = vm;
      } // if parent is an HOC, update its $el as well


      if (vm.$vnode && vm.$parent && vm.$vnode === vm.$parent._vnode) {
        vm.$parent.$el = vm.$el;
      } // updated hook is called by the scheduler to ensure that children are
      // updated in a parent's updated hook.

    };

    Vue.prototype.$forceUpdate = function () {
      var vm = this;

      if (vm._watcher) {
        vm._watcher.update();
      }
    };

    Vue.prototype.$destroy = function () {
      var vm = this;

      if (vm._isBeingDestroyed) {
        return;
      }

      callHook(vm, 'beforeDestroy');
      vm._isBeingDestroyed = true; // remove self from parent

      var parent = vm.$parent;

      if (parent && !parent._isBeingDestroyed && !vm.$options.abstract) {
        remove(parent.$children, vm);
      } // teardown watchers


      if (vm._watcher) {
        vm._watcher.teardown();
      }

      var i = vm._watchers.length;

      while (i--) {
        vm._watchers[i].teardown();
      } // remove reference from data ob
      // frozen object may not have observer.


      if (vm._data.__ob__) {
        vm._data.__ob__.vmCount--;
      } // call the last hook...


      vm._isDestroyed = true; // invoke destroy hooks on current rendered tree

      vm.__patch__(vm._vnode, null); // fire destroyed hook


      callHook(vm, 'destroyed'); // turn off all instance listeners.

      vm.$off(); // remove __vue__ reference

      if (vm.$el) {
        vm.$el.__vue__ = null;
      } // release circular reference (#6759)


      if (vm.$vnode) {
        vm.$vnode.parent = null;
      }
    };
  }

  function mountComponent(vm, el, hydrating) {
    vm.$el = el;

    if (!vm.$options.render) {
      vm.$options.render = createEmptyVNode;
      {
        /* istanbul ignore if */
        if (vm.$options.template && vm.$options.template.charAt(0) !== '#' || vm.$options.el || el) {
          warn('You are using the runtime-only build of Vue where the template ' + 'compiler is not available. Either pre-compile the templates into ' + 'render functions, or use the compiler-included build.', vm);
        } else {
          warn('Failed to mount component: template or render function not defined.', vm);
        }
      }
    }

    callHook(vm, 'beforeMount');
    var updateComponent;
    /* istanbul ignore if */

    if ("development" !== 'production' && config.performance && mark) {
      updateComponent = function updateComponent() {
        var name = vm._name;
        var id = vm._uid;
        var startTag = "vue-perf-start:" + id;
        var endTag = "vue-perf-end:" + id;
        mark(startTag);

        var vnode = vm._render();

        mark(endTag);
        measure("vue " + name + " render", startTag, endTag);
        mark(startTag);

        vm._update(vnode, hydrating);

        mark(endTag);
        measure("vue " + name + " patch", startTag, endTag);
      };
    } else {
      updateComponent = function updateComponent() {
        vm._update(vm._render(), hydrating);
      };
    } // we set this to vm._watcher inside the watcher's constructor
    // since the watcher's initial patch may call $forceUpdate (e.g. inside child
    // component's mounted hook), which relies on vm._watcher being already defined


    new Watcher(vm, updateComponent, noop, null, true
    /* isRenderWatcher */
    );
    hydrating = false; // manually mounted instance, call mounted on self
    // mounted is called for render-created child components in its inserted hook

    if (vm.$vnode == null) {
      vm._isMounted = true;
      callHook(vm, 'mounted');
    }

    return vm;
  }

  function updateChildComponent(vm, propsData, listeners, parentVnode, renderChildren) {
    {
      isUpdatingChildComponent = true;
    } // determine whether component has slot children
    // we need to do this before overwriting $options._renderChildren

    var hasChildren = !!(renderChildren || // has new static slots
    vm.$options._renderChildren || // has old static slots
    parentVnode.data.scopedSlots || // has new scoped slots
    vm.$scopedSlots !== emptyObject // has old scoped slots
    );
    vm.$options._parentVnode = parentVnode;
    vm.$vnode = parentVnode; // update vm's placeholder node without re-render

    if (vm._vnode) {
      // update child tree's parent
      vm._vnode.parent = parentVnode;
    }

    vm.$options._renderChildren = renderChildren; // update $attrs and $listeners hash
    // these are also reactive so they may trigger child update if the child
    // used them during render

    vm.$attrs = parentVnode.data.attrs || emptyObject;
    vm.$listeners = listeners || emptyObject; // update props

    if (propsData && vm.$options.props) {
      toggleObserving(false);
      var props = vm._props;
      var propKeys = vm.$options._propKeys || [];

      for (var i = 0; i < propKeys.length; i++) {
        var key = propKeys[i];
        var propOptions = vm.$options.props; // wtf flow?

        props[key] = validateProp(key, propOptions, propsData, vm);
      }

      toggleObserving(true); // keep a copy of raw propsData

      vm.$options.propsData = propsData;
    } // update listeners


    listeners = listeners || emptyObject;
    var oldListeners = vm.$options._parentListeners;
    vm.$options._parentListeners = listeners;
    updateComponentListeners(vm, listeners, oldListeners); // resolve slots + force update if has children

    if (hasChildren) {
      vm.$slots = resolveSlots(renderChildren, parentVnode.context);
      vm.$forceUpdate();
    }

    {
      isUpdatingChildComponent = false;
    }
  }

  function isInInactiveTree(vm) {
    while (vm && (vm = vm.$parent)) {
      if (vm._inactive) {
        return true;
      }
    }

    return false;
  }

  function activateChildComponent(vm, direct) {
    if (direct) {
      vm._directInactive = false;

      if (isInInactiveTree(vm)) {
        return;
      }
    } else if (vm._directInactive) {
      return;
    }

    if (vm._inactive || vm._inactive === null) {
      vm._inactive = false;

      for (var i = 0; i < vm.$children.length; i++) {
        activateChildComponent(vm.$children[i]);
      }

      callHook(vm, 'activated');
    }
  }

  function deactivateChildComponent(vm, direct) {
    if (direct) {
      vm._directInactive = true;

      if (isInInactiveTree(vm)) {
        return;
      }
    }

    if (!vm._inactive) {
      vm._inactive = true;

      for (var i = 0; i < vm.$children.length; i++) {
        deactivateChildComponent(vm.$children[i]);
      }

      callHook(vm, 'deactivated');
    }
  }

  function callHook(vm, hook) {
    // #7573 disable dep collection when invoking lifecycle hooks
    pushTarget();
    var handlers = vm.$options[hook];

    if (handlers) {
      for (var i = 0, j = handlers.length; i < j; i++) {
        try {
          handlers[i].call(vm);
        } catch (e) {
          handleError(e, vm, hook + " hook");
        }
      }
    }

    if (vm._hasHookEvent) {
      vm.$emit('hook:' + hook);
    }

    popTarget();
  }
  /*  */


  var MAX_UPDATE_COUNT = 100;
  var queue = [];
  var activatedChildren = [];
  var has = {};
  var circular = {};
  var waiting = false;
  var flushing = false;
  var index = 0;
  /**
   * Reset the scheduler's state.
   */

  function resetSchedulerState() {
    index = queue.length = activatedChildren.length = 0;
    has = {};
    {
      circular = {};
    }
    waiting = flushing = false;
  }
  /**
   * Flush both queues and run the watchers.
   */


  function flushSchedulerQueue() {
    flushing = true;
    var watcher, id; // Sort queue before flush.
    // This ensures that:
    // 1. Components are updated from parent to child. (because parent is always
    //    created before the child)
    // 2. A component's user watchers are run before its render watcher (because
    //    user watchers are created before the render watcher)
    // 3. If a component is destroyed during a parent component's watcher run,
    //    its watchers can be skipped.

    queue.sort(function (a, b) {
      return a.id - b.id;
    }); // do not cache length because more watchers might be pushed
    // as we run existing watchers

    for (index = 0; index < queue.length; index++) {
      watcher = queue[index];
      id = watcher.id;
      has[id] = null;
      watcher.run(); // in dev build, check and stop circular updates.

      if ("development" !== 'production' && has[id] != null) {
        circular[id] = (circular[id] || 0) + 1;

        if (circular[id] > MAX_UPDATE_COUNT) {
          warn('You may have an infinite update loop ' + (watcher.user ? "in watcher with expression \"" + watcher.expression + "\"" : "in a component render function."), watcher.vm);
          break;
        }
      }
    } // keep copies of post queues before resetting state


    var activatedQueue = activatedChildren.slice();
    var updatedQueue = queue.slice();
    resetSchedulerState(); // call component updated and activated hooks

    callActivatedHooks(activatedQueue);
    callUpdatedHooks(updatedQueue); // devtool hook

    /* istanbul ignore if */

    if (devtools && config.devtools) {
      devtools.emit('flush');
    }
  }

  function callUpdatedHooks(queue) {
    var i = queue.length;

    while (i--) {
      var watcher = queue[i];
      var vm = watcher.vm;

      if (vm._watcher === watcher && vm._isMounted) {
        callHook(vm, 'updated');
      }
    }
  }
  /**
   * Queue a kept-alive component that was activated during patch.
   * The queue will be processed after the entire tree has been patched.
   */


  function queueActivatedComponent(vm) {
    // setting _inactive to false here so that a render function can
    // rely on checking whether it's in an inactive tree (e.g. router-view)
    vm._inactive = false;
    activatedChildren.push(vm);
  }

  function callActivatedHooks(queue) {
    for (var i = 0; i < queue.length; i++) {
      queue[i]._inactive = true;
      activateChildComponent(queue[i], true
      /* true */
      );
    }
  }
  /**
   * Push a watcher into the watcher queue.
   * Jobs with duplicate IDs will be skipped unless it's
   * pushed when the queue is being flushed.
   */


  function queueWatcher(watcher) {
    var id = watcher.id;

    if (has[id] == null) {
      has[id] = true;

      if (!flushing) {
        queue.push(watcher);
      } else {
        // if already flushing, splice the watcher based on its id
        // if already past its id, it will be run next immediately.
        var i = queue.length - 1;

        while (i > index && queue[i].id > watcher.id) {
          i--;
        }

        queue.splice(i + 1, 0, watcher);
      } // queue the flush


      if (!waiting) {
        waiting = true;
        nextTick(flushSchedulerQueue);
      }
    }
  }
  /*  */


  var uid$1 = 0;
  /**
   * A watcher parses an expression, collects dependencies,
   * and fires callback when the expression value changes.
   * This is used for both the $watch() api and directives.
   */

  var Watcher = function Watcher(vm, expOrFn, cb, options, isRenderWatcher) {
    this.vm = vm;

    if (isRenderWatcher) {
      vm._watcher = this;
    }

    vm._watchers.push(this); // options


    if (options) {
      this.deep = !!options.deep;
      this.user = !!options.user;
      this.lazy = !!options.lazy;
      this.sync = !!options.sync;
    } else {
      this.deep = this.user = this.lazy = this.sync = false;
    }

    this.cb = cb;
    this.id = ++uid$1; // uid for batching

    this.active = true;
    this.dirty = this.lazy; // for lazy watchers

    this.deps = [];
    this.newDeps = [];
    this.depIds = new _Set();
    this.newDepIds = new _Set();
    this.expression = expOrFn.toString(); // parse expression for getter

    if (typeof expOrFn === 'function') {
      this.getter = expOrFn;
    } else {
      this.getter = parsePath(expOrFn);

      if (!this.getter) {
        this.getter = function () {};

        "development" !== 'production' && warn("Failed watching path: \"" + expOrFn + "\" " + 'Watcher only accepts simple dot-delimited paths. ' + 'For full control, use a function instead.', vm);
      }
    }

    this.value = this.lazy ? undefined : this.get();
  };
  /**
   * Evaluate the getter, and re-collect dependencies.
   */


  Watcher.prototype.get = function get() {
    pushTarget(this);
    var value;
    var vm = this.vm;

    try {
      value = this.getter.call(vm, vm);
    } catch (e) {
      if (this.user) {
        handleError(e, vm, "getter for watcher \"" + this.expression + "\"");
      } else {
        throw e;
      }
    } finally {
      // "touch" every property so they are all tracked as
      // dependencies for deep watching
      if (this.deep) {
        traverse(value);
      }

      popTarget();
      this.cleanupDeps();
    }

    return value;
  };
  /**
   * Add a dependency to this directive.
   */


  Watcher.prototype.addDep = function addDep(dep) {
    var id = dep.id;

    if (!this.newDepIds.has(id)) {
      this.newDepIds.add(id);
      this.newDeps.push(dep);

      if (!this.depIds.has(id)) {
        dep.addSub(this);
      }
    }
  };
  /**
   * Clean up for dependency collection.
   */


  Watcher.prototype.cleanupDeps = function cleanupDeps() {
    var this$1 = this;
    var i = this.deps.length;

    while (i--) {
      var dep = this$1.deps[i];

      if (!this$1.newDepIds.has(dep.id)) {
        dep.removeSub(this$1);
      }
    }

    var tmp = this.depIds;
    this.depIds = this.newDepIds;
    this.newDepIds = tmp;
    this.newDepIds.clear();
    tmp = this.deps;
    this.deps = this.newDeps;
    this.newDeps = tmp;
    this.newDeps.length = 0;
  };
  /**
   * Subscriber interface.
   * Will be called when a dependency changes.
   */


  Watcher.prototype.update = function update() {
    /* istanbul ignore else */
    if (this.lazy) {
      this.dirty = true;
    } else if (this.sync) {
      this.run();
    } else {
      queueWatcher(this);
    }
  };
  /**
   * Scheduler job interface.
   * Will be called by the scheduler.
   */


  Watcher.prototype.run = function run() {
    if (this.active) {
      var value = this.get();

      if (value !== this.value || // Deep watchers and watchers on Object/Arrays should fire even
      // when the value is the same, because the value may
      // have mutated.
      isObject(value) || this.deep) {
        // set new value
        var oldValue = this.value;
        this.value = value;

        if (this.user) {
          try {
            this.cb.call(this.vm, value, oldValue);
          } catch (e) {
            handleError(e, this.vm, "callback for watcher \"" + this.expression + "\"");
          }
        } else {
          this.cb.call(this.vm, value, oldValue);
        }
      }
    }
  };
  /**
   * Evaluate the value of the watcher.
   * This only gets called for lazy watchers.
   */


  Watcher.prototype.evaluate = function evaluate() {
    this.value = this.get();
    this.dirty = false;
  };
  /**
   * Depend on all deps collected by this watcher.
   */


  Watcher.prototype.depend = function depend() {
    var this$1 = this;
    var i = this.deps.length;

    while (i--) {
      this$1.deps[i].depend();
    }
  };
  /**
   * Remove self from all dependencies' subscriber list.
   */


  Watcher.prototype.teardown = function teardown() {
    var this$1 = this;

    if (this.active) {
      // remove self from vm's watcher list
      // this is a somewhat expensive operation so we skip it
      // if the vm is being destroyed.
      if (!this.vm._isBeingDestroyed) {
        remove(this.vm._watchers, this);
      }

      var i = this.deps.length;

      while (i--) {
        this$1.deps[i].removeSub(this$1);
      }

      this.active = false;
    }
  };
  /*  */


  var sharedPropertyDefinition = {
    enumerable: true,
    configurable: true,
    get: noop,
    set: noop
  };

  function proxy(target, sourceKey, key) {
    sharedPropertyDefinition.get = function proxyGetter() {
      return this[sourceKey][key];
    };

    sharedPropertyDefinition.set = function proxySetter(val) {
      this[sourceKey][key] = val;
    };

    Object.defineProperty(target, key, sharedPropertyDefinition);
  }

  function initState(vm) {
    vm._watchers = [];
    var opts = vm.$options;

    if (opts.props) {
      initProps(vm, opts.props);
    }

    if (opts.methods) {
      initMethods(vm, opts.methods);
    }

    if (opts.data) {
      initData(vm);
    } else {
      observe(vm._data = {}, true
      /* asRootData */
      );
    }

    if (opts.computed) {
      initComputed(vm, opts.computed);
    }

    if (opts.watch && opts.watch !== nativeWatch) {
      initWatch(vm, opts.watch);
    }
  }

  function initProps(vm, propsOptions) {
    var propsData = vm.$options.propsData || {};
    var props = vm._props = {}; // cache prop keys so that future props updates can iterate using Array
    // instead of dynamic object key enumeration.

    var keys = vm.$options._propKeys = [];
    var isRoot = !vm.$parent; // root instance props should be converted

    if (!isRoot) {
      toggleObserving(false);
    }

    var loop = function loop(key) {
      keys.push(key);
      var value = validateProp(key, propsOptions, propsData, vm);
      /* istanbul ignore else */

      {
        var hyphenatedKey = hyphenate(key);

        if (isReservedAttribute(hyphenatedKey) || config.isReservedAttr(hyphenatedKey)) {
          warn("\"" + hyphenatedKey + "\" is a reserved attribute and cannot be used as component prop.", vm);
        }

        defineReactive(props, key, value, function () {
          if (vm.$parent && !isUpdatingChildComponent) {
            warn("Avoid mutating a prop directly since the value will be " + "overwritten whenever the parent component re-renders. " + "Instead, use a data or computed property based on the prop's " + "value. Prop being mutated: \"" + key + "\"", vm);
          }
        });
      } // static props are already proxied on the component's prototype
      // during Vue.extend(). We only need to proxy props defined at
      // instantiation here.

      if (!(key in vm)) {
        proxy(vm, "_props", key);
      }
    };

    for (var key in propsOptions) {
      loop(key);
    }

    toggleObserving(true);
  }

  function initData(vm) {
    var data = vm.$options.data;
    data = vm._data = typeof data === 'function' ? getData(data, vm) : data || {};

    if (!isPlainObject(data)) {
      data = {};
      "development" !== 'production' && warn('data functions should return an object:\n' + 'https://vuejs.org/v2/guide/components.html#data-Must-Be-a-Function', vm);
    } // proxy data on instance


    var keys = Object.keys(data);
    var props = vm.$options.props;
    var methods = vm.$options.methods;
    var i = keys.length;

    while (i--) {
      var key = keys[i];
      {
        if (methods && hasOwn(methods, key)) {
          warn("Method \"" + key + "\" has already been defined as a data property.", vm);
        }
      }

      if (props && hasOwn(props, key)) {
        "development" !== 'production' && warn("The data property \"" + key + "\" is already declared as a prop. " + "Use prop default value instead.", vm);
      } else if (!isReserved(key)) {
        proxy(vm, "_data", key);
      }
    } // observe data


    observe(data, true
    /* asRootData */
    );
  }

  function getData(data, vm) {
    // #7573 disable dep collection when invoking data getters
    pushTarget();

    try {
      return data.call(vm, vm);
    } catch (e) {
      handleError(e, vm, "data()");
      return {};
    } finally {
      popTarget();
    }
  }

  var computedWatcherOptions = {
    lazy: true
  };

  function initComputed(vm, computed) {
    // $flow-disable-line
    var watchers = vm._computedWatchers = Object.create(null); // computed properties are just getters during SSR

    var isSSR = isServerRendering();

    for (var key in computed) {
      var userDef = computed[key];
      var getter = typeof userDef === 'function' ? userDef : userDef.get;

      if ("development" !== 'production' && getter == null) {
        warn("Getter is missing for computed property \"" + key + "\".", vm);
      }

      if (!isSSR) {
        // create internal watcher for the computed property.
        watchers[key] = new Watcher(vm, getter || noop, noop, computedWatcherOptions);
      } // component-defined computed properties are already defined on the
      // component prototype. We only need to define computed properties defined
      // at instantiation here.


      if (!(key in vm)) {
        defineComputed(vm, key, userDef);
      } else {
        if (key in vm.$data) {
          warn("The computed property \"" + key + "\" is already defined in data.", vm);
        } else if (vm.$options.props && key in vm.$options.props) {
          warn("The computed property \"" + key + "\" is already defined as a prop.", vm);
        }
      }
    }
  }

  function defineComputed(target, key, userDef) {
    var shouldCache = !isServerRendering();

    if (typeof userDef === 'function') {
      sharedPropertyDefinition.get = shouldCache ? createComputedGetter(key) : userDef;
      sharedPropertyDefinition.set = noop;
    } else {
      sharedPropertyDefinition.get = userDef.get ? shouldCache && userDef.cache !== false ? createComputedGetter(key) : userDef.get : noop;
      sharedPropertyDefinition.set = userDef.set ? userDef.set : noop;
    }

    if ("development" !== 'production' && sharedPropertyDefinition.set === noop) {
      sharedPropertyDefinition.set = function () {
        warn("Computed property \"" + key + "\" was assigned to but it has no setter.", this);
      };
    }

    Object.defineProperty(target, key, sharedPropertyDefinition);
  }

  function createComputedGetter(key) {
    return function computedGetter() {
      var watcher = this._computedWatchers && this._computedWatchers[key];

      if (watcher) {
        if (watcher.dirty) {
          watcher.evaluate();
        }

        if (Dep.target) {
          watcher.depend();
        }

        return watcher.value;
      }
    };
  }

  function initMethods(vm, methods) {
    var props = vm.$options.props;

    for (var key in methods) {
      {
        if (methods[key] == null) {
          warn("Method \"" + key + "\" has an undefined value in the component definition. " + "Did you reference the function correctly?", vm);
        }

        if (props && hasOwn(props, key)) {
          warn("Method \"" + key + "\" has already been defined as a prop.", vm);
        }

        if (key in vm && isReserved(key)) {
          warn("Method \"" + key + "\" conflicts with an existing Vue instance method. " + "Avoid defining component methods that start with _ or $.");
        }
      }
      vm[key] = methods[key] == null ? noop : bind(methods[key], vm);
    }
  }

  function initWatch(vm, watch) {
    for (var key in watch) {
      var handler = watch[key];

      if (Array.isArray(handler)) {
        for (var i = 0; i < handler.length; i++) {
          createWatcher(vm, key, handler[i]);
        }
      } else {
        createWatcher(vm, key, handler);
      }
    }
  }

  function createWatcher(vm, expOrFn, handler, options) {
    if (isPlainObject(handler)) {
      options = handler;
      handler = handler.handler;
    }

    if (typeof handler === 'string') {
      handler = vm[handler];
    }

    return vm.$watch(expOrFn, handler, options);
  }

  function stateMixin(Vue) {
    // flow somehow has problems with directly declared definition object
    // when using Object.defineProperty, so we have to procedurally build up
    // the object here.
    var dataDef = {};

    dataDef.get = function () {
      return this._data;
    };

    var propsDef = {};

    propsDef.get = function () {
      return this._props;
    };

    {
      dataDef.set = function (newData) {
        warn('Avoid replacing instance root $data. ' + 'Use nested data properties instead.', this);
      };

      propsDef.set = function () {
        warn("$props is readonly.", this);
      };
    }
    Object.defineProperty(Vue.prototype, '$data', dataDef);
    Object.defineProperty(Vue.prototype, '$props', propsDef);
    Vue.prototype.$set = set;
    Vue.prototype.$delete = del;

    Vue.prototype.$watch = function (expOrFn, cb, options) {
      var vm = this;

      if (isPlainObject(cb)) {
        return createWatcher(vm, expOrFn, cb, options);
      }

      options = options || {};
      options.user = true;
      var watcher = new Watcher(vm, expOrFn, cb, options);

      if (options.immediate) {
        cb.call(vm, watcher.value);
      }

      return function unwatchFn() {
        watcher.teardown();
      };
    };
  }
  /*  */


  function initProvide(vm) {
    var provide = vm.$options.provide;

    if (provide) {
      vm._provided = typeof provide === 'function' ? provide.call(vm) : provide;
    }
  }

  function initInjections(vm) {
    var result = resolveInject(vm.$options.inject, vm);

    if (result) {
      toggleObserving(false);
      Object.keys(result).forEach(function (key) {
        /* istanbul ignore else */
        {
          defineReactive(vm, key, result[key], function () {
            warn("Avoid mutating an injected value directly since the changes will be " + "overwritten whenever the provided component re-renders. " + "injection being mutated: \"" + key + "\"", vm);
          });
        }
      });
      toggleObserving(true);
    }
  }

  function resolveInject(inject, vm) {
    if (inject) {
      // inject is :any because flow is not smart enough to figure out cached
      var result = Object.create(null);
      var keys = hasSymbol ? Reflect.ownKeys(inject).filter(function (key) {
        /* istanbul ignore next */
        return Object.getOwnPropertyDescriptor(inject, key).enumerable;
      }) : Object.keys(inject);

      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        var provideKey = inject[key].from;
        var source = vm;

        while (source) {
          if (source._provided && hasOwn(source._provided, provideKey)) {
            result[key] = source._provided[provideKey];
            break;
          }

          source = source.$parent;
        }

        if (!source) {
          if ('default' in inject[key]) {
            var provideDefault = inject[key].default;
            result[key] = typeof provideDefault === 'function' ? provideDefault.call(vm) : provideDefault;
          } else {
            warn("Injection \"" + key + "\" not found", vm);
          }
        }
      }

      return result;
    }
  }
  /*  */

  /**
   * Runtime helper for rendering v-for lists.
   */


  function renderList(val, render) {
    var ret, i, l, keys, key;

    if (Array.isArray(val) || typeof val === 'string') {
      ret = new Array(val.length);

      for (i = 0, l = val.length; i < l; i++) {
        ret[i] = render(val[i], i);
      }
    } else if (typeof val === 'number') {
      ret = new Array(val);

      for (i = 0; i < val; i++) {
        ret[i] = render(i + 1, i);
      }
    } else if (isObject(val)) {
      keys = Object.keys(val);
      ret = new Array(keys.length);

      for (i = 0, l = keys.length; i < l; i++) {
        key = keys[i];
        ret[i] = render(val[key], key, i);
      }
    }

    if (isDef(ret)) {
      ret._isVList = true;
    }

    return ret;
  }
  /*  */

  /**
   * Runtime helper for rendering <slot>
   */


  function renderSlot(name, fallback, props, bindObject) {
    var scopedSlotFn = this.$scopedSlots[name];
    var nodes;

    if (scopedSlotFn) {
      // scoped slot
      props = props || {};

      if (bindObject) {
        if ("development" !== 'production' && !isObject(bindObject)) {
          warn('slot v-bind without argument expects an Object', this);
        }

        props = extend(extend({}, bindObject), props);
      }

      nodes = scopedSlotFn(props) || fallback;
    } else {
      var slotNodes = this.$slots[name]; // warn duplicate slot usage

      if (slotNodes) {
        if ("development" !== 'production' && slotNodes._rendered) {
          warn("Duplicate presence of slot \"" + name + "\" found in the same render tree " + "- this will likely cause render errors.", this);
        }

        slotNodes._rendered = true;
      }

      nodes = slotNodes || fallback;
    }

    var target = props && props.slot;

    if (target) {
      return this.$createElement('template', {
        slot: target
      }, nodes);
    } else {
      return nodes;
    }
  }
  /*  */

  /**
   * Runtime helper for resolving filters
   */


  function resolveFilter(id) {
    return resolveAsset(this.$options, 'filters', id, true) || identity;
  }
  /*  */


  function isKeyNotMatch(expect, actual) {
    if (Array.isArray(expect)) {
      return expect.indexOf(actual) === -1;
    } else {
      return expect !== actual;
    }
  }
  /**
   * Runtime helper for checking keyCodes from config.
   * exposed as Vue.prototype._k
   * passing in eventKeyName as last argument separately for backwards compat
   */


  function checkKeyCodes(eventKeyCode, key, builtInKeyCode, eventKeyName, builtInKeyName) {
    var mappedKeyCode = config.keyCodes[key] || builtInKeyCode;

    if (builtInKeyName && eventKeyName && !config.keyCodes[key]) {
      return isKeyNotMatch(builtInKeyName, eventKeyName);
    } else if (mappedKeyCode) {
      return isKeyNotMatch(mappedKeyCode, eventKeyCode);
    } else if (eventKeyName) {
      return hyphenate(eventKeyName) !== key;
    }
  }
  /*  */

  /**
   * Runtime helper for merging v-bind="object" into a VNode's data.
   */


  function bindObjectProps(data, tag, value, asProp, isSync) {
    if (value) {
      if (!isObject(value)) {
        "development" !== 'production' && warn('v-bind without argument expects an Object or Array value', this);
      } else {
        if (Array.isArray(value)) {
          value = toObject(value);
        }

        var hash;

        var loop = function loop(key) {
          if (key === 'class' || key === 'style' || isReservedAttribute(key)) {
            hash = data;
          } else {
            var type = data.attrs && data.attrs.type;
            hash = asProp || config.mustUseProp(tag, type, key) ? data.domProps || (data.domProps = {}) : data.attrs || (data.attrs = {});
          }

          if (!(key in hash)) {
            hash[key] = value[key];

            if (isSync) {
              var on = data.on || (data.on = {});

              on["update:" + key] = function ($event) {
                value[key] = $event;
              };
            }
          }
        };

        for (var key in value) {
          loop(key);
        }
      }
    }

    return data;
  }
  /*  */

  /**
   * Runtime helper for rendering static trees.
   */


  function renderStatic(index, isInFor) {
    var cached = this._staticTrees || (this._staticTrees = []);
    var tree = cached[index]; // if has already-rendered static tree and not inside v-for,
    // we can reuse the same tree.

    if (tree && !isInFor) {
      return tree;
    } // otherwise, render a fresh tree.


    tree = cached[index] = this.$options.staticRenderFns[index].call(this._renderProxy, null, this // for render fns generated for functional component templates
    );
    markStatic(tree, "__static__" + index, false);
    return tree;
  }
  /**
   * Runtime helper for v-once.
   * Effectively it means marking the node as static with a unique key.
   */


  function markOnce(tree, index, key) {
    markStatic(tree, "__once__" + index + (key ? "_" + key : ""), true);
    return tree;
  }

  function markStatic(tree, key, isOnce) {
    if (Array.isArray(tree)) {
      for (var i = 0; i < tree.length; i++) {
        if (tree[i] && typeof tree[i] !== 'string') {
          markStaticNode(tree[i], key + "_" + i, isOnce);
        }
      }
    } else {
      markStaticNode(tree, key, isOnce);
    }
  }

  function markStaticNode(node, key, isOnce) {
    node.isStatic = true;
    node.key = key;
    node.isOnce = isOnce;
  }
  /*  */


  function bindObjectListeners(data, value) {
    if (value) {
      if (!isPlainObject(value)) {
        "development" !== 'production' && warn('v-on without argument expects an Object value', this);
      } else {
        var on = data.on = data.on ? extend({}, data.on) : {};

        for (var key in value) {
          var existing = on[key];
          var ours = value[key];
          on[key] = existing ? [].concat(existing, ours) : ours;
        }
      }
    }

    return data;
  }
  /*  */


  function installRenderHelpers(target) {
    target._o = markOnce;
    target._n = toNumber;
    target._s = toString;
    target._l = renderList;
    target._t = renderSlot;
    target._q = looseEqual;
    target._i = looseIndexOf;
    target._m = renderStatic;
    target._f = resolveFilter;
    target._k = checkKeyCodes;
    target._b = bindObjectProps;
    target._v = createTextVNode;
    target._e = createEmptyVNode;
    target._u = resolveScopedSlots;
    target._g = bindObjectListeners;
  }
  /*  */


  function FunctionalRenderContext(data, props, children, parent, Ctor) {
    var options = Ctor.options; // ensure the createElement function in functional components
    // gets a unique context - this is necessary for correct named slot check

    var contextVm;

    if (hasOwn(parent, '_uid')) {
      contextVm = Object.create(parent); // $flow-disable-line

      contextVm._original = parent;
    } else {
      // the context vm passed in is a functional context as well.
      // in this case we want to make sure we are able to get a hold to the
      // real context instance.
      contextVm = parent; // $flow-disable-line

      parent = parent._original;
    }

    var isCompiled = isTrue(options._compiled);
    var needNormalization = !isCompiled;
    this.data = data;
    this.props = props;
    this.children = children;
    this.parent = parent;
    this.listeners = data.on || emptyObject;
    this.injections = resolveInject(options.inject, parent);

    this.slots = function () {
      return resolveSlots(children, parent);
    }; // support for compiled functional template


    if (isCompiled) {
      // exposing $options for renderStatic()
      this.$options = options; // pre-resolve slots for renderSlot()

      this.$slots = this.slots();
      this.$scopedSlots = data.scopedSlots || emptyObject;
    }

    if (options._scopeId) {
      this._c = function (a, b, c, d) {
        var vnode = createElement(contextVm, a, b, c, d, needNormalization);

        if (vnode && !Array.isArray(vnode)) {
          vnode.fnScopeId = options._scopeId;
          vnode.fnContext = parent;
        }

        return vnode;
      };
    } else {
      this._c = function (a, b, c, d) {
        return createElement(contextVm, a, b, c, d, needNormalization);
      };
    }
  }

  installRenderHelpers(FunctionalRenderContext.prototype);

  function createFunctionalComponent(Ctor, propsData, data, contextVm, children) {
    var options = Ctor.options;
    var props = {};
    var propOptions = options.props;

    if (isDef(propOptions)) {
      for (var key in propOptions) {
        props[key] = validateProp(key, propOptions, propsData || emptyObject);
      }
    } else {
      if (isDef(data.attrs)) {
        mergeProps(props, data.attrs);
      }

      if (isDef(data.props)) {
        mergeProps(props, data.props);
      }
    }

    var renderContext = new FunctionalRenderContext(data, props, children, contextVm, Ctor);
    var vnode = options.render.call(null, renderContext._c, renderContext);

    if (vnode instanceof VNode) {
      return cloneAndMarkFunctionalResult(vnode, data, renderContext.parent, options);
    } else if (Array.isArray(vnode)) {
      var vnodes = normalizeChildren(vnode) || [];
      var res = new Array(vnodes.length);

      for (var i = 0; i < vnodes.length; i++) {
        res[i] = cloneAndMarkFunctionalResult(vnodes[i], data, renderContext.parent, options);
      }

      return res;
    }
  }

  function cloneAndMarkFunctionalResult(vnode, data, contextVm, options) {
    // #7817 clone node before setting fnContext, otherwise if the node is reused
    // (e.g. it was from a cached normal slot) the fnContext causes named slots
    // that should not be matched to match.
    var clone = cloneVNode(vnode);
    clone.fnContext = contextVm;
    clone.fnOptions = options;

    if (data.slot) {
      (clone.data || (clone.data = {})).slot = data.slot;
    }

    return clone;
  }

  function mergeProps(to, from) {
    for (var key in from) {
      to[camelize(key)] = from[key];
    }
  }
  /*  */
  // Register the component hook to weex native render engine.
  // The hook will be triggered by native, not javascript.
  // Updates the state of the component to weex native render engine.

  /*  */
  // https://github.com/Hanks10100/weex-native-directive/tree/master/component
  // listening on native callback

  /*  */

  /*  */
  // inline hooks to be invoked on component VNodes during patch


  var componentVNodeHooks = {
    init: function init(vnode, hydrating, parentElm, refElm) {
      if (vnode.componentInstance && !vnode.componentInstance._isDestroyed && vnode.data.keepAlive) {
        // kept-alive components, treat as a patch
        var mountedNode = vnode; // work around flow

        componentVNodeHooks.prepatch(mountedNode, mountedNode);
      } else {
        var child = vnode.componentInstance = createComponentInstanceForVnode(vnode, activeInstance, parentElm, refElm);
        child.$mount(hydrating ? vnode.elm : undefined, hydrating);
      }
    },
    prepatch: function prepatch(oldVnode, vnode) {
      var options = vnode.componentOptions;
      var child = vnode.componentInstance = oldVnode.componentInstance;
      updateChildComponent(child, options.propsData, // updated props
      options.listeners, // updated listeners
      vnode, // new parent vnode
      options.children // new children
      );
    },
    insert: function insert(vnode) {
      var context = vnode.context;
      var componentInstance = vnode.componentInstance;

      if (!componentInstance._isMounted) {
        componentInstance._isMounted = true;
        callHook(componentInstance, 'mounted');
      }

      if (vnode.data.keepAlive) {
        if (context._isMounted) {
          // vue-router#1212
          // During updates, a kept-alive component's child components may
          // change, so directly walking the tree here may call activated hooks
          // on incorrect children. Instead we push them into a queue which will
          // be processed after the whole patch process ended.
          queueActivatedComponent(componentInstance);
        } else {
          activateChildComponent(componentInstance, true
          /* direct */
          );
        }
      }
    },
    destroy: function destroy(vnode) {
      var componentInstance = vnode.componentInstance;

      if (!componentInstance._isDestroyed) {
        if (!vnode.data.keepAlive) {
          componentInstance.$destroy();
        } else {
          deactivateChildComponent(componentInstance, true
          /* direct */
          );
        }
      }
    }
  };
  var hooksToMerge = Object.keys(componentVNodeHooks);

  function createComponent(Ctor, data, context, children, tag) {
    if (isUndef(Ctor)) {
      return;
    }

    var baseCtor = context.$options._base; // plain options object: turn it into a constructor

    if (isObject(Ctor)) {
      Ctor = baseCtor.extend(Ctor);
    } // if at this stage it's not a constructor or an async component factory,
    // reject.


    if (typeof Ctor !== 'function') {
      {
        warn("Invalid Component definition: " + String(Ctor), context);
      }
      return;
    } // async component


    var asyncFactory;

    if (isUndef(Ctor.cid)) {
      asyncFactory = Ctor;
      Ctor = resolveAsyncComponent(asyncFactory, baseCtor, context);

      if (Ctor === undefined) {
        // return a placeholder node for async component, which is rendered
        // as a comment node but preserves all the raw information for the node.
        // the information will be used for async server-rendering and hydration.
        return createAsyncPlaceholder(asyncFactory, data, context, children, tag);
      }
    }

    data = data || {}; // resolve constructor options in case global mixins are applied after
    // component constructor creation

    resolveConstructorOptions(Ctor); // transform component v-model data into props & events

    if (isDef(data.model)) {
      transformModel(Ctor.options, data);
    } // extract props


    var propsData = extractPropsFromVNodeData(data, Ctor, tag); // functional component

    if (isTrue(Ctor.options.functional)) {
      return createFunctionalComponent(Ctor, propsData, data, context, children);
    } // extract listeners, since these needs to be treated as
    // child component listeners instead of DOM listeners


    var listeners = data.on; // replace with listeners with .native modifier
    // so it gets processed during parent component patch.

    data.on = data.nativeOn;

    if (isTrue(Ctor.options.abstract)) {
      // abstract components do not keep anything
      // other than props & listeners & slot
      // work around flow
      var slot = data.slot;
      data = {};

      if (slot) {
        data.slot = slot;
      }
    } // install component management hooks onto the placeholder node


    installComponentHooks(data); // return a placeholder vnode

    var name = Ctor.options.name || tag;
    var vnode = new VNode("vue-component-" + Ctor.cid + (name ? "-" + name : ''), data, undefined, undefined, undefined, context, {
      Ctor: Ctor,
      propsData: propsData,
      listeners: listeners,
      tag: tag,
      children: children
    }, asyncFactory); // Weex specific: invoke recycle-list optimized @render function for
    // extracting cell-slot template.
    // https://github.com/Hanks10100/weex-native-directive/tree/master/component

    /* istanbul ignore if */

    return vnode;
  }

  function createComponentInstanceForVnode(vnode, // we know it's MountedComponentVNode but flow doesn't
  parent, // activeInstance in lifecycle state
  parentElm, refElm) {
    var options = {
      _isComponent: true,
      parent: parent,
      _parentVnode: vnode,
      _parentElm: parentElm || null,
      _refElm: refElm || null
    }; // check inline-template render functions

    var inlineTemplate = vnode.data.inlineTemplate;

    if (isDef(inlineTemplate)) {
      options.render = inlineTemplate.render;
      options.staticRenderFns = inlineTemplate.staticRenderFns;
    }

    return new vnode.componentOptions.Ctor(options);
  }

  function installComponentHooks(data) {
    var hooks = data.hook || (data.hook = {});

    for (var i = 0; i < hooksToMerge.length; i++) {
      var key = hooksToMerge[i];
      hooks[key] = componentVNodeHooks[key];
    }
  } // transform component v-model info (value and callback) into
  // prop and event handler respectively.


  function transformModel(options, data) {
    var prop = options.model && options.model.prop || 'value';
    var event = options.model && options.model.event || 'input';
    (data.props || (data.props = {}))[prop] = data.model.value;
    var on = data.on || (data.on = {});

    if (isDef(on[event])) {
      on[event] = [data.model.callback].concat(on[event]);
    } else {
      on[event] = data.model.callback;
    }
  }
  /*  */


  var SIMPLE_NORMALIZE = 1;
  var ALWAYS_NORMALIZE = 2; // wrapper function for providing a more flexible interface
  // without getting yelled at by flow

  function createElement(context, tag, data, children, normalizationType, alwaysNormalize) {
    if (Array.isArray(data) || isPrimitive(data)) {
      normalizationType = children;
      children = data;
      data = undefined;
    }

    if (isTrue(alwaysNormalize)) {
      normalizationType = ALWAYS_NORMALIZE;
    }

    return _createElement(context, tag, data, children, normalizationType);
  }

  function _createElement(context, tag, data, children, normalizationType) {
    if (isDef(data) && isDef(data.__ob__)) {
      "development" !== 'production' && warn("Avoid using observed data object as vnode data: " + JSON.stringify(data) + "\n" + 'Always create fresh vnode data objects in each render!', context);
      return createEmptyVNode();
    } // object syntax in v-bind


    if (isDef(data) && isDef(data.is)) {
      tag = data.is;
    }

    if (!tag) {
      // in case of component :is set to falsy value
      return createEmptyVNode();
    } // warn against non-primitive key


    if ("development" !== 'production' && isDef(data) && isDef(data.key) && !isPrimitive(data.key)) {
      {
        warn('Avoid using non-primitive value as key, ' + 'use string/number value instead.', context);
      }
    } // support single function children as default scoped slot


    if (Array.isArray(children) && typeof children[0] === 'function') {
      data = data || {};
      data.scopedSlots = {
        default: children[0]
      };
      children.length = 0;
    }

    if (normalizationType === ALWAYS_NORMALIZE) {
      children = normalizeChildren(children);
    } else if (normalizationType === SIMPLE_NORMALIZE) {
      children = simpleNormalizeChildren(children);
    }

    var vnode, ns;

    if (typeof tag === 'string') {
      var Ctor;
      ns = context.$vnode && context.$vnode.ns || config.getTagNamespace(tag);

      if (config.isReservedTag(tag)) {
        // platform built-in elements
        vnode = new VNode(config.parsePlatformTagName(tag), data, children, undefined, undefined, context);
      } else if (isDef(Ctor = resolveAsset(context.$options, 'components', tag))) {
        // component
        vnode = createComponent(Ctor, data, context, children, tag);
      } else {
        // unknown or unlisted namespaced elements
        // check at runtime because it may get assigned a namespace when its
        // parent normalizes children
        vnode = new VNode(tag, data, children, undefined, undefined, context);
      }
    } else {
      // direct component options / constructor
      vnode = createComponent(tag, data, context, children);
    }

    if (Array.isArray(vnode)) {
      return vnode;
    } else if (isDef(vnode)) {
      if (isDef(ns)) {
        applyNS(vnode, ns);
      }

      if (isDef(data)) {
        registerDeepBindings(data);
      }

      return vnode;
    } else {
      return createEmptyVNode();
    }
  }

  function applyNS(vnode, ns, force) {
    vnode.ns = ns;

    if (vnode.tag === 'foreignObject') {
      // use default namespace inside foreignObject
      ns = undefined;
      force = true;
    }

    if (isDef(vnode.children)) {
      for (var i = 0, l = vnode.children.length; i < l; i++) {
        var child = vnode.children[i];

        if (isDef(child.tag) && (isUndef(child.ns) || isTrue(force) && child.tag !== 'svg')) {
          applyNS(child, ns, force);
        }
      }
    }
  } // ref #5318
  // necessary to ensure parent re-render when deep bindings like :style and
  // :class are used on slot nodes


  function registerDeepBindings(data) {
    if (isObject(data.style)) {
      traverse(data.style);
    }

    if (isObject(data.class)) {
      traverse(data.class);
    }
  }
  /*  */


  function initRender(vm) {
    vm._vnode = null; // the root of the child tree

    vm._staticTrees = null; // v-once cached trees

    var options = vm.$options;
    var parentVnode = vm.$vnode = options._parentVnode; // the placeholder node in parent tree

    var renderContext = parentVnode && parentVnode.context;
    vm.$slots = resolveSlots(options._renderChildren, renderContext);
    vm.$scopedSlots = emptyObject; // bind the createElement fn to this instance
    // so that we get proper render context inside it.
    // args order: tag, data, children, normalizationType, alwaysNormalize
    // internal version is used by render functions compiled from templates

    vm._c = function (a, b, c, d) {
      return createElement(vm, a, b, c, d, false);
    }; // normalization is always applied for the public version, used in
    // user-written render functions.


    vm.$createElement = function (a, b, c, d) {
      return createElement(vm, a, b, c, d, true);
    }; // $attrs & $listeners are exposed for easier HOC creation.
    // they need to be reactive so that HOCs using them are always updated


    var parentData = parentVnode && parentVnode.data;
    /* istanbul ignore else */

    {
      defineReactive(vm, '$attrs', parentData && parentData.attrs || emptyObject, function () {
        !isUpdatingChildComponent && warn("$attrs is readonly.", vm);
      }, true);
      defineReactive(vm, '$listeners', options._parentListeners || emptyObject, function () {
        !isUpdatingChildComponent && warn("$listeners is readonly.", vm);
      }, true);
    }
  }

  function renderMixin(Vue) {
    // install runtime convenience helpers
    installRenderHelpers(Vue.prototype);

    Vue.prototype.$nextTick = function (fn) {
      return nextTick(fn, this);
    };

    Vue.prototype._render = function () {
      var vm = this;
      var ref = vm.$options;
      var render = ref.render;
      var _parentVnode = ref._parentVnode; // reset _rendered flag on slots for duplicate slot check

      {
        for (var key in vm.$slots) {
          // $flow-disable-line
          vm.$slots[key]._rendered = false;
        }
      }

      if (_parentVnode) {
        vm.$scopedSlots = _parentVnode.data.scopedSlots || emptyObject;
      } // set parent vnode. this allows render functions to have access
      // to the data on the placeholder node.


      vm.$vnode = _parentVnode; // render self

      var vnode;

      try {
        vnode = render.call(vm._renderProxy, vm.$createElement);
      } catch (e) {
        handleError(e, vm, "render"); // return error render result,
        // or previous vnode to prevent render error causing blank component

        /* istanbul ignore else */

        {
          if (vm.$options.renderError) {
            try {
              vnode = vm.$options.renderError.call(vm._renderProxy, vm.$createElement, e);
            } catch (e) {
              handleError(e, vm, "renderError");
              vnode = vm._vnode;
            }
          } else {
            vnode = vm._vnode;
          }
        }
      } // return empty vnode in case the render function errored out


      if (!(vnode instanceof VNode)) {
        if ("development" !== 'production' && Array.isArray(vnode)) {
          warn('Multiple root nodes returned from render function. Render function ' + 'should return a single root node.', vm);
        }

        vnode = createEmptyVNode();
      } // set parent


      vnode.parent = _parentVnode;
      return vnode;
    };
  }
  /*  */


  var uid$3 = 0;

  function initMixin(Vue) {
    Vue.prototype._init = function (options) {
      var vm = this; // a uid

      vm._uid = uid$3++;
      var startTag, endTag;
      /* istanbul ignore if */

      if ("development" !== 'production' && config.performance && mark) {
        startTag = "vue-perf-start:" + vm._uid;
        endTag = "vue-perf-end:" + vm._uid;
        mark(startTag);
      } // a flag to avoid this being observed


      vm._isVue = true; // merge options

      if (options && options._isComponent) {
        // optimize internal component instantiation
        // since dynamic options merging is pretty slow, and none of the
        // internal component options needs special treatment.
        initInternalComponent(vm, options);
      } else {
        vm.$options = mergeOptions(resolveConstructorOptions(vm.constructor), options || {}, vm);
      }
      /* istanbul ignore else */


      {
        initProxy(vm);
      } // expose real self

      vm._self = vm;
      initLifecycle(vm);
      initEvents(vm);
      initRender(vm);
      callHook(vm, 'beforeCreate');
      initInjections(vm); // resolve injections before data/props

      initState(vm);
      initProvide(vm); // resolve provide after data/props

      callHook(vm, 'created');
      /* istanbul ignore if */

      if ("development" !== 'production' && config.performance && mark) {
        vm._name = formatComponentName(vm, false);
        mark(endTag);
        measure("vue " + vm._name + " init", startTag, endTag);
      }

      if (vm.$options.el) {
        vm.$mount(vm.$options.el);
      }
    };
  }

  function initInternalComponent(vm, options) {
    var opts = vm.$options = Object.create(vm.constructor.options); // doing this because it's faster than dynamic enumeration.

    var parentVnode = options._parentVnode;
    opts.parent = options.parent;
    opts._parentVnode = parentVnode;
    opts._parentElm = options._parentElm;
    opts._refElm = options._refElm;
    var vnodeComponentOptions = parentVnode.componentOptions;
    opts.propsData = vnodeComponentOptions.propsData;
    opts._parentListeners = vnodeComponentOptions.listeners;
    opts._renderChildren = vnodeComponentOptions.children;
    opts._componentTag = vnodeComponentOptions.tag;

    if (options.render) {
      opts.render = options.render;
      opts.staticRenderFns = options.staticRenderFns;
    }
  }

  function resolveConstructorOptions(Ctor) {
    var options = Ctor.options;

    if (Ctor.super) {
      var superOptions = resolveConstructorOptions(Ctor.super);
      var cachedSuperOptions = Ctor.superOptions;

      if (superOptions !== cachedSuperOptions) {
        // super option changed,
        // need to resolve new options.
        Ctor.superOptions = superOptions; // check if there are any late-modified/attached options (#4976)

        var modifiedOptions = resolveModifiedOptions(Ctor); // update base extend options

        if (modifiedOptions) {
          extend(Ctor.extendOptions, modifiedOptions);
        }

        options = Ctor.options = mergeOptions(superOptions, Ctor.extendOptions);

        if (options.name) {
          options.components[options.name] = Ctor;
        }
      }
    }

    return options;
  }

  function resolveModifiedOptions(Ctor) {
    var modified;
    var latest = Ctor.options;
    var extended = Ctor.extendOptions;
    var sealed = Ctor.sealedOptions;

    for (var key in latest) {
      if (latest[key] !== sealed[key]) {
        if (!modified) {
          modified = {};
        }

        modified[key] = dedupe(latest[key], extended[key], sealed[key]);
      }
    }

    return modified;
  }

  function dedupe(latest, extended, sealed) {
    // compare latest and sealed to ensure lifecycle hooks won't be duplicated
    // between merges
    if (Array.isArray(latest)) {
      var res = [];
      sealed = Array.isArray(sealed) ? sealed : [sealed];
      extended = Array.isArray(extended) ? extended : [extended];

      for (var i = 0; i < latest.length; i++) {
        // push original options and not sealed options to exclude duplicated options
        if (extended.indexOf(latest[i]) >= 0 || sealed.indexOf(latest[i]) < 0) {
          res.push(latest[i]);
        }
      }

      return res;
    } else {
      return latest;
    }
  }

  function Vue(options) {
    if ("development" !== 'production' && !(this instanceof Vue)) {
      warn('Vue is a constructor and should be called with the `new` keyword');
    }

    this._init(options);
  }

  initMixin(Vue);
  stateMixin(Vue);
  eventsMixin(Vue);
  lifecycleMixin(Vue);
  renderMixin(Vue);
  /*  */

  function initUse(Vue) {
    Vue.use = function (plugin) {
      var installedPlugins = this._installedPlugins || (this._installedPlugins = []);

      if (installedPlugins.indexOf(plugin) > -1) {
        return this;
      } // additional parameters


      var args = toArray(arguments, 1);
      args.unshift(this);

      if (typeof plugin.install === 'function') {
        plugin.install.apply(plugin, args);
      } else if (typeof plugin === 'function') {
        plugin.apply(null, args);
      }

      installedPlugins.push(plugin);
      return this;
    };
  }
  /*  */


  function initMixin$1(Vue) {
    Vue.mixin = function (mixin) {
      this.options = mergeOptions(this.options, mixin);
      return this;
    };
  }
  /*  */


  function initExtend(Vue) {
    /**
     * Each instance constructor, including Vue, has a unique
     * cid. This enables us to create wrapped "child
     * constructors" for prototypal inheritance and cache them.
     */
    Vue.cid = 0;
    var cid = 1;
    /**
     * Class inheritance
     */

    Vue.extend = function (extendOptions) {
      extendOptions = extendOptions || {};
      var Super = this;
      var SuperId = Super.cid;
      var cachedCtors = extendOptions._Ctor || (extendOptions._Ctor = {});

      if (cachedCtors[SuperId]) {
        return cachedCtors[SuperId];
      }

      var name = extendOptions.name || Super.options.name;

      if ("development" !== 'production' && name) {
        validateComponentName(name);
      }

      var Sub = function VueComponent(options) {
        this._init(options);
      };

      Sub.prototype = Object.create(Super.prototype);
      Sub.prototype.constructor = Sub;
      Sub.cid = cid++;
      Sub.options = mergeOptions(Super.options, extendOptions);
      Sub['super'] = Super; // For props and computed properties, we define the proxy getters on
      // the Vue instances at extension time, on the extended prototype. This
      // avoids Object.defineProperty calls for each instance created.

      if (Sub.options.props) {
        initProps$1(Sub);
      }

      if (Sub.options.computed) {
        initComputed$1(Sub);
      } // allow further extension/mixin/plugin usage


      Sub.extend = Super.extend;
      Sub.mixin = Super.mixin;
      Sub.use = Super.use; // create asset registers, so extended classes
      // can have their private assets too.

      ASSET_TYPES.forEach(function (type) {
        Sub[type] = Super[type];
      }); // enable recursive self-lookup

      if (name) {
        Sub.options.components[name] = Sub;
      } // keep a reference to the super options at extension time.
      // later at instantiation we can check if Super's options have
      // been updated.


      Sub.superOptions = Super.options;
      Sub.extendOptions = extendOptions;
      Sub.sealedOptions = extend({}, Sub.options); // cache constructor

      cachedCtors[SuperId] = Sub;
      return Sub;
    };
  }

  function initProps$1(Comp) {
    var props = Comp.options.props;

    for (var key in props) {
      proxy(Comp.prototype, "_props", key);
    }
  }

  function initComputed$1(Comp) {
    var computed = Comp.options.computed;

    for (var key in computed) {
      defineComputed(Comp.prototype, key, computed[key]);
    }
  }
  /*  */


  function initAssetRegisters(Vue) {
    /**
     * Create asset registration methods.
     */
    ASSET_TYPES.forEach(function (type) {
      Vue[type] = function (id, definition) {
        if (!definition) {
          return this.options[type + 's'][id];
        } else {
          /* istanbul ignore if */
          if ("development" !== 'production' && type === 'component') {
            validateComponentName(id);
          }

          if (type === 'component' && isPlainObject(definition)) {
            definition.name = definition.name || id;
            definition = this.options._base.extend(definition);
          }

          if (type === 'directive' && typeof definition === 'function') {
            definition = {
              bind: definition,
              update: definition
            };
          }

          this.options[type + 's'][id] = definition;
          return definition;
        }
      };
    });
  }
  /*  */


  function getComponentName(opts) {
    return opts && (opts.Ctor.options.name || opts.tag);
  }

  function matches(pattern, name) {
    if (Array.isArray(pattern)) {
      return pattern.indexOf(name) > -1;
    } else if (typeof pattern === 'string') {
      return pattern.split(',').indexOf(name) > -1;
    } else if (isRegExp(pattern)) {
      return pattern.test(name);
    }
    /* istanbul ignore next */


    return false;
  }

  function pruneCache(keepAliveInstance, filter) {
    var cache = keepAliveInstance.cache;
    var keys = keepAliveInstance.keys;
    var _vnode = keepAliveInstance._vnode;

    for (var key in cache) {
      var cachedNode = cache[key];

      if (cachedNode) {
        var name = getComponentName(cachedNode.componentOptions);

        if (name && !filter(name)) {
          pruneCacheEntry(cache, key, keys, _vnode);
        }
      }
    }
  }

  function pruneCacheEntry(cache, key, keys, current) {
    var cached$$1 = cache[key];

    if (cached$$1 && (!current || cached$$1.tag !== current.tag)) {
      cached$$1.componentInstance.$destroy();
    }

    cache[key] = null;
    remove(keys, key);
  }

  var patternTypes = [String, RegExp, Array];
  var KeepAlive = {
    name: 'keep-alive',
    abstract: true,
    props: {
      include: patternTypes,
      exclude: patternTypes,
      max: [String, Number]
    },
    created: function created() {
      this.cache = Object.create(null);
      this.keys = [];
    },
    destroyed: function destroyed() {
      var this$1 = this;

      for (var key in this$1.cache) {
        pruneCacheEntry(this$1.cache, key, this$1.keys);
      }
    },
    mounted: function mounted() {
      var this$1 = this;
      this.$watch('include', function (val) {
        pruneCache(this$1, function (name) {
          return matches(val, name);
        });
      });
      this.$watch('exclude', function (val) {
        pruneCache(this$1, function (name) {
          return !matches(val, name);
        });
      });
    },
    render: function render() {
      var slot = this.$slots.default;
      var vnode = getFirstComponentChild(slot);
      var componentOptions = vnode && vnode.componentOptions;

      if (componentOptions) {
        // check pattern
        var name = getComponentName(componentOptions);
        var ref = this;
        var include = ref.include;
        var exclude = ref.exclude;

        if ( // not included
        include && (!name || !matches(include, name)) || // excluded
        exclude && name && matches(exclude, name)) {
          return vnode;
        }

        var ref$1 = this;
        var cache = ref$1.cache;
        var keys = ref$1.keys;
        var key = vnode.key == null // same constructor may get registered as different local components
        // so cid alone is not enough (#3269)
        ? componentOptions.Ctor.cid + (componentOptions.tag ? "::" + componentOptions.tag : '') : vnode.key;

        if (cache[key]) {
          vnode.componentInstance = cache[key].componentInstance; // make current key freshest

          remove(keys, key);
          keys.push(key);
        } else {
          cache[key] = vnode;
          keys.push(key); // prune oldest entry

          if (this.max && keys.length > parseInt(this.max)) {
            pruneCacheEntry(cache, keys[0], keys, this._vnode);
          }
        }

        vnode.data.keepAlive = true;
      }

      return vnode || slot && slot[0];
    }
  };
  var builtInComponents = {
    KeepAlive: KeepAlive
    /*  */

  };

  function initGlobalAPI(Vue) {
    // config
    var configDef = {};

    configDef.get = function () {
      return config;
    };

    {
      configDef.set = function () {
        warn('Do not replace the Vue.config object, set individual fields instead.');
      };
    }
    Object.defineProperty(Vue, 'config', configDef); // exposed util methods.
    // NOTE: these are not considered part of the public API - avoid relying on
    // them unless you are aware of the risk.

    Vue.util = {
      warn: warn,
      extend: extend,
      mergeOptions: mergeOptions,
      defineReactive: defineReactive
    };
    Vue.set = set;
    Vue.delete = del;
    Vue.nextTick = nextTick;
    Vue.options = Object.create(null);
    ASSET_TYPES.forEach(function (type) {
      Vue.options[type + 's'] = Object.create(null);
    }); // this is used to identify the "base" constructor to extend all plain-object
    // components with in Weex's multi-instance scenarios.

    Vue.options._base = Vue;
    extend(Vue.options.components, builtInComponents);
    initUse(Vue);
    initMixin$1(Vue);
    initExtend(Vue);
    initAssetRegisters(Vue);
  }

  initGlobalAPI(Vue);
  Object.defineProperty(Vue.prototype, '$isServer', {
    get: isServerRendering
  });
  Object.defineProperty(Vue.prototype, '$ssrContext', {
    get: function get() {
      /* istanbul ignore next */
      return this.$vnode && this.$vnode.ssrContext;
    }
  }); // expose FunctionalRenderContext for ssr runtime helper installation

  Object.defineProperty(Vue, 'FunctionalRenderContext', {
    value: FunctionalRenderContext
  });
  Vue.version = '2.5.17';
  /*  */
  // these are reserved for web because they are directly compiled away
  // during template compilation

  var isReservedAttr = makeMap('style,class'); // attributes that should be using props for binding

  var acceptValue = makeMap('input,textarea,option,select,progress');

  var mustUseProp = function mustUseProp(tag, type, attr) {
    return attr === 'value' && acceptValue(tag) && type !== 'button' || attr === 'selected' && tag === 'option' || attr === 'checked' && tag === 'input' || attr === 'muted' && tag === 'video';
  };

  var isEnumeratedAttr = makeMap('contenteditable,draggable,spellcheck');
  var isBooleanAttr = makeMap('allowfullscreen,async,autofocus,autoplay,checked,compact,controls,declare,' + 'default,defaultchecked,defaultmuted,defaultselected,defer,disabled,' + 'enabled,formnovalidate,hidden,indeterminate,inert,ismap,itemscope,loop,multiple,' + 'muted,nohref,noresize,noshade,novalidate,nowrap,open,pauseonexit,readonly,' + 'required,reversed,scoped,seamless,selected,sortable,translate,' + 'truespeed,typemustmatch,visible');
  var xlinkNS = 'http://www.w3.org/1999/xlink';

  var isXlink = function isXlink(name) {
    return name.charAt(5) === ':' && name.slice(0, 5) === 'xlink';
  };

  var getXlinkProp = function getXlinkProp(name) {
    return isXlink(name) ? name.slice(6, name.length) : '';
  };

  var isFalsyAttrValue = function isFalsyAttrValue(val) {
    return val == null || val === false;
  };
  /*  */


  function genClassForVnode(vnode) {
    var data = vnode.data;
    var parentNode = vnode;
    var childNode = vnode;

    while (isDef(childNode.componentInstance)) {
      childNode = childNode.componentInstance._vnode;

      if (childNode && childNode.data) {
        data = mergeClassData(childNode.data, data);
      }
    }

    while (isDef(parentNode = parentNode.parent)) {
      if (parentNode && parentNode.data) {
        data = mergeClassData(data, parentNode.data);
      }
    }

    return renderClass(data.staticClass, data.class);
  }

  function mergeClassData(child, parent) {
    return {
      staticClass: concat(child.staticClass, parent.staticClass),
      class: isDef(child.class) ? [child.class, parent.class] : parent.class
    };
  }

  function renderClass(staticClass, dynamicClass) {
    if (isDef(staticClass) || isDef(dynamicClass)) {
      return concat(staticClass, stringifyClass(dynamicClass));
    }
    /* istanbul ignore next */


    return '';
  }

  function concat(a, b) {
    return a ? b ? a + ' ' + b : a : b || '';
  }

  function stringifyClass(value) {
    if (Array.isArray(value)) {
      return stringifyArray(value);
    }

    if (isObject(value)) {
      return stringifyObject(value);
    }

    if (typeof value === 'string') {
      return value;
    }
    /* istanbul ignore next */


    return '';
  }

  function stringifyArray(value) {
    var res = '';
    var stringified;

    for (var i = 0, l = value.length; i < l; i++) {
      if (isDef(stringified = stringifyClass(value[i])) && stringified !== '') {
        if (res) {
          res += ' ';
        }

        res += stringified;
      }
    }

    return res;
  }

  function stringifyObject(value) {
    var res = '';

    for (var key in value) {
      if (value[key]) {
        if (res) {
          res += ' ';
        }

        res += key;
      }
    }

    return res;
  }
  /*  */


  var namespaceMap = {
    svg: 'http://www.w3.org/2000/svg',
    math: 'http://www.w3.org/1998/Math/MathML'
  };
  var isHTMLTag = makeMap('html,body,base,head,link,meta,style,title,' + 'address,article,aside,footer,header,h1,h2,h3,h4,h5,h6,hgroup,nav,section,' + 'div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,' + 'a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,rtc,ruby,' + 's,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,' + 'embed,object,param,source,canvas,script,noscript,del,ins,' + 'caption,col,colgroup,table,thead,tbody,td,th,tr,' + 'button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,' + 'output,progress,select,textarea,' + 'details,dialog,menu,menuitem,summary,' + 'content,element,shadow,template,blockquote,iframe,tfoot'); // this map is intentionally selective, only covering SVG elements that may
  // contain child elements.

  var isSVG = makeMap('svg,animate,circle,clippath,cursor,defs,desc,ellipse,filter,font-face,' + 'foreignObject,g,glyph,image,line,marker,mask,missing-glyph,path,pattern,' + 'polygon,polyline,rect,switch,symbol,text,textpath,tspan,use,view', true);

  var isPreTag = function isPreTag(tag) {
    return tag === 'pre';
  };

  var isReservedTag = function isReservedTag(tag) {
    return isHTMLTag(tag) || isSVG(tag);
  };

  function getTagNamespace(tag) {
    if (isSVG(tag)) {
      return 'svg';
    } // basic support for MathML
    // note it doesn't support other MathML elements being component roots


    if (tag === 'math') {
      return 'math';
    }
  }

  var unknownElementCache = Object.create(null);

  function isUnknownElement(tag) {
    /* istanbul ignore if */
    if (!inBrowser) {
      return true;
    }

    if (isReservedTag(tag)) {
      return false;
    }

    tag = tag.toLowerCase();
    /* istanbul ignore if */

    if (unknownElementCache[tag] != null) {
      return unknownElementCache[tag];
    }

    var el = document.createElement(tag);

    if (tag.indexOf('-') > -1) {
      // http://stackoverflow.com/a/28210364/1070244
      return unknownElementCache[tag] = el.constructor === window.HTMLUnknownElement || el.constructor === window.HTMLElement;
    } else {
      return unknownElementCache[tag] = /HTMLUnknownElement/.test(el.toString());
    }
  }

  var isTextInputType = makeMap('text,number,password,search,email,tel,url');
  /*  */

  /**
   * Query an element selector if it's not an element already.
   */

  function query(el) {
    if (typeof el === 'string') {
      var selected = document.querySelector(el);

      if (!selected) {
        "development" !== 'production' && warn('Cannot find element: ' + el);
        return document.createElement('div');
      }

      return selected;
    } else {
      return el;
    }
  }
  /*  */


  function createElement$1(tagName, vnode) {
    var elm = document.createElement(tagName);

    if (tagName !== 'select') {
      return elm;
    } // false or null will remove the attribute but undefined will not


    if (vnode.data && vnode.data.attrs && vnode.data.attrs.multiple !== undefined) {
      elm.setAttribute('multiple', 'multiple');
    }

    return elm;
  }

  function createElementNS(namespace, tagName) {
    return document.createElementNS(namespaceMap[namespace], tagName);
  }

  function createTextNode(text) {
    return document.createTextNode(text);
  }

  function createComment(text) {
    return document.createComment(text);
  }

  function insertBefore(parentNode, newNode, referenceNode) {
    parentNode.insertBefore(newNode, referenceNode);
  }

  function removeChild(node, child) {
    node.removeChild(child);
  }

  function appendChild(node, child) {
    node.appendChild(child);
  }

  function parentNode(node) {
    return node.parentNode;
  }

  function nextSibling(node) {
    return node.nextSibling;
  }

  function tagName(node) {
    return node.tagName;
  }

  function setTextContent(node, text) {
    node.textContent = text;
  }

  function setStyleScope(node, scopeId) {
    node.setAttribute(scopeId, '');
  }

  var nodeOps = Object.freeze({
    createElement: createElement$1,
    createElementNS: createElementNS,
    createTextNode: createTextNode,
    createComment: createComment,
    insertBefore: insertBefore,
    removeChild: removeChild,
    appendChild: appendChild,
    parentNode: parentNode,
    nextSibling: nextSibling,
    tagName: tagName,
    setTextContent: setTextContent,
    setStyleScope: setStyleScope
  });
  /*  */

  var ref = {
    create: function create(_, vnode) {
      registerRef(vnode);
    },
    update: function update(oldVnode, vnode) {
      if (oldVnode.data.ref !== vnode.data.ref) {
        registerRef(oldVnode, true);
        registerRef(vnode);
      }
    },
    destroy: function destroy(vnode) {
      registerRef(vnode, true);
    }
  };

  function registerRef(vnode, isRemoval) {
    var key = vnode.data.ref;

    if (!isDef(key)) {
      return;
    }

    var vm = vnode.context;
    var ref = vnode.componentInstance || vnode.elm;
    var refs = vm.$refs;

    if (isRemoval) {
      if (Array.isArray(refs[key])) {
        remove(refs[key], ref);
      } else if (refs[key] === ref) {
        refs[key] = undefined;
      }
    } else {
      if (vnode.data.refInFor) {
        if (!Array.isArray(refs[key])) {
          refs[key] = [ref];
        } else if (refs[key].indexOf(ref) < 0) {
          // $flow-disable-line
          refs[key].push(ref);
        }
      } else {
        refs[key] = ref;
      }
    }
  }
  /**
   * Virtual DOM patching algorithm based on Snabbdom by
   * Simon Friis Vindum (@paldepind)
   * Licensed under the MIT License
   * https://github.com/paldepind/snabbdom/blob/master/LICENSE
   *
   * modified by Evan You (@yyx990803)
   *
   * Not type-checking this because this file is perf-critical and the cost
   * of making flow understand it is not worth it.
   */


  var emptyNode = new VNode('', {}, []);
  var hooks = ['create', 'activate', 'update', 'remove', 'destroy'];

  function sameVnode(a, b) {
    return a.key === b.key && (a.tag === b.tag && a.isComment === b.isComment && isDef(a.data) === isDef(b.data) && sameInputType(a, b) || isTrue(a.isAsyncPlaceholder) && a.asyncFactory === b.asyncFactory && isUndef(b.asyncFactory.error));
  }

  function sameInputType(a, b) {
    if (a.tag !== 'input') {
      return true;
    }

    var i;
    var typeA = isDef(i = a.data) && isDef(i = i.attrs) && i.type;
    var typeB = isDef(i = b.data) && isDef(i = i.attrs) && i.type;
    return typeA === typeB || isTextInputType(typeA) && isTextInputType(typeB);
  }

  function createKeyToOldIdx(children, beginIdx, endIdx) {
    var i, key;
    var map = {};

    for (i = beginIdx; i <= endIdx; ++i) {
      key = children[i].key;

      if (isDef(key)) {
        map[key] = i;
      }
    }

    return map;
  }

  function createPatchFunction(backend) {
    var i, j;
    var cbs = {};
    var modules = backend.modules;
    var nodeOps = backend.nodeOps;

    for (i = 0; i < hooks.length; ++i) {
      cbs[hooks[i]] = [];

      for (j = 0; j < modules.length; ++j) {
        if (isDef(modules[j][hooks[i]])) {
          cbs[hooks[i]].push(modules[j][hooks[i]]);
        }
      }
    }

    function emptyNodeAt(elm) {
      return new VNode(nodeOps.tagName(elm).toLowerCase(), {}, [], undefined, elm);
    }

    function createRmCb(childElm, listeners) {
      function remove() {
        if (--remove.listeners === 0) {
          removeNode(childElm);
        }
      }

      remove.listeners = listeners;
      return remove;
    }

    function removeNode(el) {
      var parent = nodeOps.parentNode(el); // element may have already been removed due to v-html / v-text

      if (isDef(parent)) {
        nodeOps.removeChild(parent, el);
      }
    }

    function isUnknownElement$$1(vnode, inVPre) {
      return !inVPre && !vnode.ns && !(config.ignoredElements.length && config.ignoredElements.some(function (ignore) {
        return isRegExp(ignore) ? ignore.test(vnode.tag) : ignore === vnode.tag;
      })) && config.isUnknownElement(vnode.tag);
    }

    var creatingElmInVPre = 0;

    function createElm(vnode, insertedVnodeQueue, parentElm, refElm, nested, ownerArray, index) {
      if (isDef(vnode.elm) && isDef(ownerArray)) {
        // This vnode was used in a previous render!
        // now it's used as a new node, overwriting its elm would cause
        // potential patch errors down the road when it's used as an insertion
        // reference node. Instead, we clone the node on-demand before creating
        // associated DOM element for it.
        vnode = ownerArray[index] = cloneVNode(vnode);
      }

      vnode.isRootInsert = !nested; // for transition enter check

      if (createComponent(vnode, insertedVnodeQueue, parentElm, refElm)) {
        return;
      }

      var data = vnode.data;
      var children = vnode.children;
      var tag = vnode.tag;

      if (isDef(tag)) {
        {
          if (data && data.pre) {
            creatingElmInVPre++;
          }

          if (isUnknownElement$$1(vnode, creatingElmInVPre)) {
            warn('Unknown custom element: <' + tag + '> - did you ' + 'register the component correctly? For recursive components, ' + 'make sure to provide the "name" option.', vnode.context);
          }
        }
        vnode.elm = vnode.ns ? nodeOps.createElementNS(vnode.ns, tag) : nodeOps.createElement(tag, vnode);
        setScope(vnode);
        /* istanbul ignore if */

        {
          createChildren(vnode, children, insertedVnodeQueue);

          if (isDef(data)) {
            invokeCreateHooks(vnode, insertedVnodeQueue);
          }

          insert(parentElm, vnode.elm, refElm);
        }

        if ("development" !== 'production' && data && data.pre) {
          creatingElmInVPre--;
        }
      } else if (isTrue(vnode.isComment)) {
        vnode.elm = nodeOps.createComment(vnode.text);
        insert(parentElm, vnode.elm, refElm);
      } else {
        vnode.elm = nodeOps.createTextNode(vnode.text);
        insert(parentElm, vnode.elm, refElm);
      }
    }

    function createComponent(vnode, insertedVnodeQueue, parentElm, refElm) {
      var i = vnode.data;

      if (isDef(i)) {
        var isReactivated = isDef(vnode.componentInstance) && i.keepAlive;

        if (isDef(i = i.hook) && isDef(i = i.init)) {
          i(vnode, false
          /* hydrating */
          , parentElm, refElm);
        } // after calling the init hook, if the vnode is a child component
        // it should've created a child instance and mounted it. the child
        // component also has set the placeholder vnode's elm.
        // in that case we can just return the element and be done.


        if (isDef(vnode.componentInstance)) {
          initComponent(vnode, insertedVnodeQueue);

          if (isTrue(isReactivated)) {
            reactivateComponent(vnode, insertedVnodeQueue, parentElm, refElm);
          }

          return true;
        }
      }
    }

    function initComponent(vnode, insertedVnodeQueue) {
      if (isDef(vnode.data.pendingInsert)) {
        insertedVnodeQueue.push.apply(insertedVnodeQueue, vnode.data.pendingInsert);
        vnode.data.pendingInsert = null;
      }

      vnode.elm = vnode.componentInstance.$el;

      if (isPatchable(vnode)) {
        invokeCreateHooks(vnode, insertedVnodeQueue);
        setScope(vnode);
      } else {
        // empty component root.
        // skip all element-related modules except for ref (#3455)
        registerRef(vnode); // make sure to invoke the insert hook

        insertedVnodeQueue.push(vnode);
      }
    }

    function reactivateComponent(vnode, insertedVnodeQueue, parentElm, refElm) {
      var i; // hack for #4339: a reactivated component with inner transition
      // does not trigger because the inner node's created hooks are not called
      // again. It's not ideal to involve module-specific logic in here but
      // there doesn't seem to be a better way to do it.

      var innerNode = vnode;

      while (innerNode.componentInstance) {
        innerNode = innerNode.componentInstance._vnode;

        if (isDef(i = innerNode.data) && isDef(i = i.transition)) {
          for (i = 0; i < cbs.activate.length; ++i) {
            cbs.activate[i](emptyNode, innerNode);
          }

          insertedVnodeQueue.push(innerNode);
          break;
        }
      } // unlike a newly created component,
      // a reactivated keep-alive component doesn't insert itself


      insert(parentElm, vnode.elm, refElm);
    }

    function insert(parent, elm, ref$$1) {
      if (isDef(parent)) {
        if (isDef(ref$$1)) {
          if (ref$$1.parentNode === parent) {
            nodeOps.insertBefore(parent, elm, ref$$1);
          }
        } else {
          nodeOps.appendChild(parent, elm);
        }
      }
    }

    function createChildren(vnode, children, insertedVnodeQueue) {
      if (Array.isArray(children)) {
        {
          checkDuplicateKeys(children);
        }

        for (var i = 0; i < children.length; ++i) {
          createElm(children[i], insertedVnodeQueue, vnode.elm, null, true, children, i);
        }
      } else if (isPrimitive(vnode.text)) {
        nodeOps.appendChild(vnode.elm, nodeOps.createTextNode(String(vnode.text)));
      }
    }

    function isPatchable(vnode) {
      while (vnode.componentInstance) {
        vnode = vnode.componentInstance._vnode;
      }

      return isDef(vnode.tag);
    }

    function invokeCreateHooks(vnode, insertedVnodeQueue) {
      for (var i$1 = 0; i$1 < cbs.create.length; ++i$1) {
        cbs.create[i$1](emptyNode, vnode);
      }

      i = vnode.data.hook; // Reuse variable

      if (isDef(i)) {
        if (isDef(i.create)) {
          i.create(emptyNode, vnode);
        }

        if (isDef(i.insert)) {
          insertedVnodeQueue.push(vnode);
        }
      }
    } // set scope id attribute for scoped CSS.
    // this is implemented as a special case to avoid the overhead
    // of going through the normal attribute patching process.


    function setScope(vnode) {
      var i;

      if (isDef(i = vnode.fnScopeId)) {
        nodeOps.setStyleScope(vnode.elm, i);
      } else {
        var ancestor = vnode;

        while (ancestor) {
          if (isDef(i = ancestor.context) && isDef(i = i.$options._scopeId)) {
            nodeOps.setStyleScope(vnode.elm, i);
          }

          ancestor = ancestor.parent;
        }
      } // for slot content they should also get the scopeId from the host instance.


      if (isDef(i = activeInstance) && i !== vnode.context && i !== vnode.fnContext && isDef(i = i.$options._scopeId)) {
        nodeOps.setStyleScope(vnode.elm, i);
      }
    }

    function addVnodes(parentElm, refElm, vnodes, startIdx, endIdx, insertedVnodeQueue) {
      for (; startIdx <= endIdx; ++startIdx) {
        createElm(vnodes[startIdx], insertedVnodeQueue, parentElm, refElm, false, vnodes, startIdx);
      }
    }

    function invokeDestroyHook(vnode) {
      var i, j;
      var data = vnode.data;

      if (isDef(data)) {
        if (isDef(i = data.hook) && isDef(i = i.destroy)) {
          i(vnode);
        }

        for (i = 0; i < cbs.destroy.length; ++i) {
          cbs.destroy[i](vnode);
        }
      }

      if (isDef(i = vnode.children)) {
        for (j = 0; j < vnode.children.length; ++j) {
          invokeDestroyHook(vnode.children[j]);
        }
      }
    }

    function removeVnodes(parentElm, vnodes, startIdx, endIdx) {
      for (; startIdx <= endIdx; ++startIdx) {
        var ch = vnodes[startIdx];

        if (isDef(ch)) {
          if (isDef(ch.tag)) {
            removeAndInvokeRemoveHook(ch);
            invokeDestroyHook(ch);
          } else {
            // Text node
            removeNode(ch.elm);
          }
        }
      }
    }

    function removeAndInvokeRemoveHook(vnode, rm) {
      if (isDef(rm) || isDef(vnode.data)) {
        var i;
        var listeners = cbs.remove.length + 1;

        if (isDef(rm)) {
          // we have a recursively passed down rm callback
          // increase the listeners count
          rm.listeners += listeners;
        } else {
          // directly removing
          rm = createRmCb(vnode.elm, listeners);
        } // recursively invoke hooks on child component root node


        if (isDef(i = vnode.componentInstance) && isDef(i = i._vnode) && isDef(i.data)) {
          removeAndInvokeRemoveHook(i, rm);
        }

        for (i = 0; i < cbs.remove.length; ++i) {
          cbs.remove[i](vnode, rm);
        }

        if (isDef(i = vnode.data.hook) && isDef(i = i.remove)) {
          i(vnode, rm);
        } else {
          rm();
        }
      } else {
        removeNode(vnode.elm);
      }
    }

    function updateChildren(parentElm, oldCh, newCh, insertedVnodeQueue, removeOnly) {
      var oldStartIdx = 0;
      var newStartIdx = 0;
      var oldEndIdx = oldCh.length - 1;
      var oldStartVnode = oldCh[0];
      var oldEndVnode = oldCh[oldEndIdx];
      var newEndIdx = newCh.length - 1;
      var newStartVnode = newCh[0];
      var newEndVnode = newCh[newEndIdx];
      var oldKeyToIdx, idxInOld, vnodeToMove, refElm; // removeOnly is a special flag used only by <transition-group>
      // to ensure removed elements stay in correct relative positions
      // during leaving transitions

      var canMove = !removeOnly;
      {
        checkDuplicateKeys(newCh);
      }

      while (oldStartIdx <= oldEndIdx && newStartIdx <= newEndIdx) {
        if (isUndef(oldStartVnode)) {
          oldStartVnode = oldCh[++oldStartIdx]; // Vnode has been moved left
        } else if (isUndef(oldEndVnode)) {
          oldEndVnode = oldCh[--oldEndIdx];
        } else if (sameVnode(oldStartVnode, newStartVnode)) {
          patchVnode(oldStartVnode, newStartVnode, insertedVnodeQueue);
          oldStartVnode = oldCh[++oldStartIdx];
          newStartVnode = newCh[++newStartIdx];
        } else if (sameVnode(oldEndVnode, newEndVnode)) {
          patchVnode(oldEndVnode, newEndVnode, insertedVnodeQueue);
          oldEndVnode = oldCh[--oldEndIdx];
          newEndVnode = newCh[--newEndIdx];
        } else if (sameVnode(oldStartVnode, newEndVnode)) {
          // Vnode moved right
          patchVnode(oldStartVnode, newEndVnode, insertedVnodeQueue);
          canMove && nodeOps.insertBefore(parentElm, oldStartVnode.elm, nodeOps.nextSibling(oldEndVnode.elm));
          oldStartVnode = oldCh[++oldStartIdx];
          newEndVnode = newCh[--newEndIdx];
        } else if (sameVnode(oldEndVnode, newStartVnode)) {
          // Vnode moved left
          patchVnode(oldEndVnode, newStartVnode, insertedVnodeQueue);
          canMove && nodeOps.insertBefore(parentElm, oldEndVnode.elm, oldStartVnode.elm);
          oldEndVnode = oldCh[--oldEndIdx];
          newStartVnode = newCh[++newStartIdx];
        } else {
          if (isUndef(oldKeyToIdx)) {
            oldKeyToIdx = createKeyToOldIdx(oldCh, oldStartIdx, oldEndIdx);
          }

          idxInOld = isDef(newStartVnode.key) ? oldKeyToIdx[newStartVnode.key] : findIdxInOld(newStartVnode, oldCh, oldStartIdx, oldEndIdx);

          if (isUndef(idxInOld)) {
            // New element
            createElm(newStartVnode, insertedVnodeQueue, parentElm, oldStartVnode.elm, false, newCh, newStartIdx);
          } else {
            vnodeToMove = oldCh[idxInOld];

            if (sameVnode(vnodeToMove, newStartVnode)) {
              patchVnode(vnodeToMove, newStartVnode, insertedVnodeQueue);
              oldCh[idxInOld] = undefined;
              canMove && nodeOps.insertBefore(parentElm, vnodeToMove.elm, oldStartVnode.elm);
            } else {
              // same key but different element. treat as new element
              createElm(newStartVnode, insertedVnodeQueue, parentElm, oldStartVnode.elm, false, newCh, newStartIdx);
            }
          }

          newStartVnode = newCh[++newStartIdx];
        }
      }

      if (oldStartIdx > oldEndIdx) {
        refElm = isUndef(newCh[newEndIdx + 1]) ? null : newCh[newEndIdx + 1].elm;
        addVnodes(parentElm, refElm, newCh, newStartIdx, newEndIdx, insertedVnodeQueue);
      } else if (newStartIdx > newEndIdx) {
        removeVnodes(parentElm, oldCh, oldStartIdx, oldEndIdx);
      }
    }

    function checkDuplicateKeys(children) {
      var seenKeys = {};

      for (var i = 0; i < children.length; i++) {
        var vnode = children[i];
        var key = vnode.key;

        if (isDef(key)) {
          if (seenKeys[key]) {
            warn("Duplicate keys detected: '" + key + "'. This may cause an update error.", vnode.context);
          } else {
            seenKeys[key] = true;
          }
        }
      }
    }

    function findIdxInOld(node, oldCh, start, end) {
      for (var i = start; i < end; i++) {
        var c = oldCh[i];

        if (isDef(c) && sameVnode(node, c)) {
          return i;
        }
      }
    }

    function patchVnode(oldVnode, vnode, insertedVnodeQueue, removeOnly) {
      if (oldVnode === vnode) {
        return;
      }

      var elm = vnode.elm = oldVnode.elm;

      if (isTrue(oldVnode.isAsyncPlaceholder)) {
        if (isDef(vnode.asyncFactory.resolved)) {
          hydrate(oldVnode.elm, vnode, insertedVnodeQueue);
        } else {
          vnode.isAsyncPlaceholder = true;
        }

        return;
      } // reuse element for static trees.
      // note we only do this if the vnode is cloned -
      // if the new node is not cloned it means the render functions have been
      // reset by the hot-reload-api and we need to do a proper re-render.


      if (isTrue(vnode.isStatic) && isTrue(oldVnode.isStatic) && vnode.key === oldVnode.key && (isTrue(vnode.isCloned) || isTrue(vnode.isOnce))) {
        vnode.componentInstance = oldVnode.componentInstance;
        return;
      }

      var i;
      var data = vnode.data;

      if (isDef(data) && isDef(i = data.hook) && isDef(i = i.prepatch)) {
        i(oldVnode, vnode);
      }

      var oldCh = oldVnode.children;
      var ch = vnode.children;

      if (isDef(data) && isPatchable(vnode)) {
        for (i = 0; i < cbs.update.length; ++i) {
          cbs.update[i](oldVnode, vnode);
        }

        if (isDef(i = data.hook) && isDef(i = i.update)) {
          i(oldVnode, vnode);
        }
      }

      if (isUndef(vnode.text)) {
        if (isDef(oldCh) && isDef(ch)) {
          if (oldCh !== ch) {
            updateChildren(elm, oldCh, ch, insertedVnodeQueue, removeOnly);
          }
        } else if (isDef(ch)) {
          if (isDef(oldVnode.text)) {
            nodeOps.setTextContent(elm, '');
          }

          addVnodes(elm, null, ch, 0, ch.length - 1, insertedVnodeQueue);
        } else if (isDef(oldCh)) {
          removeVnodes(elm, oldCh, 0, oldCh.length - 1);
        } else if (isDef(oldVnode.text)) {
          nodeOps.setTextContent(elm, '');
        }
      } else if (oldVnode.text !== vnode.text) {
        nodeOps.setTextContent(elm, vnode.text);
      }

      if (isDef(data)) {
        if (isDef(i = data.hook) && isDef(i = i.postpatch)) {
          i(oldVnode, vnode);
        }
      }
    }

    function invokeInsertHook(vnode, queue, initial) {
      // delay insert hooks for component root nodes, invoke them after the
      // element is really inserted
      if (isTrue(initial) && isDef(vnode.parent)) {
        vnode.parent.data.pendingInsert = queue;
      } else {
        for (var i = 0; i < queue.length; ++i) {
          queue[i].data.hook.insert(queue[i]);
        }
      }
    }

    var hydrationBailed = false; // list of modules that can skip create hook during hydration because they
    // are already rendered on the client or has no need for initialization
    // Note: style is excluded because it relies on initial clone for future
    // deep updates (#7063).

    var isRenderedModule = makeMap('attrs,class,staticClass,staticStyle,key'); // Note: this is a browser-only function so we can assume elms are DOM nodes.

    function hydrate(elm, vnode, insertedVnodeQueue, inVPre) {
      var i;
      var tag = vnode.tag;
      var data = vnode.data;
      var children = vnode.children;
      inVPre = inVPre || data && data.pre;
      vnode.elm = elm;

      if (isTrue(vnode.isComment) && isDef(vnode.asyncFactory)) {
        vnode.isAsyncPlaceholder = true;
        return true;
      } // assert node match


      {
        if (!assertNodeMatch(elm, vnode, inVPre)) {
          return false;
        }
      }

      if (isDef(data)) {
        if (isDef(i = data.hook) && isDef(i = i.init)) {
          i(vnode, true
          /* hydrating */
          );
        }

        if (isDef(i = vnode.componentInstance)) {
          // child component. it should have hydrated its own tree.
          initComponent(vnode, insertedVnodeQueue);
          return true;
        }
      }

      if (isDef(tag)) {
        if (isDef(children)) {
          // empty element, allow client to pick up and populate children
          if (!elm.hasChildNodes()) {
            createChildren(vnode, children, insertedVnodeQueue);
          } else {
            // v-html and domProps: innerHTML
            if (isDef(i = data) && isDef(i = i.domProps) && isDef(i = i.innerHTML)) {
              if (i !== elm.innerHTML) {
                /* istanbul ignore if */
                if ("development" !== 'production' && typeof console !== 'undefined' && !hydrationBailed) {
                  hydrationBailed = true;
                  console.warn('Parent: ', elm);
                  console.warn('server innerHTML: ', i);
                  console.warn('client innerHTML: ', elm.innerHTML);
                }

                return false;
              }
            } else {
              // iterate and compare children lists
              var childrenMatch = true;
              var childNode = elm.firstChild;

              for (var i$1 = 0; i$1 < children.length; i$1++) {
                if (!childNode || !hydrate(childNode, children[i$1], insertedVnodeQueue, inVPre)) {
                  childrenMatch = false;
                  break;
                }

                childNode = childNode.nextSibling;
              } // if childNode is not null, it means the actual childNodes list is
              // longer than the virtual children list.


              if (!childrenMatch || childNode) {
                /* istanbul ignore if */
                if ("development" !== 'production' && typeof console !== 'undefined' && !hydrationBailed) {
                  hydrationBailed = true;
                  console.warn('Parent: ', elm);
                  console.warn('Mismatching childNodes vs. VNodes: ', elm.childNodes, children);
                }

                return false;
              }
            }
          }
        }

        if (isDef(data)) {
          var fullInvoke = false;

          for (var key in data) {
            if (!isRenderedModule(key)) {
              fullInvoke = true;
              invokeCreateHooks(vnode, insertedVnodeQueue);
              break;
            }
          }

          if (!fullInvoke && data['class']) {
            // ensure collecting deps for deep class bindings for future updates
            traverse(data['class']);
          }
        }
      } else if (elm.data !== vnode.text) {
        elm.data = vnode.text;
      }

      return true;
    }

    function assertNodeMatch(node, vnode, inVPre) {
      if (isDef(vnode.tag)) {
        return vnode.tag.indexOf('vue-component') === 0 || !isUnknownElement$$1(vnode, inVPre) && vnode.tag.toLowerCase() === (node.tagName && node.tagName.toLowerCase());
      } else {
        return node.nodeType === (vnode.isComment ? 8 : 3);
      }
    }

    return function patch(oldVnode, vnode, hydrating, removeOnly, parentElm, refElm) {
      if (isUndef(vnode)) {
        if (isDef(oldVnode)) {
          invokeDestroyHook(oldVnode);
        }

        return;
      }

      var isInitialPatch = false;
      var insertedVnodeQueue = [];

      if (isUndef(oldVnode)) {
        // empty mount (likely as component), create new root element
        isInitialPatch = true;
        createElm(vnode, insertedVnodeQueue, parentElm, refElm);
      } else {
        var isRealElement = isDef(oldVnode.nodeType);

        if (!isRealElement && sameVnode(oldVnode, vnode)) {
          // patch existing root node
          patchVnode(oldVnode, vnode, insertedVnodeQueue, removeOnly);
        } else {
          if (isRealElement) {
            // mounting to a real element
            // check if this is server-rendered content and if we can perform
            // a successful hydration.
            if (oldVnode.nodeType === 1 && oldVnode.hasAttribute(SSR_ATTR)) {
              oldVnode.removeAttribute(SSR_ATTR);
              hydrating = true;
            }

            if (isTrue(hydrating)) {
              if (hydrate(oldVnode, vnode, insertedVnodeQueue)) {
                invokeInsertHook(vnode, insertedVnodeQueue, true);
                return oldVnode;
              } else {
                warn('The client-side rendered virtual DOM tree is not matching ' + 'server-rendered content. This is likely caused by incorrect ' + 'HTML markup, for example nesting block-level elements inside ' + '<p>, or missing <tbody>. Bailing hydration and performing ' + 'full client-side render.');
              }
            } // either not server-rendered, or hydration failed.
            // create an empty node and replace it


            oldVnode = emptyNodeAt(oldVnode);
          } // replacing existing element


          var oldElm = oldVnode.elm;
          var parentElm$1 = nodeOps.parentNode(oldElm); // create new node

          createElm(vnode, insertedVnodeQueue, // extremely rare edge case: do not insert if old element is in a
          // leaving transition. Only happens when combining transition +
          // keep-alive + HOCs. (#4590)
          oldElm._leaveCb ? null : parentElm$1, nodeOps.nextSibling(oldElm)); // update parent placeholder node element, recursively

          if (isDef(vnode.parent)) {
            var ancestor = vnode.parent;
            var patchable = isPatchable(vnode);

            while (ancestor) {
              for (var i = 0; i < cbs.destroy.length; ++i) {
                cbs.destroy[i](ancestor);
              }

              ancestor.elm = vnode.elm;

              if (patchable) {
                for (var i$1 = 0; i$1 < cbs.create.length; ++i$1) {
                  cbs.create[i$1](emptyNode, ancestor);
                } // #6513
                // invoke insert hooks that may have been merged by create hooks.
                // e.g. for directives that uses the "inserted" hook.


                var insert = ancestor.data.hook.insert;

                if (insert.merged) {
                  // start at index 1 to avoid re-invoking component mounted hook
                  for (var i$2 = 1; i$2 < insert.fns.length; i$2++) {
                    insert.fns[i$2]();
                  }
                }
              } else {
                registerRef(ancestor);
              }

              ancestor = ancestor.parent;
            }
          } // destroy old node


          if (isDef(parentElm$1)) {
            removeVnodes(parentElm$1, [oldVnode], 0, 0);
          } else if (isDef(oldVnode.tag)) {
            invokeDestroyHook(oldVnode);
          }
        }
      }

      invokeInsertHook(vnode, insertedVnodeQueue, isInitialPatch);
      return vnode.elm;
    };
  }
  /*  */


  var directives = {
    create: updateDirectives,
    update: updateDirectives,
    destroy: function unbindDirectives(vnode) {
      updateDirectives(vnode, emptyNode);
    }
  };

  function updateDirectives(oldVnode, vnode) {
    if (oldVnode.data.directives || vnode.data.directives) {
      _update(oldVnode, vnode);
    }
  }

  function _update(oldVnode, vnode) {
    var isCreate = oldVnode === emptyNode;
    var isDestroy = vnode === emptyNode;
    var oldDirs = normalizeDirectives$1(oldVnode.data.directives, oldVnode.context);
    var newDirs = normalizeDirectives$1(vnode.data.directives, vnode.context);
    var dirsWithInsert = [];
    var dirsWithPostpatch = [];
    var key, oldDir, dir;

    for (key in newDirs) {
      oldDir = oldDirs[key];
      dir = newDirs[key];

      if (!oldDir) {
        // new directive, bind
        callHook$1(dir, 'bind', vnode, oldVnode);

        if (dir.def && dir.def.inserted) {
          dirsWithInsert.push(dir);
        }
      } else {
        // existing directive, update
        dir.oldValue = oldDir.value;
        callHook$1(dir, 'update', vnode, oldVnode);

        if (dir.def && dir.def.componentUpdated) {
          dirsWithPostpatch.push(dir);
        }
      }
    }

    if (dirsWithInsert.length) {
      var callInsert = function callInsert() {
        for (var i = 0; i < dirsWithInsert.length; i++) {
          callHook$1(dirsWithInsert[i], 'inserted', vnode, oldVnode);
        }
      };

      if (isCreate) {
        mergeVNodeHook(vnode, 'insert', callInsert);
      } else {
        callInsert();
      }
    }

    if (dirsWithPostpatch.length) {
      mergeVNodeHook(vnode, 'postpatch', function () {
        for (var i = 0; i < dirsWithPostpatch.length; i++) {
          callHook$1(dirsWithPostpatch[i], 'componentUpdated', vnode, oldVnode);
        }
      });
    }

    if (!isCreate) {
      for (key in oldDirs) {
        if (!newDirs[key]) {
          // no longer present, unbind
          callHook$1(oldDirs[key], 'unbind', oldVnode, oldVnode, isDestroy);
        }
      }
    }
  }

  var emptyModifiers = Object.create(null);

  function normalizeDirectives$1(dirs, vm) {
    var res = Object.create(null);

    if (!dirs) {
      // $flow-disable-line
      return res;
    }

    var i, dir;

    for (i = 0; i < dirs.length; i++) {
      dir = dirs[i];

      if (!dir.modifiers) {
        // $flow-disable-line
        dir.modifiers = emptyModifiers;
      }

      res[getRawDirName(dir)] = dir;
      dir.def = resolveAsset(vm.$options, 'directives', dir.name, true);
    } // $flow-disable-line


    return res;
  }

  function getRawDirName(dir) {
    return dir.rawName || dir.name + "." + Object.keys(dir.modifiers || {}).join('.');
  }

  function callHook$1(dir, hook, vnode, oldVnode, isDestroy) {
    var fn = dir.def && dir.def[hook];

    if (fn) {
      try {
        fn(vnode.elm, dir, vnode, oldVnode, isDestroy);
      } catch (e) {
        handleError(e, vnode.context, "directive " + dir.name + " " + hook + " hook");
      }
    }
  }

  var baseModules = [ref, directives];
  /*  */

  function updateAttrs(oldVnode, vnode) {
    var opts = vnode.componentOptions;

    if (isDef(opts) && opts.Ctor.options.inheritAttrs === false) {
      return;
    }

    if (isUndef(oldVnode.data.attrs) && isUndef(vnode.data.attrs)) {
      return;
    }

    var key, cur, old;
    var elm = vnode.elm;
    var oldAttrs = oldVnode.data.attrs || {};
    var attrs = vnode.data.attrs || {}; // clone observed objects, as the user probably wants to mutate it

    if (isDef(attrs.__ob__)) {
      attrs = vnode.data.attrs = extend({}, attrs);
    }

    for (key in attrs) {
      cur = attrs[key];
      old = oldAttrs[key];

      if (old !== cur) {
        setAttr(elm, key, cur);
      }
    } // #4391: in IE9, setting type can reset value for input[type=radio]
    // #6666: IE/Edge forces progress value down to 1 before setting a max

    /* istanbul ignore if */


    if ((isIE || isEdge) && attrs.value !== oldAttrs.value) {
      setAttr(elm, 'value', attrs.value);
    }

    for (key in oldAttrs) {
      if (isUndef(attrs[key])) {
        if (isXlink(key)) {
          elm.removeAttributeNS(xlinkNS, getXlinkProp(key));
        } else if (!isEnumeratedAttr(key)) {
          elm.removeAttribute(key);
        }
      }
    }
  }

  function setAttr(el, key, value) {
    if (el.tagName.indexOf('-') > -1) {
      baseSetAttr(el, key, value);
    } else if (isBooleanAttr(key)) {
      // set attribute for blank value
      // e.g. <option disabled>Select one</option>
      if (isFalsyAttrValue(value)) {
        el.removeAttribute(key);
      } else {
        // technically allowfullscreen is a boolean attribute for <iframe>,
        // but Flash expects a value of "true" when used on <embed> tag
        value = key === 'allowfullscreen' && el.tagName === 'EMBED' ? 'true' : key;
        el.setAttribute(key, value);
      }
    } else if (isEnumeratedAttr(key)) {
      el.setAttribute(key, isFalsyAttrValue(value) || value === 'false' ? 'false' : 'true');
    } else if (isXlink(key)) {
      if (isFalsyAttrValue(value)) {
        el.removeAttributeNS(xlinkNS, getXlinkProp(key));
      } else {
        el.setAttributeNS(xlinkNS, key, value);
      }
    } else {
      baseSetAttr(el, key, value);
    }
  }

  function baseSetAttr(el, key, value) {
    if (isFalsyAttrValue(value)) {
      el.removeAttribute(key);
    } else {
      // #7138: IE10 & 11 fires input event when setting placeholder on
      // <textarea>... block the first input event and remove the blocker
      // immediately.

      /* istanbul ignore if */
      if (isIE && !isIE9 && el.tagName === 'TEXTAREA' && key === 'placeholder' && !el.__ieph) {
        var blocker = function blocker(e) {
          e.stopImmediatePropagation();
          el.removeEventListener('input', blocker);
        };

        el.addEventListener('input', blocker); // $flow-disable-line

        el.__ieph = true;
        /* IE placeholder patched */
      }

      el.setAttribute(key, value);
    }
  }

  var attrs = {
    create: updateAttrs,
    update: updateAttrs
    /*  */

  };

  function updateClass(oldVnode, vnode) {
    var el = vnode.elm;
    var data = vnode.data;
    var oldData = oldVnode.data;

    if (isUndef(data.staticClass) && isUndef(data.class) && (isUndef(oldData) || isUndef(oldData.staticClass) && isUndef(oldData.class))) {
      return;
    }

    var cls = genClassForVnode(vnode); // handle transition classes

    var transitionClass = el._transitionClasses;

    if (isDef(transitionClass)) {
      cls = concat(cls, stringifyClass(transitionClass));
    } // set the class


    if (cls !== el._prevClass) {
      el.setAttribute('class', cls);
      el._prevClass = cls;
    }
  }

  var klass = {
    create: updateClass,
    update: updateClass
    /*  */

  };
  var validDivisionCharRE = /[\w).+\-_$\]]/;

  function parseFilters(exp) {
    var inSingle = false;
    var inDouble = false;
    var inTemplateString = false;
    var inRegex = false;
    var curly = 0;
    var square = 0;
    var paren = 0;
    var lastFilterIndex = 0;
    var c, prev, i, expression, filters;

    for (i = 0; i < exp.length; i++) {
      prev = c;
      c = exp.charCodeAt(i);

      if (inSingle) {
        if (c === 0x27 && prev !== 0x5C) {
          inSingle = false;
        }
      } else if (inDouble) {
        if (c === 0x22 && prev !== 0x5C) {
          inDouble = false;
        }
      } else if (inTemplateString) {
        if (c === 0x60 && prev !== 0x5C) {
          inTemplateString = false;
        }
      } else if (inRegex) {
        if (c === 0x2f && prev !== 0x5C) {
          inRegex = false;
        }
      } else if (c === 0x7C && // pipe
      exp.charCodeAt(i + 1) !== 0x7C && exp.charCodeAt(i - 1) !== 0x7C && !curly && !square && !paren) {
        if (expression === undefined) {
          // first filter, end of expression
          lastFilterIndex = i + 1;
          expression = exp.slice(0, i).trim();
        } else {
          pushFilter();
        }
      } else {
        switch (c) {
          case 0x22:
            inDouble = true;
            break;
          // "

          case 0x27:
            inSingle = true;
            break;
          // '

          case 0x60:
            inTemplateString = true;
            break;
          // `

          case 0x28:
            paren++;
            break;
          // (

          case 0x29:
            paren--;
            break;
          // )

          case 0x5B:
            square++;
            break;
          // [

          case 0x5D:
            square--;
            break;
          // ]

          case 0x7B:
            curly++;
            break;
          // {

          case 0x7D:
            curly--;
            break;
          // }
        }

        if (c === 0x2f) {
          // /
          var j = i - 1;
          var p = void 0; // find first non-whitespace prev char

          for (; j >= 0; j--) {
            p = exp.charAt(j);

            if (p !== ' ') {
              break;
            }
          }

          if (!p || !validDivisionCharRE.test(p)) {
            inRegex = true;
          }
        }
      }
    }

    if (expression === undefined) {
      expression = exp.slice(0, i).trim();
    } else if (lastFilterIndex !== 0) {
      pushFilter();
    }

    function pushFilter() {
      (filters || (filters = [])).push(exp.slice(lastFilterIndex, i).trim());
      lastFilterIndex = i + 1;
    }

    if (filters) {
      for (i = 0; i < filters.length; i++) {
        expression = wrapFilter(expression, filters[i]);
      }
    }

    return expression;
  }

  function wrapFilter(exp, filter) {
    var i = filter.indexOf('(');

    if (i < 0) {
      // _f: resolveFilter
      return "_f(\"" + filter + "\")(" + exp + ")";
    } else {
      var name = filter.slice(0, i);
      var args = filter.slice(i + 1);
      return "_f(\"" + name + "\")(" + exp + (args !== ')' ? ',' + args : args);
    }
  }
  /*  */


  function baseWarn(msg) {
    console.error("[Vue compiler]: " + msg);
  }

  function pluckModuleFunction(modules, key) {
    return modules ? modules.map(function (m) {
      return m[key];
    }).filter(function (_) {
      return _;
    }) : [];
  }

  function addProp(el, name, value) {
    (el.props || (el.props = [])).push({
      name: name,
      value: value
    });
    el.plain = false;
  }

  function addAttr(el, name, value) {
    (el.attrs || (el.attrs = [])).push({
      name: name,
      value: value
    });
    el.plain = false;
  } // add a raw attr (use this in preTransforms)


  function addRawAttr(el, name, value) {
    el.attrsMap[name] = value;
    el.attrsList.push({
      name: name,
      value: value
    });
  }

  function addDirective(el, name, rawName, value, arg, modifiers) {
    (el.directives || (el.directives = [])).push({
      name: name,
      rawName: rawName,
      value: value,
      arg: arg,
      modifiers: modifiers
    });
    el.plain = false;
  }

  function addHandler(el, name, value, modifiers, important, warn) {
    modifiers = modifiers || emptyObject; // warn prevent and passive modifier

    /* istanbul ignore if */

    if ("development" !== 'production' && warn && modifiers.prevent && modifiers.passive) {
      warn('passive and prevent can\'t be used together. ' + 'Passive handler can\'t prevent default event.');
    } // check capture modifier


    if (modifiers.capture) {
      delete modifiers.capture;
      name = '!' + name; // mark the event as captured
    }

    if (modifiers.once) {
      delete modifiers.once;
      name = '~' + name; // mark the event as once
    }
    /* istanbul ignore if */


    if (modifiers.passive) {
      delete modifiers.passive;
      name = '&' + name; // mark the event as passive
    } // normalize click.right and click.middle since they don't actually fire
    // this is technically browser-specific, but at least for now browsers are
    // the only target envs that have right/middle clicks.


    if (name === 'click') {
      if (modifiers.right) {
        name = 'contextmenu';
        delete modifiers.right;
      } else if (modifiers.middle) {
        name = 'mouseup';
      }
    }

    var events;

    if (modifiers.native) {
      delete modifiers.native;
      events = el.nativeEvents || (el.nativeEvents = {});
    } else {
      events = el.events || (el.events = {});
    }

    var newHandler = {
      value: value.trim()
    };

    if (modifiers !== emptyObject) {
      newHandler.modifiers = modifiers;
    }

    var handlers = events[name];
    /* istanbul ignore if */

    if (Array.isArray(handlers)) {
      important ? handlers.unshift(newHandler) : handlers.push(newHandler);
    } else if (handlers) {
      events[name] = important ? [newHandler, handlers] : [handlers, newHandler];
    } else {
      events[name] = newHandler;
    }

    el.plain = false;
  }

  function getBindingAttr(el, name, getStatic) {
    var dynamicValue = getAndRemoveAttr(el, ':' + name) || getAndRemoveAttr(el, 'v-bind:' + name);

    if (dynamicValue != null) {
      return parseFilters(dynamicValue);
    } else if (getStatic !== false) {
      var staticValue = getAndRemoveAttr(el, name);

      if (staticValue != null) {
        return JSON.stringify(staticValue);
      }
    }
  } // note: this only removes the attr from the Array (attrsList) so that it
  // doesn't get processed by processAttrs.
  // By default it does NOT remove it from the map (attrsMap) because the map is
  // needed during codegen.


  function getAndRemoveAttr(el, name, removeFromMap) {
    var val;

    if ((val = el.attrsMap[name]) != null) {
      var list = el.attrsList;

      for (var i = 0, l = list.length; i < l; i++) {
        if (list[i].name === name) {
          list.splice(i, 1);
          break;
        }
      }
    }

    if (removeFromMap) {
      delete el.attrsMap[name];
    }

    return val;
  }
  /*  */

  /**
   * Cross-platform code generation for component v-model
   */


  function genComponentModel(el, value, modifiers) {
    var ref = modifiers || {};
    var number = ref.number;
    var trim = ref.trim;
    var baseValueExpression = '$$v';
    var valueExpression = baseValueExpression;

    if (trim) {
      valueExpression = "(typeof " + baseValueExpression + " === 'string'" + "? " + baseValueExpression + ".trim()" + ": " + baseValueExpression + ")";
    }

    if (number) {
      valueExpression = "_n(" + valueExpression + ")";
    }

    var assignment = genAssignmentCode(value, valueExpression);
    el.model = {
      value: "(" + value + ")",
      expression: "\"" + value + "\"",
      callback: "function (" + baseValueExpression + ") {" + assignment + "}"
    };
  }
  /**
   * Cross-platform codegen helper for generating v-model value assignment code.
   */


  function genAssignmentCode(value, assignment) {
    var res = parseModel(value);

    if (res.key === null) {
      return value + "=" + assignment;
    } else {
      return "$set(" + res.exp + ", " + res.key + ", " + assignment + ")";
    }
  }
  /**
   * Parse a v-model expression into a base path and a final key segment.
   * Handles both dot-path and possible square brackets.
   *
   * Possible cases:
   *
   * - test
   * - test[key]
   * - test[test1[key]]
   * - test["a"][key]
   * - xxx.test[a[a].test1[key]]
   * - test.xxx.a["asa"][test1[key]]
   *
   */


  var len;
  var str;
  var chr;
  var index$1;
  var expressionPos;
  var expressionEndPos;

  function parseModel(val) {
    // Fix https://github.com/vuejs/vue/pull/7730
    // allow v-model="obj.val " (trailing whitespace)
    val = val.trim();
    len = val.length;

    if (val.indexOf('[') < 0 || val.lastIndexOf(']') < len - 1) {
      index$1 = val.lastIndexOf('.');

      if (index$1 > -1) {
        return {
          exp: val.slice(0, index$1),
          key: '"' + val.slice(index$1 + 1) + '"'
        };
      } else {
        return {
          exp: val,
          key: null
        };
      }
    }

    str = val;
    index$1 = expressionPos = expressionEndPos = 0;

    while (!eof()) {
      chr = next();
      /* istanbul ignore if */

      if (isStringStart(chr)) {
        parseString(chr);
      } else if (chr === 0x5B) {
        parseBracket(chr);
      }
    }

    return {
      exp: val.slice(0, expressionPos),
      key: val.slice(expressionPos + 1, expressionEndPos)
    };
  }

  function next() {
    return str.charCodeAt(++index$1);
  }

  function eof() {
    return index$1 >= len;
  }

  function isStringStart(chr) {
    return chr === 0x22 || chr === 0x27;
  }

  function parseBracket(chr) {
    var inBracket = 1;
    expressionPos = index$1;

    while (!eof()) {
      chr = next();

      if (isStringStart(chr)) {
        parseString(chr);
        continue;
      }

      if (chr === 0x5B) {
        inBracket++;
      }

      if (chr === 0x5D) {
        inBracket--;
      }

      if (inBracket === 0) {
        expressionEndPos = index$1;
        break;
      }
    }
  }

  function parseString(chr) {
    var stringQuote = chr;

    while (!eof()) {
      chr = next();

      if (chr === stringQuote) {
        break;
      }
    }
  }
  /*  */


  var warn$1; // in some cases, the event used has to be determined at runtime
  // so we used some reserved tokens during compile.

  var RANGE_TOKEN = '__r';
  var CHECKBOX_RADIO_TOKEN = '__c';

  function model(el, dir, _warn) {
    warn$1 = _warn;
    var value = dir.value;
    var modifiers = dir.modifiers;
    var tag = el.tag;
    var type = el.attrsMap.type;
    {
      // inputs with type="file" are read only and setting the input's
      // value will throw an error.
      if (tag === 'input' && type === 'file') {
        warn$1("<" + el.tag + " v-model=\"" + value + "\" type=\"file\">:\n" + "File inputs are read only. Use a v-on:change listener instead.");
      }
    }

    if (el.component) {
      genComponentModel(el, value, modifiers); // component v-model doesn't need extra runtime

      return false;
    } else if (tag === 'select') {
      genSelect(el, value, modifiers);
    } else if (tag === 'input' && type === 'checkbox') {
      genCheckboxModel(el, value, modifiers);
    } else if (tag === 'input' && type === 'radio') {
      genRadioModel(el, value, modifiers);
    } else if (tag === 'input' || tag === 'textarea') {
      genDefaultModel(el, value, modifiers);
    } else if (!config.isReservedTag(tag)) {
      genComponentModel(el, value, modifiers); // component v-model doesn't need extra runtime

      return false;
    } else {
      warn$1("<" + el.tag + " v-model=\"" + value + "\">: " + "v-model is not supported on this element type. " + 'If you are working with contenteditable, it\'s recommended to ' + 'wrap a library dedicated for that purpose inside a custom component.');
    } // ensure runtime directive metadata


    return true;
  }

  function genCheckboxModel(el, value, modifiers) {
    var number = modifiers && modifiers.number;
    var valueBinding = getBindingAttr(el, 'value') || 'null';
    var trueValueBinding = getBindingAttr(el, 'true-value') || 'true';
    var falseValueBinding = getBindingAttr(el, 'false-value') || 'false';
    addProp(el, 'checked', "Array.isArray(" + value + ")" + "?_i(" + value + "," + valueBinding + ")>-1" + (trueValueBinding === 'true' ? ":(" + value + ")" : ":_q(" + value + "," + trueValueBinding + ")"));
    addHandler(el, 'change', "var $$a=" + value + "," + '$$el=$event.target,' + "$$c=$$el.checked?(" + trueValueBinding + "):(" + falseValueBinding + ");" + 'if(Array.isArray($$a)){' + "var $$v=" + (number ? '_n(' + valueBinding + ')' : valueBinding) + "," + '$$i=_i($$a,$$v);' + "if($$el.checked){$$i<0&&(" + genAssignmentCode(value, '$$a.concat([$$v])') + ")}" + "else{$$i>-1&&(" + genAssignmentCode(value, '$$a.slice(0,$$i).concat($$a.slice($$i+1))') + ")}" + "}else{" + genAssignmentCode(value, '$$c') + "}", null, true);
  }

  function genRadioModel(el, value, modifiers) {
    var number = modifiers && modifiers.number;
    var valueBinding = getBindingAttr(el, 'value') || 'null';
    valueBinding = number ? "_n(" + valueBinding + ")" : valueBinding;
    addProp(el, 'checked', "_q(" + value + "," + valueBinding + ")");
    addHandler(el, 'change', genAssignmentCode(value, valueBinding), null, true);
  }

  function genSelect(el, value, modifiers) {
    var number = modifiers && modifiers.number;
    var selectedVal = "Array.prototype.filter" + ".call($event.target.options,function(o){return o.selected})" + ".map(function(o){var val = \"_value\" in o ? o._value : o.value;" + "return " + (number ? '_n(val)' : 'val') + "})";
    var assignment = '$event.target.multiple ? $$selectedVal : $$selectedVal[0]';
    var code = "var $$selectedVal = " + selectedVal + ";";
    code = code + " " + genAssignmentCode(value, assignment);
    addHandler(el, 'change', code, null, true);
  }

  function genDefaultModel(el, value, modifiers) {
    var type = el.attrsMap.type; // warn if v-bind:value conflicts with v-model
    // except for inputs with v-bind:type

    {
      var value$1 = el.attrsMap['v-bind:value'] || el.attrsMap[':value'];
      var typeBinding = el.attrsMap['v-bind:type'] || el.attrsMap[':type'];

      if (value$1 && !typeBinding) {
        var binding = el.attrsMap['v-bind:value'] ? 'v-bind:value' : ':value';
        warn$1(binding + "=\"" + value$1 + "\" conflicts with v-model on the same element " + 'because the latter already expands to a value binding internally');
      }
    }
    var ref = modifiers || {};
    var lazy = ref.lazy;
    var number = ref.number;
    var trim = ref.trim;
    var needCompositionGuard = !lazy && type !== 'range';
    var event = lazy ? 'change' : type === 'range' ? RANGE_TOKEN : 'input';
    var valueExpression = '$event.target.value';

    if (trim) {
      valueExpression = "$event.target.value.trim()";
    }

    if (number) {
      valueExpression = "_n(" + valueExpression + ")";
    }

    var code = genAssignmentCode(value, valueExpression);

    if (needCompositionGuard) {
      code = "if($event.target.composing)return;" + code;
    }

    addProp(el, 'value', "(" + value + ")");
    addHandler(el, event, code, null, true);

    if (trim || number) {
      addHandler(el, 'blur', '$forceUpdate()');
    }
  }
  /*  */
  // normalize v-model event tokens that can only be determined at runtime.
  // it's important to place the event as the first in the array because
  // the whole point is ensuring the v-model callback gets called before
  // user-attached handlers.


  function normalizeEvents(on) {
    /* istanbul ignore if */
    if (isDef(on[RANGE_TOKEN])) {
      // IE input[type=range] only supports `change` event
      var event = isIE ? 'change' : 'input';
      on[event] = [].concat(on[RANGE_TOKEN], on[event] || []);
      delete on[RANGE_TOKEN];
    } // This was originally intended to fix #4521 but no longer necessary
    // after 2.5. Keeping it for backwards compat with generated code from < 2.4

    /* istanbul ignore if */


    if (isDef(on[CHECKBOX_RADIO_TOKEN])) {
      on.change = [].concat(on[CHECKBOX_RADIO_TOKEN], on.change || []);
      delete on[CHECKBOX_RADIO_TOKEN];
    }
  }

  var target$1;

  function createOnceHandler(handler, event, capture) {
    var _target = target$1; // save current target element in closure

    return function onceHandler() {
      var res = handler.apply(null, arguments);

      if (res !== null) {
        remove$2(event, onceHandler, capture, _target);
      }
    };
  }

  function add$1(event, handler, once$$1, capture, passive) {
    handler = withMacroTask(handler);

    if (once$$1) {
      handler = createOnceHandler(handler, event, capture);
    }

    target$1.addEventListener(event, handler, supportsPassive ? {
      capture: capture,
      passive: passive
    } : capture);
  }

  function remove$2(event, handler, capture, _target) {
    (_target || target$1).removeEventListener(event, handler._withTask || handler, capture);
  }

  function updateDOMListeners(oldVnode, vnode) {
    if (isUndef(oldVnode.data.on) && isUndef(vnode.data.on)) {
      return;
    }

    var on = vnode.data.on || {};
    var oldOn = oldVnode.data.on || {};
    target$1 = vnode.elm;
    normalizeEvents(on);
    updateListeners(on, oldOn, add$1, remove$2, vnode.context);
    target$1 = undefined;
  }

  var events = {
    create: updateDOMListeners,
    update: updateDOMListeners
    /*  */

  };

  function updateDOMProps(oldVnode, vnode) {
    if (isUndef(oldVnode.data.domProps) && isUndef(vnode.data.domProps)) {
      return;
    }

    var key, cur;
    var elm = vnode.elm;
    var oldProps = oldVnode.data.domProps || {};
    var props = vnode.data.domProps || {}; // clone observed objects, as the user probably wants to mutate it

    if (isDef(props.__ob__)) {
      props = vnode.data.domProps = extend({}, props);
    }

    for (key in oldProps) {
      if (isUndef(props[key])) {
        elm[key] = '';
      }
    }

    for (key in props) {
      cur = props[key]; // ignore children if the node has textContent or innerHTML,
      // as these will throw away existing DOM nodes and cause removal errors
      // on subsequent patches (#3360)

      if (key === 'textContent' || key === 'innerHTML') {
        if (vnode.children) {
          vnode.children.length = 0;
        }

        if (cur === oldProps[key]) {
          continue;
        } // #6601 work around Chrome version <= 55 bug where single textNode
        // replaced by innerHTML/textContent retains its parentNode property


        if (elm.childNodes.length === 1) {
          elm.removeChild(elm.childNodes[0]);
        }
      }

      if (key === 'value') {
        // store value as _value as well since
        // non-string values will be stringified
        elm._value = cur; // avoid resetting cursor position when value is the same

        var strCur = isUndef(cur) ? '' : String(cur);

        if (shouldUpdateValue(elm, strCur)) {
          elm.value = strCur;
        }
      } else {
        elm[key] = cur;
      }
    }
  } // check platforms/web/util/attrs.js acceptValue


  function shouldUpdateValue(elm, checkVal) {
    return !elm.composing && (elm.tagName === 'OPTION' || isNotInFocusAndDirty(elm, checkVal) || isDirtyWithModifiers(elm, checkVal));
  }

  function isNotInFocusAndDirty(elm, checkVal) {
    // return true when textbox (.number and .trim) loses focus and its value is
    // not equal to the updated value
    var notInFocus = true; // #6157
    // work around IE bug when accessing document.activeElement in an iframe

    try {
      notInFocus = document.activeElement !== elm;
    } catch (e) {}

    return notInFocus && elm.value !== checkVal;
  }

  function isDirtyWithModifiers(elm, newVal) {
    var value = elm.value;
    var modifiers = elm._vModifiers; // injected by v-model runtime

    if (isDef(modifiers)) {
      if (modifiers.lazy) {
        // inputs with lazy should only be updated when not in focus
        return false;
      }

      if (modifiers.number) {
        return toNumber(value) !== toNumber(newVal);
      }

      if (modifiers.trim) {
        return value.trim() !== newVal.trim();
      }
    }

    return value !== newVal;
  }

  var domProps = {
    create: updateDOMProps,
    update: updateDOMProps
    /*  */

  };
  var parseStyleText = cached(function (cssText) {
    var res = {};
    var listDelimiter = /;(?![^(]*\))/g;
    var propertyDelimiter = /:(.+)/;
    cssText.split(listDelimiter).forEach(function (item) {
      if (item) {
        var tmp = item.split(propertyDelimiter);
        tmp.length > 1 && (res[tmp[0].trim()] = tmp[1].trim());
      }
    });
    return res;
  }); // merge static and dynamic style data on the same vnode

  function normalizeStyleData(data) {
    var style = normalizeStyleBinding(data.style); // static style is pre-processed into an object during compilation
    // and is always a fresh object, so it's safe to merge into it

    return data.staticStyle ? extend(data.staticStyle, style) : style;
  } // normalize possible array / string values into Object


  function normalizeStyleBinding(bindingStyle) {
    if (Array.isArray(bindingStyle)) {
      return toObject(bindingStyle);
    }

    if (typeof bindingStyle === 'string') {
      return parseStyleText(bindingStyle);
    }

    return bindingStyle;
  }
  /**
   * parent component style should be after child's
   * so that parent component's style could override it
   */


  function getStyle(vnode, checkChild) {
    var res = {};
    var styleData;

    if (checkChild) {
      var childNode = vnode;

      while (childNode.componentInstance) {
        childNode = childNode.componentInstance._vnode;

        if (childNode && childNode.data && (styleData = normalizeStyleData(childNode.data))) {
          extend(res, styleData);
        }
      }
    }

    if (styleData = normalizeStyleData(vnode.data)) {
      extend(res, styleData);
    }

    var parentNode = vnode;

    while (parentNode = parentNode.parent) {
      if (parentNode.data && (styleData = normalizeStyleData(parentNode.data))) {
        extend(res, styleData);
      }
    }

    return res;
  }
  /*  */


  var cssVarRE = /^--/;
  var importantRE = /\s*!important$/;

  var setProp = function setProp(el, name, val) {
    /* istanbul ignore if */
    if (cssVarRE.test(name)) {
      el.style.setProperty(name, val);
    } else if (importantRE.test(val)) {
      el.style.setProperty(name, val.replace(importantRE, ''), 'important');
    } else {
      var normalizedName = normalize(name);

      if (Array.isArray(val)) {
        // Support values array created by autoprefixer, e.g.
        // {display: ["-webkit-box", "-ms-flexbox", "flex"]}
        // Set them one by one, and the browser will only set those it can recognize
        for (var i = 0, len = val.length; i < len; i++) {
          el.style[normalizedName] = val[i];
        }
      } else {
        el.style[normalizedName] = val;
      }
    }
  };

  var vendorNames = ['Webkit', 'Moz', 'ms'];
  var emptyStyle;
  var normalize = cached(function (prop) {
    emptyStyle = emptyStyle || document.createElement('div').style;
    prop = camelize(prop);

    if (prop !== 'filter' && prop in emptyStyle) {
      return prop;
    }

    var capName = prop.charAt(0).toUpperCase() + prop.slice(1);

    for (var i = 0; i < vendorNames.length; i++) {
      var name = vendorNames[i] + capName;

      if (name in emptyStyle) {
        return name;
      }
    }
  });

  function updateStyle(oldVnode, vnode) {
    var data = vnode.data;
    var oldData = oldVnode.data;

    if (isUndef(data.staticStyle) && isUndef(data.style) && isUndef(oldData.staticStyle) && isUndef(oldData.style)) {
      return;
    }

    var cur, name;
    var el = vnode.elm;
    var oldStaticStyle = oldData.staticStyle;
    var oldStyleBinding = oldData.normalizedStyle || oldData.style || {}; // if static style exists, stylebinding already merged into it when doing normalizeStyleData

    var oldStyle = oldStaticStyle || oldStyleBinding;
    var style = normalizeStyleBinding(vnode.data.style) || {}; // store normalized style under a different key for next diff
    // make sure to clone it if it's reactive, since the user likely wants
    // to mutate it.

    vnode.data.normalizedStyle = isDef(style.__ob__) ? extend({}, style) : style;
    var newStyle = getStyle(vnode, true);

    for (name in oldStyle) {
      if (isUndef(newStyle[name])) {
        setProp(el, name, '');
      }
    }

    for (name in newStyle) {
      cur = newStyle[name];

      if (cur !== oldStyle[name]) {
        // ie9 setting to null has no effect, must use empty string
        setProp(el, name, cur == null ? '' : cur);
      }
    }
  }

  var style = {
    create: updateStyle,
    update: updateStyle
    /*  */

    /**
     * Add class with compatibility for SVG since classList is not supported on
     * SVG elements in IE
     */

  };

  function addClass(el, cls) {
    /* istanbul ignore if */
    if (!cls || !(cls = cls.trim())) {
      return;
    }
    /* istanbul ignore else */


    if (el.classList) {
      if (cls.indexOf(' ') > -1) {
        cls.split(/\s+/).forEach(function (c) {
          return el.classList.add(c);
        });
      } else {
        el.classList.add(cls);
      }
    } else {
      var cur = " " + (el.getAttribute('class') || '') + " ";

      if (cur.indexOf(' ' + cls + ' ') < 0) {
        el.setAttribute('class', (cur + cls).trim());
      }
    }
  }
  /**
   * Remove class with compatibility for SVG since classList is not supported on
   * SVG elements in IE
   */


  function removeClass(el, cls) {
    /* istanbul ignore if */
    if (!cls || !(cls = cls.trim())) {
      return;
    }
    /* istanbul ignore else */


    if (el.classList) {
      if (cls.indexOf(' ') > -1) {
        cls.split(/\s+/).forEach(function (c) {
          return el.classList.remove(c);
        });
      } else {
        el.classList.remove(cls);
      }

      if (!el.classList.length) {
        el.removeAttribute('class');
      }
    } else {
      var cur = " " + (el.getAttribute('class') || '') + " ";
      var tar = ' ' + cls + ' ';

      while (cur.indexOf(tar) >= 0) {
        cur = cur.replace(tar, ' ');
      }

      cur = cur.trim();

      if (cur) {
        el.setAttribute('class', cur);
      } else {
        el.removeAttribute('class');
      }
    }
  }
  /*  */


  function resolveTransition(def) {
    if (!def) {
      return;
    }
    /* istanbul ignore else */


    if (_typeof(def) === 'object') {
      var res = {};

      if (def.css !== false) {
        extend(res, autoCssTransition(def.name || 'v'));
      }

      extend(res, def);
      return res;
    } else if (typeof def === 'string') {
      return autoCssTransition(def);
    }
  }

  var autoCssTransition = cached(function (name) {
    return {
      enterClass: name + "-enter",
      enterToClass: name + "-enter-to",
      enterActiveClass: name + "-enter-active",
      leaveClass: name + "-leave",
      leaveToClass: name + "-leave-to",
      leaveActiveClass: name + "-leave-active"
    };
  });
  var hasTransition = inBrowser && !isIE9;
  var TRANSITION = 'transition';
  var ANIMATION = 'animation'; // Transition property/event sniffing

  var transitionProp = 'transition';
  var transitionEndEvent = 'transitionend';
  var animationProp = 'animation';
  var animationEndEvent = 'animationend';

  if (hasTransition) {
    /* istanbul ignore if */
    if (window.ontransitionend === undefined && window.onwebkittransitionend !== undefined) {
      transitionProp = 'WebkitTransition';
      transitionEndEvent = 'webkitTransitionEnd';
    }

    if (window.onanimationend === undefined && window.onwebkitanimationend !== undefined) {
      animationProp = 'WebkitAnimation';
      animationEndEvent = 'webkitAnimationEnd';
    }
  } // binding to window is necessary to make hot reload work in IE in strict mode


  var raf = inBrowser ? window.requestAnimationFrame ? window.requestAnimationFrame.bind(window) : setTimeout :
  /* istanbul ignore next */
  function (fn) {
    return fn();
  };

  function nextFrame(fn) {
    raf(function () {
      raf(fn);
    });
  }

  function addTransitionClass(el, cls) {
    var transitionClasses = el._transitionClasses || (el._transitionClasses = []);

    if (transitionClasses.indexOf(cls) < 0) {
      transitionClasses.push(cls);
      addClass(el, cls);
    }
  }

  function removeTransitionClass(el, cls) {
    if (el._transitionClasses) {
      remove(el._transitionClasses, cls);
    }

    removeClass(el, cls);
  }

  function whenTransitionEnds(el, expectedType, cb) {
    var ref = getTransitionInfo(el, expectedType);
    var type = ref.type;
    var timeout = ref.timeout;
    var propCount = ref.propCount;

    if (!type) {
      return cb();
    }

    var event = type === TRANSITION ? transitionEndEvent : animationEndEvent;
    var ended = 0;

    var end = function end() {
      el.removeEventListener(event, onEnd);
      cb();
    };

    var onEnd = function onEnd(e) {
      if (e.target === el) {
        if (++ended >= propCount) {
          end();
        }
      }
    };

    setTimeout(function () {
      if (ended < propCount) {
        end();
      }
    }, timeout + 1);
    el.addEventListener(event, onEnd);
  }

  var transformRE = /\b(transform|all)(,|$)/;

  function getTransitionInfo(el, expectedType) {
    var styles = window.getComputedStyle(el);
    var transitionDelays = styles[transitionProp + 'Delay'].split(', ');
    var transitionDurations = styles[transitionProp + 'Duration'].split(', ');
    var transitionTimeout = getTimeout(transitionDelays, transitionDurations);
    var animationDelays = styles[animationProp + 'Delay'].split(', ');
    var animationDurations = styles[animationProp + 'Duration'].split(', ');
    var animationTimeout = getTimeout(animationDelays, animationDurations);
    var type;
    var timeout = 0;
    var propCount = 0;
    /* istanbul ignore if */

    if (expectedType === TRANSITION) {
      if (transitionTimeout > 0) {
        type = TRANSITION;
        timeout = transitionTimeout;
        propCount = transitionDurations.length;
      }
    } else if (expectedType === ANIMATION) {
      if (animationTimeout > 0) {
        type = ANIMATION;
        timeout = animationTimeout;
        propCount = animationDurations.length;
      }
    } else {
      timeout = Math.max(transitionTimeout, animationTimeout);
      type = timeout > 0 ? transitionTimeout > animationTimeout ? TRANSITION : ANIMATION : null;
      propCount = type ? type === TRANSITION ? transitionDurations.length : animationDurations.length : 0;
    }

    var hasTransform = type === TRANSITION && transformRE.test(styles[transitionProp + 'Property']);
    return {
      type: type,
      timeout: timeout,
      propCount: propCount,
      hasTransform: hasTransform
    };
  }

  function getTimeout(delays, durations) {
    /* istanbul ignore next */
    while (delays.length < durations.length) {
      delays = delays.concat(delays);
    }

    return Math.max.apply(null, durations.map(function (d, i) {
      return toMs(d) + toMs(delays[i]);
    }));
  }

  function toMs(s) {
    return Number(s.slice(0, -1)) * 1000;
  }
  /*  */


  function enter(vnode, toggleDisplay) {
    var el = vnode.elm; // call leave callback now

    if (isDef(el._leaveCb)) {
      el._leaveCb.cancelled = true;

      el._leaveCb();
    }

    var data = resolveTransition(vnode.data.transition);

    if (isUndef(data)) {
      return;
    }
    /* istanbul ignore if */


    if (isDef(el._enterCb) || el.nodeType !== 1) {
      return;
    }

    var css = data.css;
    var type = data.type;
    var enterClass = data.enterClass;
    var enterToClass = data.enterToClass;
    var enterActiveClass = data.enterActiveClass;
    var appearClass = data.appearClass;
    var appearToClass = data.appearToClass;
    var appearActiveClass = data.appearActiveClass;
    var beforeEnter = data.beforeEnter;
    var enter = data.enter;
    var afterEnter = data.afterEnter;
    var enterCancelled = data.enterCancelled;
    var beforeAppear = data.beforeAppear;
    var appear = data.appear;
    var afterAppear = data.afterAppear;
    var appearCancelled = data.appearCancelled;
    var duration = data.duration; // activeInstance will always be the <transition> component managing this
    // transition. One edge case to check is when the <transition> is placed
    // as the root node of a child component. In that case we need to check
    // <transition>'s parent for appear check.

    var context = activeInstance;
    var transitionNode = activeInstance.$vnode;

    while (transitionNode && transitionNode.parent) {
      transitionNode = transitionNode.parent;
      context = transitionNode.context;
    }

    var isAppear = !context._isMounted || !vnode.isRootInsert;

    if (isAppear && !appear && appear !== '') {
      return;
    }

    var startClass = isAppear && appearClass ? appearClass : enterClass;
    var activeClass = isAppear && appearActiveClass ? appearActiveClass : enterActiveClass;
    var toClass = isAppear && appearToClass ? appearToClass : enterToClass;
    var beforeEnterHook = isAppear ? beforeAppear || beforeEnter : beforeEnter;
    var enterHook = isAppear ? typeof appear === 'function' ? appear : enter : enter;
    var afterEnterHook = isAppear ? afterAppear || afterEnter : afterEnter;
    var enterCancelledHook = isAppear ? appearCancelled || enterCancelled : enterCancelled;
    var explicitEnterDuration = toNumber(isObject(duration) ? duration.enter : duration);

    if ("development" !== 'production' && explicitEnterDuration != null) {
      checkDuration(explicitEnterDuration, 'enter', vnode);
    }

    var expectsCSS = css !== false && !isIE9;
    var userWantsControl = getHookArgumentsLength(enterHook);
    var cb = el._enterCb = once(function () {
      if (expectsCSS) {
        removeTransitionClass(el, toClass);
        removeTransitionClass(el, activeClass);
      }

      if (cb.cancelled) {
        if (expectsCSS) {
          removeTransitionClass(el, startClass);
        }

        enterCancelledHook && enterCancelledHook(el);
      } else {
        afterEnterHook && afterEnterHook(el);
      }

      el._enterCb = null;
    });

    if (!vnode.data.show) {
      // remove pending leave element on enter by injecting an insert hook
      mergeVNodeHook(vnode, 'insert', function () {
        var parent = el.parentNode;
        var pendingNode = parent && parent._pending && parent._pending[vnode.key];

        if (pendingNode && pendingNode.tag === vnode.tag && pendingNode.elm._leaveCb) {
          pendingNode.elm._leaveCb();
        }

        enterHook && enterHook(el, cb);
      });
    } // start enter transition


    beforeEnterHook && beforeEnterHook(el);

    if (expectsCSS) {
      addTransitionClass(el, startClass);
      addTransitionClass(el, activeClass);
      nextFrame(function () {
        removeTransitionClass(el, startClass);

        if (!cb.cancelled) {
          addTransitionClass(el, toClass);

          if (!userWantsControl) {
            if (isValidDuration(explicitEnterDuration)) {
              setTimeout(cb, explicitEnterDuration);
            } else {
              whenTransitionEnds(el, type, cb);
            }
          }
        }
      });
    }

    if (vnode.data.show) {
      toggleDisplay && toggleDisplay();
      enterHook && enterHook(el, cb);
    }

    if (!expectsCSS && !userWantsControl) {
      cb();
    }
  }

  function leave(vnode, rm) {
    var el = vnode.elm; // call enter callback now

    if (isDef(el._enterCb)) {
      el._enterCb.cancelled = true;

      el._enterCb();
    }

    var data = resolveTransition(vnode.data.transition);

    if (isUndef(data) || el.nodeType !== 1) {
      return rm();
    }
    /* istanbul ignore if */


    if (isDef(el._leaveCb)) {
      return;
    }

    var css = data.css;
    var type = data.type;
    var leaveClass = data.leaveClass;
    var leaveToClass = data.leaveToClass;
    var leaveActiveClass = data.leaveActiveClass;
    var beforeLeave = data.beforeLeave;
    var leave = data.leave;
    var afterLeave = data.afterLeave;
    var leaveCancelled = data.leaveCancelled;
    var delayLeave = data.delayLeave;
    var duration = data.duration;
    var expectsCSS = css !== false && !isIE9;
    var userWantsControl = getHookArgumentsLength(leave);
    var explicitLeaveDuration = toNumber(isObject(duration) ? duration.leave : duration);

    if ("development" !== 'production' && isDef(explicitLeaveDuration)) {
      checkDuration(explicitLeaveDuration, 'leave', vnode);
    }

    var cb = el._leaveCb = once(function () {
      if (el.parentNode && el.parentNode._pending) {
        el.parentNode._pending[vnode.key] = null;
      }

      if (expectsCSS) {
        removeTransitionClass(el, leaveToClass);
        removeTransitionClass(el, leaveActiveClass);
      }

      if (cb.cancelled) {
        if (expectsCSS) {
          removeTransitionClass(el, leaveClass);
        }

        leaveCancelled && leaveCancelled(el);
      } else {
        rm();
        afterLeave && afterLeave(el);
      }

      el._leaveCb = null;
    });

    if (delayLeave) {
      delayLeave(performLeave);
    } else {
      performLeave();
    }

    function performLeave() {
      // the delayed leave may have already been cancelled
      if (cb.cancelled) {
        return;
      } // record leaving element


      if (!vnode.data.show) {
        (el.parentNode._pending || (el.parentNode._pending = {}))[vnode.key] = vnode;
      }

      beforeLeave && beforeLeave(el);

      if (expectsCSS) {
        addTransitionClass(el, leaveClass);
        addTransitionClass(el, leaveActiveClass);
        nextFrame(function () {
          removeTransitionClass(el, leaveClass);

          if (!cb.cancelled) {
            addTransitionClass(el, leaveToClass);

            if (!userWantsControl) {
              if (isValidDuration(explicitLeaveDuration)) {
                setTimeout(cb, explicitLeaveDuration);
              } else {
                whenTransitionEnds(el, type, cb);
              }
            }
          }
        });
      }

      leave && leave(el, cb);

      if (!expectsCSS && !userWantsControl) {
        cb();
      }
    }
  } // only used in dev mode


  function checkDuration(val, name, vnode) {
    if (typeof val !== 'number') {
      warn("<transition> explicit " + name + " duration is not a valid number - " + "got " + JSON.stringify(val) + ".", vnode.context);
    } else if (isNaN(val)) {
      warn("<transition> explicit " + name + " duration is NaN - " + 'the duration expression might be incorrect.', vnode.context);
    }
  }

  function isValidDuration(val) {
    return typeof val === 'number' && !isNaN(val);
  }
  /**
   * Normalize a transition hook's argument length. The hook may be:
   * - a merged hook (invoker) with the original in .fns
   * - a wrapped component method (check ._length)
   * - a plain function (.length)
   */


  function getHookArgumentsLength(fn) {
    if (isUndef(fn)) {
      return false;
    }

    var invokerFns = fn.fns;

    if (isDef(invokerFns)) {
      // invoker
      return getHookArgumentsLength(Array.isArray(invokerFns) ? invokerFns[0] : invokerFns);
    } else {
      return (fn._length || fn.length) > 1;
    }
  }

  function _enter(_, vnode) {
    if (vnode.data.show !== true) {
      enter(vnode);
    }
  }

  var transition = inBrowser ? {
    create: _enter,
    activate: _enter,
    remove: function remove$$1(vnode, rm) {
      /* istanbul ignore else */
      if (vnode.data.show !== true) {
        leave(vnode, rm);
      } else {
        rm();
      }
    }
  } : {};
  var platformModules = [attrs, klass, events, domProps, style, transition];
  /*  */
  // the directive module should be applied last, after all
  // built-in modules have been applied.

  var modules = platformModules.concat(baseModules);
  var patch = createPatchFunction({
    nodeOps: nodeOps,
    modules: modules
  });
  /**
   * Not type checking this file because flow doesn't like attaching
   * properties to Elements.
   */

  /* istanbul ignore if */

  if (isIE9) {
    // http://www.matts411.com/post/internet-explorer-9-oninput/
    document.addEventListener('selectionchange', function () {
      var el = document.activeElement;

      if (el && el.vmodel) {
        trigger(el, 'input');
      }
    });
  }

  var directive = {
    inserted: function inserted(el, binding, vnode, oldVnode) {
      if (vnode.tag === 'select') {
        // #6903
        if (oldVnode.elm && !oldVnode.elm._vOptions) {
          mergeVNodeHook(vnode, 'postpatch', function () {
            directive.componentUpdated(el, binding, vnode);
          });
        } else {
          setSelected(el, binding, vnode.context);
        }

        el._vOptions = [].map.call(el.options, getValue);
      } else if (vnode.tag === 'textarea' || isTextInputType(el.type)) {
        el._vModifiers = binding.modifiers;

        if (!binding.modifiers.lazy) {
          el.addEventListener('compositionstart', onCompositionStart);
          el.addEventListener('compositionend', onCompositionEnd); // Safari < 10.2 & UIWebView doesn't fire compositionend when
          // switching focus before confirming composition choice
          // this also fixes the issue where some browsers e.g. iOS Chrome
          // fires "change" instead of "input" on autocomplete.

          el.addEventListener('change', onCompositionEnd);
          /* istanbul ignore if */

          if (isIE9) {
            el.vmodel = true;
          }
        }
      }
    },
    componentUpdated: function componentUpdated(el, binding, vnode) {
      if (vnode.tag === 'select') {
        setSelected(el, binding, vnode.context); // in case the options rendered by v-for have changed,
        // it's possible that the value is out-of-sync with the rendered options.
        // detect such cases and filter out values that no longer has a matching
        // option in the DOM.

        var prevOptions = el._vOptions;
        var curOptions = el._vOptions = [].map.call(el.options, getValue);

        if (curOptions.some(function (o, i) {
          return !looseEqual(o, prevOptions[i]);
        })) {
          // trigger change event if
          // no matching option found for at least one value
          var needReset = el.multiple ? binding.value.some(function (v) {
            return hasNoMatchingOption(v, curOptions);
          }) : binding.value !== binding.oldValue && hasNoMatchingOption(binding.value, curOptions);

          if (needReset) {
            trigger(el, 'change');
          }
        }
      }
    }
  };

  function setSelected(el, binding, vm) {
    actuallySetSelected(el, binding, vm);
    /* istanbul ignore if */

    if (isIE || isEdge) {
      setTimeout(function () {
        actuallySetSelected(el, binding, vm);
      }, 0);
    }
  }

  function actuallySetSelected(el, binding, vm) {
    var value = binding.value;
    var isMultiple = el.multiple;

    if (isMultiple && !Array.isArray(value)) {
      "development" !== 'production' && warn("<select multiple v-model=\"" + binding.expression + "\"> " + "expects an Array value for its binding, but got " + Object.prototype.toString.call(value).slice(8, -1), vm);
      return;
    }

    var selected, option;

    for (var i = 0, l = el.options.length; i < l; i++) {
      option = el.options[i];

      if (isMultiple) {
        selected = looseIndexOf(value, getValue(option)) > -1;

        if (option.selected !== selected) {
          option.selected = selected;
        }
      } else {
        if (looseEqual(getValue(option), value)) {
          if (el.selectedIndex !== i) {
            el.selectedIndex = i;
          }

          return;
        }
      }
    }

    if (!isMultiple) {
      el.selectedIndex = -1;
    }
  }

  function hasNoMatchingOption(value, options) {
    return options.every(function (o) {
      return !looseEqual(o, value);
    });
  }

  function getValue(option) {
    return '_value' in option ? option._value : option.value;
  }

  function onCompositionStart(e) {
    e.target.composing = true;
  }

  function onCompositionEnd(e) {
    // prevent triggering an input event for no reason
    if (!e.target.composing) {
      return;
    }

    e.target.composing = false;
    trigger(e.target, 'input');
  }

  function trigger(el, type) {
    var e = document.createEvent('HTMLEvents');
    e.initEvent(type, true, true);
    el.dispatchEvent(e);
  }
  /*  */
  // recursively search for possible transition defined inside the component root


  function locateNode(vnode) {
    return vnode.componentInstance && (!vnode.data || !vnode.data.transition) ? locateNode(vnode.componentInstance._vnode) : vnode;
  }

  var show = {
    bind: function bind(el, ref, vnode) {
      var value = ref.value;
      vnode = locateNode(vnode);
      var transition$$1 = vnode.data && vnode.data.transition;
      var originalDisplay = el.__vOriginalDisplay = el.style.display === 'none' ? '' : el.style.display;

      if (value && transition$$1) {
        vnode.data.show = true;
        enter(vnode, function () {
          el.style.display = originalDisplay;
        });
      } else {
        el.style.display = value ? originalDisplay : 'none';
      }
    },
    update: function update(el, ref, vnode) {
      var value = ref.value;
      var oldValue = ref.oldValue;
      /* istanbul ignore if */

      if (!value === !oldValue) {
        return;
      }

      vnode = locateNode(vnode);
      var transition$$1 = vnode.data && vnode.data.transition;

      if (transition$$1) {
        vnode.data.show = true;

        if (value) {
          enter(vnode, function () {
            el.style.display = el.__vOriginalDisplay;
          });
        } else {
          leave(vnode, function () {
            el.style.display = 'none';
          });
        }
      } else {
        el.style.display = value ? el.__vOriginalDisplay : 'none';
      }
    },
    unbind: function unbind(el, binding, vnode, oldVnode, isDestroy) {
      if (!isDestroy) {
        el.style.display = el.__vOriginalDisplay;
      }
    }
  };
  var platformDirectives = {
    model: directive,
    show: show
    /*  */
    // Provides transition support for a single element/component.
    // supports transition mode (out-in / in-out)

  };
  var transitionProps = {
    name: String,
    appear: Boolean,
    css: Boolean,
    mode: String,
    type: String,
    enterClass: String,
    leaveClass: String,
    enterToClass: String,
    leaveToClass: String,
    enterActiveClass: String,
    leaveActiveClass: String,
    appearClass: String,
    appearActiveClass: String,
    appearToClass: String,
    duration: [Number, String, Object]
  }; // in case the child is also an abstract component, e.g. <keep-alive>
  // we want to recursively retrieve the real component to be rendered

  function getRealChild(vnode) {
    var compOptions = vnode && vnode.componentOptions;

    if (compOptions && compOptions.Ctor.options.abstract) {
      return getRealChild(getFirstComponentChild(compOptions.children));
    } else {
      return vnode;
    }
  }

  function extractTransitionData(comp) {
    var data = {};
    var options = comp.$options; // props

    for (var key in options.propsData) {
      data[key] = comp[key];
    } // events.
    // extract listeners and pass them directly to the transition methods


    var listeners = options._parentListeners;

    for (var key$1 in listeners) {
      data[camelize(key$1)] = listeners[key$1];
    }

    return data;
  }

  function placeholder(h, rawChild) {
    if (/\d-keep-alive$/.test(rawChild.tag)) {
      return h('keep-alive', {
        props: rawChild.componentOptions.propsData
      });
    }
  }

  function hasParentTransition(vnode) {
    while (vnode = vnode.parent) {
      if (vnode.data.transition) {
        return true;
      }
    }
  }

  function isSameChild(child, oldChild) {
    return oldChild.key === child.key && oldChild.tag === child.tag;
  }

  var Transition = {
    name: 'transition',
    props: transitionProps,
    abstract: true,
    render: function render(h) {
      var this$1 = this;
      var children = this.$slots.default;

      if (!children) {
        return;
      } // filter out text nodes (possible whitespaces)


      children = children.filter(function (c) {
        return c.tag || isAsyncPlaceholder(c);
      });
      /* istanbul ignore if */

      if (!children.length) {
        return;
      } // warn multiple elements


      if ("development" !== 'production' && children.length > 1) {
        warn('<transition> can only be used on a single element. Use ' + '<transition-group> for lists.', this.$parent);
      }

      var mode = this.mode; // warn invalid mode

      if ("development" !== 'production' && mode && mode !== 'in-out' && mode !== 'out-in') {
        warn('invalid <transition> mode: ' + mode, this.$parent);
      }

      var rawChild = children[0]; // if this is a component root node and the component's
      // parent container node also has transition, skip.

      if (hasParentTransition(this.$vnode)) {
        return rawChild;
      } // apply transition data to child
      // use getRealChild() to ignore abstract components e.g. keep-alive


      var child = getRealChild(rawChild);
      /* istanbul ignore if */

      if (!child) {
        return rawChild;
      }

      if (this._leaving) {
        return placeholder(h, rawChild);
      } // ensure a key that is unique to the vnode type and to this transition
      // component instance. This key will be used to remove pending leaving nodes
      // during entering.


      var id = "__transition-" + this._uid + "-";
      child.key = child.key == null ? child.isComment ? id + 'comment' : id + child.tag : isPrimitive(child.key) ? String(child.key).indexOf(id) === 0 ? child.key : id + child.key : child.key;
      var data = (child.data || (child.data = {})).transition = extractTransitionData(this);
      var oldRawChild = this._vnode;
      var oldChild = getRealChild(oldRawChild); // mark v-show
      // so that the transition module can hand over the control to the directive

      if (child.data.directives && child.data.directives.some(function (d) {
        return d.name === 'show';
      })) {
        child.data.show = true;
      }

      if (oldChild && oldChild.data && !isSameChild(child, oldChild) && !isAsyncPlaceholder(oldChild) && // #6687 component root is a comment node
      !(oldChild.componentInstance && oldChild.componentInstance._vnode.isComment)) {
        // replace old child transition data with fresh one
        // important for dynamic transitions!
        var oldData = oldChild.data.transition = extend({}, data); // handle transition mode

        if (mode === 'out-in') {
          // return placeholder node and queue update when leave finishes
          this._leaving = true;
          mergeVNodeHook(oldData, 'afterLeave', function () {
            this$1._leaving = false;
            this$1.$forceUpdate();
          });
          return placeholder(h, rawChild);
        } else if (mode === 'in-out') {
          if (isAsyncPlaceholder(child)) {
            return oldRawChild;
          }

          var delayedLeave;

          var performLeave = function performLeave() {
            delayedLeave();
          };

          mergeVNodeHook(data, 'afterEnter', performLeave);
          mergeVNodeHook(data, 'enterCancelled', performLeave);
          mergeVNodeHook(oldData, 'delayLeave', function (leave) {
            delayedLeave = leave;
          });
        }
      }

      return rawChild;
    }
    /*  */
    // Provides transition support for list items.
    // supports move transitions using the FLIP technique.
    // Because the vdom's children update algorithm is "unstable" - i.e.
    // it doesn't guarantee the relative positioning of removed elements,
    // we force transition-group to update its children into two passes:
    // in the first pass, we remove all nodes that need to be removed,
    // triggering their leaving transition; in the second pass, we insert/move
    // into the final desired state. This way in the second pass removed
    // nodes will remain where they should be.

  };
  var props = extend({
    tag: String,
    moveClass: String
  }, transitionProps);
  delete props.mode;
  var TransitionGroup = {
    props: props,
    render: function render(h) {
      var tag = this.tag || this.$vnode.data.tag || 'span';
      var map = Object.create(null);
      var prevChildren = this.prevChildren = this.children;
      var rawChildren = this.$slots.default || [];
      var children = this.children = [];
      var transitionData = extractTransitionData(this);

      for (var i = 0; i < rawChildren.length; i++) {
        var c = rawChildren[i];

        if (c.tag) {
          if (c.key != null && String(c.key).indexOf('__vlist') !== 0) {
            children.push(c);
            map[c.key] = c;
            (c.data || (c.data = {})).transition = transitionData;
          } else {
            var opts = c.componentOptions;
            var name = opts ? opts.Ctor.options.name || opts.tag || '' : c.tag;
            warn("<transition-group> children must be keyed: <" + name + ">");
          }
        }
      }

      if (prevChildren) {
        var kept = [];
        var removed = [];

        for (var i$1 = 0; i$1 < prevChildren.length; i$1++) {
          var c$1 = prevChildren[i$1];
          c$1.data.transition = transitionData;
          c$1.data.pos = c$1.elm.getBoundingClientRect();

          if (map[c$1.key]) {
            kept.push(c$1);
          } else {
            removed.push(c$1);
          }
        }

        this.kept = h(tag, null, kept);
        this.removed = removed;
      }

      return h(tag, null, children);
    },
    beforeUpdate: function beforeUpdate() {
      // force removing pass
      this.__patch__(this._vnode, this.kept, false, // hydrating
      true // removeOnly (!important, avoids unnecessary moves)
      );

      this._vnode = this.kept;
    },
    updated: function updated() {
      var children = this.prevChildren;
      var moveClass = this.moveClass || (this.name || 'v') + '-move';

      if (!children.length || !this.hasMove(children[0].elm, moveClass)) {
        return;
      } // we divide the work into three loops to avoid mixing DOM reads and writes
      // in each iteration - which helps prevent layout thrashing.


      children.forEach(callPendingCbs);
      children.forEach(recordPosition);
      children.forEach(applyTranslation); // force reflow to put everything in position
      // assign to this to avoid being removed in tree-shaking
      // $flow-disable-line

      this._reflow = document.body.offsetHeight;
      children.forEach(function (c) {
        if (c.data.moved) {
          var el = c.elm;
          var s = el.style;
          addTransitionClass(el, moveClass);
          s.transform = s.WebkitTransform = s.transitionDuration = '';
          el.addEventListener(transitionEndEvent, el._moveCb = function cb(e) {
            if (!e || /transform$/.test(e.propertyName)) {
              el.removeEventListener(transitionEndEvent, cb);
              el._moveCb = null;
              removeTransitionClass(el, moveClass);
            }
          });
        }
      });
    },
    methods: {
      hasMove: function hasMove(el, moveClass) {
        /* istanbul ignore if */
        if (!hasTransition) {
          return false;
        }
        /* istanbul ignore if */


        if (this._hasMove) {
          return this._hasMove;
        } // Detect whether an element with the move class applied has
        // CSS transitions. Since the element may be inside an entering
        // transition at this very moment, we make a clone of it and remove
        // all other transition classes applied to ensure only the move class
        // is applied.


        var clone = el.cloneNode();

        if (el._transitionClasses) {
          el._transitionClasses.forEach(function (cls) {
            removeClass(clone, cls);
          });
        }

        addClass(clone, moveClass);
        clone.style.display = 'none';
        this.$el.appendChild(clone);
        var info = getTransitionInfo(clone);
        this.$el.removeChild(clone);
        return this._hasMove = info.hasTransform;
      }
    }
  };

  function callPendingCbs(c) {
    /* istanbul ignore if */
    if (c.elm._moveCb) {
      c.elm._moveCb();
    }
    /* istanbul ignore if */


    if (c.elm._enterCb) {
      c.elm._enterCb();
    }
  }

  function recordPosition(c) {
    c.data.newPos = c.elm.getBoundingClientRect();
  }

  function applyTranslation(c) {
    var oldPos = c.data.pos;
    var newPos = c.data.newPos;
    var dx = oldPos.left - newPos.left;
    var dy = oldPos.top - newPos.top;

    if (dx || dy) {
      c.data.moved = true;
      var s = c.elm.style;
      s.transform = s.WebkitTransform = "translate(" + dx + "px," + dy + "px)";
      s.transitionDuration = '0s';
    }
  }

  var platformComponents = {
    Transition: Transition,
    TransitionGroup: TransitionGroup
    /*  */
    // install platform specific utils

  };
  Vue.config.mustUseProp = mustUseProp;
  Vue.config.isReservedTag = isReservedTag;
  Vue.config.isReservedAttr = isReservedAttr;
  Vue.config.getTagNamespace = getTagNamespace;
  Vue.config.isUnknownElement = isUnknownElement; // install platform runtime directives & components

  extend(Vue.options.directives, platformDirectives);
  extend(Vue.options.components, platformComponents); // install platform patch function

  Vue.prototype.__patch__ = inBrowser ? patch : noop; // public mount method

  Vue.prototype.$mount = function (el, hydrating) {
    el = el && inBrowser ? query(el) : undefined;
    return mountComponent(this, el, hydrating);
  }; // devtools global hook

  /* istanbul ignore next */


  if (inBrowser) {
    setTimeout(function () {
      if (config.devtools) {
        if (devtools) {
          devtools.emit('init', Vue);
        } else if ("development" !== 'production' && "development" !== 'test' && isChrome) {
          console[console.info ? 'info' : 'log']('Download the Vue Devtools extension for a better development experience:\n' + 'https://github.com/vuejs/vue-devtools');
        }
      }

      if ("development" !== 'production' && "development" !== 'test' && config.productionTip !== false && typeof console !== 'undefined') {
        console[console.info ? 'info' : 'log']("You are running Vue in development mode.\n" + "Make sure to turn on production mode when deploying for production.\n" + "See more tips at https://vuejs.org/guide/deployment.html");
      }
    }, 0);
  }
  /*  */


  var defaultTagRE = /\{\{((?:.|\n)+?)\}\}/g;
  var regexEscapeRE = /[-.*+?^${}()|[\]\/\\]/g;
  var buildRegex = cached(function (delimiters) {
    var open = delimiters[0].replace(regexEscapeRE, '\\$&');
    var close = delimiters[1].replace(regexEscapeRE, '\\$&');
    return new RegExp(open + '((?:.|\\n)+?)' + close, 'g');
  });

  function parseText(text, delimiters) {
    var tagRE = delimiters ? buildRegex(delimiters) : defaultTagRE;

    if (!tagRE.test(text)) {
      return;
    }

    var tokens = [];
    var rawTokens = [];
    var lastIndex = tagRE.lastIndex = 0;
    var match, index, tokenValue;

    while (match = tagRE.exec(text)) {
      index = match.index; // push text token

      if (index > lastIndex) {
        rawTokens.push(tokenValue = text.slice(lastIndex, index));
        tokens.push(JSON.stringify(tokenValue));
      } // tag token


      var exp = parseFilters(match[1].trim());
      tokens.push("_s(" + exp + ")");
      rawTokens.push({
        '@binding': exp
      });
      lastIndex = index + match[0].length;
    }

    if (lastIndex < text.length) {
      rawTokens.push(tokenValue = text.slice(lastIndex));
      tokens.push(JSON.stringify(tokenValue));
    }

    return {
      expression: tokens.join('+'),
      tokens: rawTokens
    };
  }
  /*  */


  function transformNode(el, options) {
    var warn = options.warn || baseWarn;
    var staticClass = getAndRemoveAttr(el, 'class');

    if ("development" !== 'production' && staticClass) {
      var res = parseText(staticClass, options.delimiters);

      if (res) {
        warn("class=\"" + staticClass + "\": " + 'Interpolation inside attributes has been removed. ' + 'Use v-bind or the colon shorthand instead. For example, ' + 'instead of <div class="{{ val }}">, use <div :class="val">.');
      }
    }

    if (staticClass) {
      el.staticClass = JSON.stringify(staticClass);
    }

    var classBinding = getBindingAttr(el, 'class', false
    /* getStatic */
    );

    if (classBinding) {
      el.classBinding = classBinding;
    }
  }

  function genData(el) {
    var data = '';

    if (el.staticClass) {
      data += "staticClass:" + el.staticClass + ",";
    }

    if (el.classBinding) {
      data += "class:" + el.classBinding + ",";
    }

    return data;
  }

  var klass$1 = {
    staticKeys: ['staticClass'],
    transformNode: transformNode,
    genData: genData
    /*  */

  };

  function transformNode$1(el, options) {
    var warn = options.warn || baseWarn;
    var staticStyle = getAndRemoveAttr(el, 'style');

    if (staticStyle) {
      /* istanbul ignore if */
      {
        var res = parseText(staticStyle, options.delimiters);

        if (res) {
          warn("style=\"" + staticStyle + "\": " + 'Interpolation inside attributes has been removed. ' + 'Use v-bind or the colon shorthand instead. For example, ' + 'instead of <div style="{{ val }}">, use <div :style="val">.');
        }
      }
      el.staticStyle = JSON.stringify(parseStyleText(staticStyle));
    }

    var styleBinding = getBindingAttr(el, 'style', false
    /* getStatic */
    );

    if (styleBinding) {
      el.styleBinding = styleBinding;
    }
  }

  function genData$1(el) {
    var data = '';

    if (el.staticStyle) {
      data += "staticStyle:" + el.staticStyle + ",";
    }

    if (el.styleBinding) {
      data += "style:(" + el.styleBinding + "),";
    }

    return data;
  }

  var style$1 = {
    staticKeys: ['staticStyle'],
    transformNode: transformNode$1,
    genData: genData$1
    /*  */

  };
  var decoder;
  var he = {
    decode: function decode(html) {
      decoder = decoder || document.createElement('div');
      decoder.innerHTML = html;
      return decoder.textContent;
    }
    /*  */

  };
  var isUnaryTag = makeMap('area,base,br,col,embed,frame,hr,img,input,isindex,keygen,' + 'link,meta,param,source,track,wbr'); // Elements that you can, intentionally, leave open
  // (and which close themselves)

  var canBeLeftOpenTag = makeMap('colgroup,dd,dt,li,options,p,td,tfoot,th,thead,tr,source'); // HTML5 tags https://html.spec.whatwg.org/multipage/indices.html#elements-3
  // Phrasing Content https://html.spec.whatwg.org/multipage/dom.html#phrasing-content

  var isNonPhrasingTag = makeMap('address,article,aside,base,blockquote,body,caption,col,colgroup,dd,' + 'details,dialog,div,dl,dt,fieldset,figcaption,figure,footer,form,' + 'h1,h2,h3,h4,h5,h6,head,header,hgroup,hr,html,legend,li,menuitem,meta,' + 'optgroup,option,param,rp,rt,source,style,summary,tbody,td,tfoot,th,thead,' + 'title,tr,track');
  /**
   * Not type-checking this file because it's mostly vendor code.
   */

  /*!
   * HTML Parser By John Resig (ejohn.org)
   * Modified by Juriy "kangax" Zaytsev
   * Original code by Erik Arvidsson, Mozilla Public License
   * http://erik.eae.net/simplehtmlparser/simplehtmlparser.js
   */
  // Regular Expressions for parsing tags and attributes

  var attribute = /^\s*([^\s"'<>\/=]+)(?:\s*(=)\s*(?:"([^"]*)"+|'([^']*)'+|([^\s"'=<>`]+)))?/; // could use https://www.w3.org/TR/1999/REC-xml-names-19990114/#NT-QName
  // but for Vue templates we can enforce a simple charset

  var ncname = '[a-zA-Z_][\\w\\-\\.]*';
  var qnameCapture = "((?:" + ncname + "\\:)?" + ncname + ")";
  var startTagOpen = new RegExp("^<" + qnameCapture);
  var startTagClose = /^\s*(\/?)>/;
  var endTag = new RegExp("^<\\/" + qnameCapture + "[^>]*>");
  var doctype = /^<!DOCTYPE [^>]+>/i; // #7298: escape - to avoid being pased as HTML comment when inlined in page

  var comment = /^<!\--/;
  var conditionalComment = /^<!\[/;
  var IS_REGEX_CAPTURING_BROKEN = false;
  'x'.replace(/x(.)?/g, function (m, g) {
    IS_REGEX_CAPTURING_BROKEN = g === '';
  }); // Special Elements (can contain anything)

  var isPlainTextElement = makeMap('script,style,textarea', true);
  var reCache = {};
  var decodingMap = {
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&amp;': '&',
    '&#10;': '\n',
    '&#9;': '\t'
  };
  var encodedAttr = /&(?:lt|gt|quot|amp);/g;
  var encodedAttrWithNewLines = /&(?:lt|gt|quot|amp|#10|#9);/g; // #5992

  var isIgnoreNewlineTag = makeMap('pre,textarea', true);

  var shouldIgnoreFirstNewline = function shouldIgnoreFirstNewline(tag, html) {
    return tag && isIgnoreNewlineTag(tag) && html[0] === '\n';
  };

  function decodeAttr(value, shouldDecodeNewlines) {
    var re = shouldDecodeNewlines ? encodedAttrWithNewLines : encodedAttr;
    return value.replace(re, function (match) {
      return decodingMap[match];
    });
  }

  function parseHTML(html, options) {
    var stack = [];
    var expectHTML = options.expectHTML;
    var isUnaryTag$$1 = options.isUnaryTag || no;
    var canBeLeftOpenTag$$1 = options.canBeLeftOpenTag || no;
    var index = 0;
    var last, lastTag;

    while (html) {
      last = html; // Make sure we're not in a plaintext content element like script/style

      if (!lastTag || !isPlainTextElement(lastTag)) {
        var textEnd = html.indexOf('<');

        if (textEnd === 0) {
          // Comment:
          if (comment.test(html)) {
            var commentEnd = html.indexOf('-->');

            if (commentEnd >= 0) {
              if (options.shouldKeepComment) {
                options.comment(html.substring(4, commentEnd));
              }

              advance(commentEnd + 3);
              continue;
            }
          } // http://en.wikipedia.org/wiki/Conditional_comment#Downlevel-revealed_conditional_comment


          if (conditionalComment.test(html)) {
            var conditionalEnd = html.indexOf(']>');

            if (conditionalEnd >= 0) {
              advance(conditionalEnd + 2);
              continue;
            }
          } // Doctype:


          var doctypeMatch = html.match(doctype);

          if (doctypeMatch) {
            advance(doctypeMatch[0].length);
            continue;
          } // End tag:


          var endTagMatch = html.match(endTag);

          if (endTagMatch) {
            var curIndex = index;
            advance(endTagMatch[0].length);
            parseEndTag(endTagMatch[1], curIndex, index);
            continue;
          } // Start tag:


          var startTagMatch = parseStartTag();

          if (startTagMatch) {
            handleStartTag(startTagMatch);

            if (shouldIgnoreFirstNewline(lastTag, html)) {
              advance(1);
            }

            continue;
          }
        }

        var text = void 0,
            rest = void 0,
            next = void 0;

        if (textEnd >= 0) {
          rest = html.slice(textEnd);

          while (!endTag.test(rest) && !startTagOpen.test(rest) && !comment.test(rest) && !conditionalComment.test(rest)) {
            // < in plain text, be forgiving and treat it as text
            next = rest.indexOf('<', 1);

            if (next < 0) {
              break;
            }

            textEnd += next;
            rest = html.slice(textEnd);
          }

          text = html.substring(0, textEnd);
          advance(textEnd);
        }

        if (textEnd < 0) {
          text = html;
          html = '';
        }

        if (options.chars && text) {
          options.chars(text);
        }
      } else {
        var endTagLength = 0;
        var stackedTag = lastTag.toLowerCase();
        var reStackedTag = reCache[stackedTag] || (reCache[stackedTag] = new RegExp('([\\s\\S]*?)(</' + stackedTag + '[^>]*>)', 'i'));
        var rest$1 = html.replace(reStackedTag, function (all, text, endTag) {
          endTagLength = endTag.length;

          if (!isPlainTextElement(stackedTag) && stackedTag !== 'noscript') {
            text = text.replace(/<!\--([\s\S]*?)-->/g, '$1') // #7298
            .replace(/<!\[CDATA\[([\s\S]*?)]]>/g, '$1');
          }

          if (shouldIgnoreFirstNewline(stackedTag, text)) {
            text = text.slice(1);
          }

          if (options.chars) {
            options.chars(text);
          }

          return '';
        });
        index += html.length - rest$1.length;
        html = rest$1;
        parseEndTag(stackedTag, index - endTagLength, index);
      }

      if (html === last) {
        options.chars && options.chars(html);

        if ("development" !== 'production' && !stack.length && options.warn) {
          options.warn("Mal-formatted tag at end of template: \"" + html + "\"");
        }

        break;
      }
    } // Clean up any remaining tags


    parseEndTag();

    function advance(n) {
      index += n;
      html = html.substring(n);
    }

    function parseStartTag() {
      var start = html.match(startTagOpen);

      if (start) {
        var match = {
          tagName: start[1],
          attrs: [],
          start: index
        };
        advance(start[0].length);
        var end, attr;

        while (!(end = html.match(startTagClose)) && (attr = html.match(attribute))) {
          advance(attr[0].length);
          match.attrs.push(attr);
        }

        if (end) {
          match.unarySlash = end[1];
          advance(end[0].length);
          match.end = index;
          return match;
        }
      }
    }

    function handleStartTag(match) {
      var tagName = match.tagName;
      var unarySlash = match.unarySlash;

      if (expectHTML) {
        if (lastTag === 'p' && isNonPhrasingTag(tagName)) {
          parseEndTag(lastTag);
        }

        if (canBeLeftOpenTag$$1(tagName) && lastTag === tagName) {
          parseEndTag(tagName);
        }
      }

      var unary = isUnaryTag$$1(tagName) || !!unarySlash;
      var l = match.attrs.length;
      var attrs = new Array(l);

      for (var i = 0; i < l; i++) {
        var args = match.attrs[i]; // hackish work around FF bug https://bugzilla.mozilla.org/show_bug.cgi?id=369778

        if (IS_REGEX_CAPTURING_BROKEN && args[0].indexOf('""') === -1) {
          if (args[3] === '') {
            delete args[3];
          }

          if (args[4] === '') {
            delete args[4];
          }

          if (args[5] === '') {
            delete args[5];
          }
        }

        var value = args[3] || args[4] || args[5] || '';
        var shouldDecodeNewlines = tagName === 'a' && args[1] === 'href' ? options.shouldDecodeNewlinesForHref : options.shouldDecodeNewlines;
        attrs[i] = {
          name: args[1],
          value: decodeAttr(value, shouldDecodeNewlines)
        };
      }

      if (!unary) {
        stack.push({
          tag: tagName,
          lowerCasedTag: tagName.toLowerCase(),
          attrs: attrs
        });
        lastTag = tagName;
      }

      if (options.start) {
        options.start(tagName, attrs, unary, match.start, match.end);
      }
    }

    function parseEndTag(tagName, start, end) {
      var pos, lowerCasedTagName;

      if (start == null) {
        start = index;
      }

      if (end == null) {
        end = index;
      }

      if (tagName) {
        lowerCasedTagName = tagName.toLowerCase();
      } // Find the closest opened tag of the same type


      if (tagName) {
        for (pos = stack.length - 1; pos >= 0; pos--) {
          if (stack[pos].lowerCasedTag === lowerCasedTagName) {
            break;
          }
        }
      } else {
        // If no tag name is provided, clean shop
        pos = 0;
      }

      if (pos >= 0) {
        // Close all the open elements, up the stack
        for (var i = stack.length - 1; i >= pos; i--) {
          if ("development" !== 'production' && (i > pos || !tagName) && options.warn) {
            options.warn("tag <" + stack[i].tag + "> has no matching end tag.");
          }

          if (options.end) {
            options.end(stack[i].tag, start, end);
          }
        } // Remove the open elements from the stack


        stack.length = pos;
        lastTag = pos && stack[pos - 1].tag;
      } else if (lowerCasedTagName === 'br') {
        if (options.start) {
          options.start(tagName, [], true, start, end);
        }
      } else if (lowerCasedTagName === 'p') {
        if (options.start) {
          options.start(tagName, [], false, start, end);
        }

        if (options.end) {
          options.end(tagName, start, end);
        }
      }
    }
  }
  /*  */


  var onRE = /^@|^v-on:/;
  var dirRE = /^v-|^@|^:/;
  var forAliasRE = /([^]*?)\s+(?:in|of)\s+([^]*)/;
  var forIteratorRE = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/;
  var stripParensRE = /^\(|\)$/g;
  var argRE = /:(.*)$/;
  var bindRE = /^:|^v-bind:/;
  var modifierRE = /\.[^.]+/g;
  var decodeHTMLCached = cached(he.decode); // configurable state

  var warn$2;
  var delimiters;
  var transforms;
  var preTransforms;
  var postTransforms;
  var platformIsPreTag;
  var platformMustUseProp;
  var platformGetTagNamespace;

  function createASTElement(tag, attrs, parent) {
    return {
      type: 1,
      tag: tag,
      attrsList: attrs,
      attrsMap: makeAttrsMap(attrs),
      parent: parent,
      children: []
    };
  }
  /**
   * Convert HTML string to AST.
   */


  function parse(template, options) {
    warn$2 = options.warn || baseWarn;
    platformIsPreTag = options.isPreTag || no;
    platformMustUseProp = options.mustUseProp || no;
    platformGetTagNamespace = options.getTagNamespace || no;
    transforms = pluckModuleFunction(options.modules, 'transformNode');
    preTransforms = pluckModuleFunction(options.modules, 'preTransformNode');
    postTransforms = pluckModuleFunction(options.modules, 'postTransformNode');
    delimiters = options.delimiters;
    var stack = [];
    var preserveWhitespace = options.preserveWhitespace !== false;
    var root;
    var currentParent;
    var inVPre = false;
    var inPre = false;
    var warned = false;

    function warnOnce(msg) {
      if (!warned) {
        warned = true;
        warn$2(msg);
      }
    }

    function closeElement(element) {
      // check pre state
      if (element.pre) {
        inVPre = false;
      }

      if (platformIsPreTag(element.tag)) {
        inPre = false;
      } // apply post-transforms


      for (var i = 0; i < postTransforms.length; i++) {
        postTransforms[i](element, options);
      }
    }

    parseHTML(template, {
      warn: warn$2,
      expectHTML: options.expectHTML,
      isUnaryTag: options.isUnaryTag,
      canBeLeftOpenTag: options.canBeLeftOpenTag,
      shouldDecodeNewlines: options.shouldDecodeNewlines,
      shouldDecodeNewlinesForHref: options.shouldDecodeNewlinesForHref,
      shouldKeepComment: options.comments,
      start: function start(tag, attrs, unary) {
        // check namespace.
        // inherit parent ns if there is one
        var ns = currentParent && currentParent.ns || platformGetTagNamespace(tag); // handle IE svg bug

        /* istanbul ignore if */

        if (isIE && ns === 'svg') {
          attrs = guardIESVGBug(attrs);
        }

        var element = createASTElement(tag, attrs, currentParent);

        if (ns) {
          element.ns = ns;
        }

        if (isForbiddenTag(element) && !isServerRendering()) {
          element.forbidden = true;
          "development" !== 'production' && warn$2('Templates should only be responsible for mapping the state to the ' + 'UI. Avoid placing tags with side-effects in your templates, such as ' + "<" + tag + ">" + ', as they will not be parsed.');
        } // apply pre-transforms


        for (var i = 0; i < preTransforms.length; i++) {
          element = preTransforms[i](element, options) || element;
        }

        if (!inVPre) {
          processPre(element);

          if (element.pre) {
            inVPre = true;
          }
        }

        if (platformIsPreTag(element.tag)) {
          inPre = true;
        }

        if (inVPre) {
          processRawAttrs(element);
        } else if (!element.processed) {
          // structural directives
          processFor(element);
          processIf(element);
          processOnce(element); // element-scope stuff

          processElement(element, options);
        }

        function checkRootConstraints(el) {
          {
            if (el.tag === 'slot' || el.tag === 'template') {
              warnOnce("Cannot use <" + el.tag + "> as component root element because it may " + 'contain multiple nodes.');
            }

            if (el.attrsMap.hasOwnProperty('v-for')) {
              warnOnce('Cannot use v-for on stateful component root element because ' + 'it renders multiple elements.');
            }
          }
        } // tree management


        if (!root) {
          root = element;
          checkRootConstraints(root);
        } else if (!stack.length) {
          // allow root elements with v-if, v-else-if and v-else
          if (root.if && (element.elseif || element.else)) {
            checkRootConstraints(element);
            addIfCondition(root, {
              exp: element.elseif,
              block: element
            });
          } else {
            warnOnce("Component template should contain exactly one root element. " + "If you are using v-if on multiple elements, " + "use v-else-if to chain them instead.");
          }
        }

        if (currentParent && !element.forbidden) {
          if (element.elseif || element.else) {
            processIfConditions(element, currentParent);
          } else if (element.slotScope) {
            // scoped slot
            currentParent.plain = false;
            var name = element.slotTarget || '"default"';
            (currentParent.scopedSlots || (currentParent.scopedSlots = {}))[name] = element;
          } else {
            currentParent.children.push(element);
            element.parent = currentParent;
          }
        }

        if (!unary) {
          currentParent = element;
          stack.push(element);
        } else {
          closeElement(element);
        }
      },
      end: function end() {
        // remove trailing whitespace
        var element = stack[stack.length - 1];
        var lastNode = element.children[element.children.length - 1];

        if (lastNode && lastNode.type === 3 && lastNode.text === ' ' && !inPre) {
          element.children.pop();
        } // pop stack


        stack.length -= 1;
        currentParent = stack[stack.length - 1];
        closeElement(element);
      },
      chars: function chars(text) {
        if (!currentParent) {
          {
            if (text === template) {
              warnOnce('Component template requires a root element, rather than just text.');
            } else if (text = text.trim()) {
              warnOnce("text \"" + text + "\" outside root element will be ignored.");
            }
          }
          return;
        } // IE textarea placeholder bug

        /* istanbul ignore if */


        if (isIE && currentParent.tag === 'textarea' && currentParent.attrsMap.placeholder === text) {
          return;
        }

        var children = currentParent.children;
        text = inPre || text.trim() ? isTextTag(currentParent) ? text : decodeHTMLCached(text) // only preserve whitespace if its not right after a starting tag
        : preserveWhitespace && children.length ? ' ' : '';

        if (text) {
          var res;

          if (!inVPre && text !== ' ' && (res = parseText(text, delimiters))) {
            children.push({
              type: 2,
              expression: res.expression,
              tokens: res.tokens,
              text: text
            });
          } else if (text !== ' ' || !children.length || children[children.length - 1].text !== ' ') {
            children.push({
              type: 3,
              text: text
            });
          }
        }
      },
      comment: function comment(text) {
        currentParent.children.push({
          type: 3,
          text: text,
          isComment: true
        });
      }
    });
    return root;
  }

  function processPre(el) {
    if (getAndRemoveAttr(el, 'v-pre') != null) {
      el.pre = true;
    }
  }

  function processRawAttrs(el) {
    var l = el.attrsList.length;

    if (l) {
      var attrs = el.attrs = new Array(l);

      for (var i = 0; i < l; i++) {
        attrs[i] = {
          name: el.attrsList[i].name,
          value: JSON.stringify(el.attrsList[i].value)
        };
      }
    } else if (!el.pre) {
      // non root node in pre blocks with no attributes
      el.plain = true;
    }
  }

  function processElement(element, options) {
    processKey(element); // determine whether this is a plain element after
    // removing structural attributes

    element.plain = !element.key && !element.attrsList.length;
    processRef(element);
    processSlot(element);
    processComponent(element);

    for (var i = 0; i < transforms.length; i++) {
      element = transforms[i](element, options) || element;
    }

    processAttrs(element);
  }

  function processKey(el) {
    var exp = getBindingAttr(el, 'key');

    if (exp) {
      if ("development" !== 'production' && el.tag === 'template') {
        warn$2("<template> cannot be keyed. Place the key on real elements instead.");
      }

      el.key = exp;
    }
  }

  function processRef(el) {
    var ref = getBindingAttr(el, 'ref');

    if (ref) {
      el.ref = ref;
      el.refInFor = checkInFor(el);
    }
  }

  function processFor(el) {
    var exp;

    if (exp = getAndRemoveAttr(el, 'v-for')) {
      var res = parseFor(exp);

      if (res) {
        extend(el, res);
      } else {
        warn$2("Invalid v-for expression: " + exp);
      }
    }
  }

  function parseFor(exp) {
    var inMatch = exp.match(forAliasRE);

    if (!inMatch) {
      return;
    }

    var res = {};
    res.for = inMatch[2].trim();
    var alias = inMatch[1].trim().replace(stripParensRE, '');
    var iteratorMatch = alias.match(forIteratorRE);

    if (iteratorMatch) {
      res.alias = alias.replace(forIteratorRE, '');
      res.iterator1 = iteratorMatch[1].trim();

      if (iteratorMatch[2]) {
        res.iterator2 = iteratorMatch[2].trim();
      }
    } else {
      res.alias = alias;
    }

    return res;
  }

  function processIf(el) {
    var exp = getAndRemoveAttr(el, 'v-if');

    if (exp) {
      el.if = exp;
      addIfCondition(el, {
        exp: exp,
        block: el
      });
    } else {
      if (getAndRemoveAttr(el, 'v-else') != null) {
        el.else = true;
      }

      var elseif = getAndRemoveAttr(el, 'v-else-if');

      if (elseif) {
        el.elseif = elseif;
      }
    }
  }

  function processIfConditions(el, parent) {
    var prev = findPrevElement(parent.children);

    if (prev && prev.if) {
      addIfCondition(prev, {
        exp: el.elseif,
        block: el
      });
    } else {
      warn$2("v-" + (el.elseif ? 'else-if="' + el.elseif + '"' : 'else') + " " + "used on element <" + el.tag + "> without corresponding v-if.");
    }
  }

  function findPrevElement(children) {
    var i = children.length;

    while (i--) {
      if (children[i].type === 1) {
        return children[i];
      } else {
        if ("development" !== 'production' && children[i].text !== ' ') {
          warn$2("text \"" + children[i].text.trim() + "\" between v-if and v-else(-if) " + "will be ignored.");
        }

        children.pop();
      }
    }
  }

  function addIfCondition(el, condition) {
    if (!el.ifConditions) {
      el.ifConditions = [];
    }

    el.ifConditions.push(condition);
  }

  function processOnce(el) {
    var once$$1 = getAndRemoveAttr(el, 'v-once');

    if (once$$1 != null) {
      el.once = true;
    }
  }

  function processSlot(el) {
    if (el.tag === 'slot') {
      el.slotName = getBindingAttr(el, 'name');

      if ("development" !== 'production' && el.key) {
        warn$2("`key` does not work on <slot> because slots are abstract outlets " + "and can possibly expand into multiple elements. " + "Use the key on a wrapping element instead.");
      }
    } else {
      var slotScope;

      if (el.tag === 'template') {
        slotScope = getAndRemoveAttr(el, 'scope');
        /* istanbul ignore if */

        if ("development" !== 'production' && slotScope) {
          warn$2("the \"scope\" attribute for scoped slots have been deprecated and " + "replaced by \"slot-scope\" since 2.5. The new \"slot-scope\" attribute " + "can also be used on plain elements in addition to <template> to " + "denote scoped slots.", true);
        }

        el.slotScope = slotScope || getAndRemoveAttr(el, 'slot-scope');
      } else if (slotScope = getAndRemoveAttr(el, 'slot-scope')) {
        /* istanbul ignore if */
        if ("development" !== 'production' && el.attrsMap['v-for']) {
          warn$2("Ambiguous combined usage of slot-scope and v-for on <" + el.tag + "> " + "(v-for takes higher priority). Use a wrapper <template> for the " + "scoped slot to make it clearer.", true);
        }

        el.slotScope = slotScope;
      }

      var slotTarget = getBindingAttr(el, 'slot');

      if (slotTarget) {
        el.slotTarget = slotTarget === '""' ? '"default"' : slotTarget; // preserve slot as an attribute for native shadow DOM compat
        // only for non-scoped slots.

        if (el.tag !== 'template' && !el.slotScope) {
          addAttr(el, 'slot', slotTarget);
        }
      }
    }
  }

  function processComponent(el) {
    var binding;

    if (binding = getBindingAttr(el, 'is')) {
      el.component = binding;
    }

    if (getAndRemoveAttr(el, 'inline-template') != null) {
      el.inlineTemplate = true;
    }
  }

  function processAttrs(el) {
    var list = el.attrsList;
    var i, l, name, rawName, value, modifiers, isProp;

    for (i = 0, l = list.length; i < l; i++) {
      name = rawName = list[i].name;
      value = list[i].value;

      if (dirRE.test(name)) {
        // mark element as dynamic
        el.hasBindings = true; // modifiers

        modifiers = parseModifiers(name);

        if (modifiers) {
          name = name.replace(modifierRE, '');
        }

        if (bindRE.test(name)) {
          // v-bind
          name = name.replace(bindRE, '');
          value = parseFilters(value);
          isProp = false;

          if (modifiers) {
            if (modifiers.prop) {
              isProp = true;
              name = camelize(name);

              if (name === 'innerHtml') {
                name = 'innerHTML';
              }
            }

            if (modifiers.camel) {
              name = camelize(name);
            }

            if (modifiers.sync) {
              addHandler(el, "update:" + camelize(name), genAssignmentCode(value, "$event"));
            }
          }

          if (isProp || !el.component && platformMustUseProp(el.tag, el.attrsMap.type, name)) {
            addProp(el, name, value);
          } else {
            addAttr(el, name, value);
          }
        } else if (onRE.test(name)) {
          // v-on
          name = name.replace(onRE, '');
          addHandler(el, name, value, modifiers, false, warn$2);
        } else {
          // normal directives
          name = name.replace(dirRE, ''); // parse arg

          var argMatch = name.match(argRE);
          var arg = argMatch && argMatch[1];

          if (arg) {
            name = name.slice(0, -(arg.length + 1));
          }

          addDirective(el, name, rawName, value, arg, modifiers);

          if ("development" !== 'production' && name === 'model') {
            checkForAliasModel(el, value);
          }
        }
      } else {
        // literal attribute
        {
          var res = parseText(value, delimiters);

          if (res) {
            warn$2(name + "=\"" + value + "\": " + 'Interpolation inside attributes has been removed. ' + 'Use v-bind or the colon shorthand instead. For example, ' + 'instead of <div id="{{ val }}">, use <div :id="val">.');
          }
        }
        addAttr(el, name, JSON.stringify(value)); // #6887 firefox doesn't update muted state if set via attribute
        // even immediately after element creation

        if (!el.component && name === 'muted' && platformMustUseProp(el.tag, el.attrsMap.type, name)) {
          addProp(el, name, 'true');
        }
      }
    }
  }

  function checkInFor(el) {
    var parent = el;

    while (parent) {
      if (parent.for !== undefined) {
        return true;
      }

      parent = parent.parent;
    }

    return false;
  }

  function parseModifiers(name) {
    var match = name.match(modifierRE);

    if (match) {
      var ret = {};
      match.forEach(function (m) {
        ret[m.slice(1)] = true;
      });
      return ret;
    }
  }

  function makeAttrsMap(attrs) {
    var map = {};

    for (var i = 0, l = attrs.length; i < l; i++) {
      if ("development" !== 'production' && map[attrs[i].name] && !isIE && !isEdge) {
        warn$2('duplicate attribute: ' + attrs[i].name);
      }

      map[attrs[i].name] = attrs[i].value;
    }

    return map;
  } // for script (e.g. type="x/template") or style, do not decode content


  function isTextTag(el) {
    return el.tag === 'script' || el.tag === 'style';
  }

  function isForbiddenTag(el) {
    return el.tag === 'style' || el.tag === 'script' && (!el.attrsMap.type || el.attrsMap.type === 'text/javascript');
  }

  var ieNSBug = /^xmlns:NS\d+/;
  var ieNSPrefix = /^NS\d+:/;
  /* istanbul ignore next */

  function guardIESVGBug(attrs) {
    var res = [];

    for (var i = 0; i < attrs.length; i++) {
      var attr = attrs[i];

      if (!ieNSBug.test(attr.name)) {
        attr.name = attr.name.replace(ieNSPrefix, '');
        res.push(attr);
      }
    }

    return res;
  }

  function checkForAliasModel(el, value) {
    var _el = el;

    while (_el) {
      if (_el.for && _el.alias === value) {
        warn$2("<" + el.tag + " v-model=\"" + value + "\">: " + "You are binding v-model directly to a v-for iteration alias. " + "This will not be able to modify the v-for source array because " + "writing to the alias is like modifying a function local variable. " + "Consider using an array of objects and use v-model on an object property instead.");
      }

      _el = _el.parent;
    }
  }
  /*  */

  /**
   * Expand input[v-model] with dyanmic type bindings into v-if-else chains
   * Turn this:
   *   <input v-model="data[type]" :type="type">
   * into this:
   *   <input v-if="type === 'checkbox'" type="checkbox" v-model="data[type]">
   *   <input v-else-if="type === 'radio'" type="radio" v-model="data[type]">
   *   <input v-else :type="type" v-model="data[type]">
   */


  function preTransformNode(el, options) {
    if (el.tag === 'input') {
      var map = el.attrsMap;

      if (!map['v-model']) {
        return;
      }

      var typeBinding;

      if (map[':type'] || map['v-bind:type']) {
        typeBinding = getBindingAttr(el, 'type');
      }

      if (!map.type && !typeBinding && map['v-bind']) {
        typeBinding = "(" + map['v-bind'] + ").type";
      }

      if (typeBinding) {
        var ifCondition = getAndRemoveAttr(el, 'v-if', true);
        var ifConditionExtra = ifCondition ? "&&(" + ifCondition + ")" : "";
        var hasElse = getAndRemoveAttr(el, 'v-else', true) != null;
        var elseIfCondition = getAndRemoveAttr(el, 'v-else-if', true); // 1. checkbox

        var branch0 = cloneASTElement(el); // process for on the main node

        processFor(branch0);
        addRawAttr(branch0, 'type', 'checkbox');
        processElement(branch0, options);
        branch0.processed = true; // prevent it from double-processed

        branch0.if = "(" + typeBinding + ")==='checkbox'" + ifConditionExtra;
        addIfCondition(branch0, {
          exp: branch0.if,
          block: branch0
        }); // 2. add radio else-if condition

        var branch1 = cloneASTElement(el);
        getAndRemoveAttr(branch1, 'v-for', true);
        addRawAttr(branch1, 'type', 'radio');
        processElement(branch1, options);
        addIfCondition(branch0, {
          exp: "(" + typeBinding + ")==='radio'" + ifConditionExtra,
          block: branch1
        }); // 3. other

        var branch2 = cloneASTElement(el);
        getAndRemoveAttr(branch2, 'v-for', true);
        addRawAttr(branch2, ':type', typeBinding);
        processElement(branch2, options);
        addIfCondition(branch0, {
          exp: ifCondition,
          block: branch2
        });

        if (hasElse) {
          branch0.else = true;
        } else if (elseIfCondition) {
          branch0.elseif = elseIfCondition;
        }

        return branch0;
      }
    }
  }

  function cloneASTElement(el) {
    return createASTElement(el.tag, el.attrsList.slice(), el.parent);
  }

  var model$2 = {
    preTransformNode: preTransformNode
  };
  var modules$1 = [klass$1, style$1, model$2];
  /*  */

  function text(el, dir) {
    if (dir.value) {
      addProp(el, 'textContent', "_s(" + dir.value + ")");
    }
  }
  /*  */


  function html(el, dir) {
    if (dir.value) {
      addProp(el, 'innerHTML', "_s(" + dir.value + ")");
    }
  }

  var directives$1 = {
    model: model,
    text: text,
    html: html
    /*  */

  };
  var baseOptions = {
    expectHTML: true,
    modules: modules$1,
    directives: directives$1,
    isPreTag: isPreTag,
    isUnaryTag: isUnaryTag,
    mustUseProp: mustUseProp,
    canBeLeftOpenTag: canBeLeftOpenTag,
    isReservedTag: isReservedTag,
    getTagNamespace: getTagNamespace,
    staticKeys: genStaticKeys(modules$1)
  };
  /*  */

  var isStaticKey;
  var isPlatformReservedTag;
  var genStaticKeysCached = cached(genStaticKeys$1);
  /**
   * Goal of the optimizer: walk the generated template AST tree
   * and detect sub-trees that are purely static, i.e. parts of
   * the DOM that never needs to change.
   *
   * Once we detect these sub-trees, we can:
   *
   * 1. Hoist them into constants, so that we no longer need to
   *    create fresh nodes for them on each re-render;
   * 2. Completely skip them in the patching process.
   */

  function optimize(root, options) {
    if (!root) {
      return;
    }

    isStaticKey = genStaticKeysCached(options.staticKeys || '');
    isPlatformReservedTag = options.isReservedTag || no; // first pass: mark all non-static nodes.

    markStatic$1(root); // second pass: mark static roots.

    markStaticRoots(root, false);
  }

  function genStaticKeys$1(keys) {
    return makeMap('type,tag,attrsList,attrsMap,plain,parent,children,attrs' + (keys ? ',' + keys : ''));
  }

  function markStatic$1(node) {
    node.static = isStatic(node);

    if (node.type === 1) {
      // do not make component slot content static. this avoids
      // 1. components not able to mutate slot nodes
      // 2. static slot content fails for hot-reloading
      if (!isPlatformReservedTag(node.tag) && node.tag !== 'slot' && node.attrsMap['inline-template'] == null) {
        return;
      }

      for (var i = 0, l = node.children.length; i < l; i++) {
        var child = node.children[i];
        markStatic$1(child);

        if (!child.static) {
          node.static = false;
        }
      }

      if (node.ifConditions) {
        for (var i$1 = 1, l$1 = node.ifConditions.length; i$1 < l$1; i$1++) {
          var block = node.ifConditions[i$1].block;
          markStatic$1(block);

          if (!block.static) {
            node.static = false;
          }
        }
      }
    }
  }

  function markStaticRoots(node, isInFor) {
    if (node.type === 1) {
      if (node.static || node.once) {
        node.staticInFor = isInFor;
      } // For a node to qualify as a static root, it should have children that
      // are not just static text. Otherwise the cost of hoisting out will
      // outweigh the benefits and it's better off to just always render it fresh.


      if (node.static && node.children.length && !(node.children.length === 1 && node.children[0].type === 3)) {
        node.staticRoot = true;
        return;
      } else {
        node.staticRoot = false;
      }

      if (node.children) {
        for (var i = 0, l = node.children.length; i < l; i++) {
          markStaticRoots(node.children[i], isInFor || !!node.for);
        }
      }

      if (node.ifConditions) {
        for (var i$1 = 1, l$1 = node.ifConditions.length; i$1 < l$1; i$1++) {
          markStaticRoots(node.ifConditions[i$1].block, isInFor);
        }
      }
    }
  }

  function isStatic(node) {
    if (node.type === 2) {
      // expression
      return false;
    }

    if (node.type === 3) {
      // text
      return true;
    }

    return !!(node.pre || !node.hasBindings && // no dynamic bindings
    !node.if && !node.for && // not v-if or v-for or v-else
    !isBuiltInTag(node.tag) && // not a built-in
    isPlatformReservedTag(node.tag) && // not a component
    !isDirectChildOfTemplateFor(node) && Object.keys(node).every(isStaticKey));
  }

  function isDirectChildOfTemplateFor(node) {
    while (node.parent) {
      node = node.parent;

      if (node.tag !== 'template') {
        return false;
      }

      if (node.for) {
        return true;
      }
    }

    return false;
  }
  /*  */


  var fnExpRE = /^([\w$_]+|\([^)]*?\))\s*=>|^function\s*\(/;
  var simplePathRE = /^[A-Za-z_$][\w$]*(?:\.[A-Za-z_$][\w$]*|\['[^']*?']|\["[^"]*?"]|\[\d+]|\[[A-Za-z_$][\w$]*])*$/; // KeyboardEvent.keyCode aliases

  var keyCodes = {
    esc: 27,
    tab: 9,
    enter: 13,
    space: 32,
    up: 38,
    left: 37,
    right: 39,
    down: 40,
    'delete': [8, 46]
  }; // KeyboardEvent.key aliases

  var keyNames = {
    esc: 'Escape',
    tab: 'Tab',
    enter: 'Enter',
    space: ' ',
    // #7806: IE11 uses key names without `Arrow` prefix for arrow keys.
    up: ['Up', 'ArrowUp'],
    left: ['Left', 'ArrowLeft'],
    right: ['Right', 'ArrowRight'],
    down: ['Down', 'ArrowDown'],
    'delete': ['Backspace', 'Delete']
  }; // #4868: modifiers that prevent the execution of the listener
  // need to explicitly return null so that we can determine whether to remove
  // the listener for .once

  var genGuard = function genGuard(condition) {
    return "if(" + condition + ")return null;";
  };

  var modifierCode = {
    stop: '$event.stopPropagation();',
    prevent: '$event.preventDefault();',
    self: genGuard("$event.target !== $event.currentTarget"),
    ctrl: genGuard("!$event.ctrlKey"),
    shift: genGuard("!$event.shiftKey"),
    alt: genGuard("!$event.altKey"),
    meta: genGuard("!$event.metaKey"),
    left: genGuard("'button' in $event && $event.button !== 0"),
    middle: genGuard("'button' in $event && $event.button !== 1"),
    right: genGuard("'button' in $event && $event.button !== 2")
  };

  function genHandlers(events, isNative, warn) {
    var res = isNative ? 'nativeOn:{' : 'on:{';

    for (var name in events) {
      res += "\"" + name + "\":" + genHandler(name, events[name]) + ",";
    }

    return res.slice(0, -1) + '}';
  }

  function genHandler(name, handler) {
    if (!handler) {
      return 'function(){}';
    }

    if (Array.isArray(handler)) {
      return "[" + handler.map(function (handler) {
        return genHandler(name, handler);
      }).join(',') + "]";
    }

    var isMethodPath = simplePathRE.test(handler.value);
    var isFunctionExpression = fnExpRE.test(handler.value);

    if (!handler.modifiers) {
      if (isMethodPath || isFunctionExpression) {
        return handler.value;
      }
      /* istanbul ignore if */


      return "function($event){" + handler.value + "}"; // inline statement
    } else {
      var code = '';
      var genModifierCode = '';
      var keys = [];

      for (var key in handler.modifiers) {
        if (modifierCode[key]) {
          genModifierCode += modifierCode[key]; // left/right

          if (keyCodes[key]) {
            keys.push(key);
          }
        } else if (key === 'exact') {
          var modifiers = handler.modifiers;
          genModifierCode += genGuard(['ctrl', 'shift', 'alt', 'meta'].filter(function (keyModifier) {
            return !modifiers[keyModifier];
          }).map(function (keyModifier) {
            return "$event." + keyModifier + "Key";
          }).join('||'));
        } else {
          keys.push(key);
        }
      }

      if (keys.length) {
        code += genKeyFilter(keys);
      } // Make sure modifiers like prevent and stop get executed after key filtering


      if (genModifierCode) {
        code += genModifierCode;
      }

      var handlerCode = isMethodPath ? "return " + handler.value + "($event)" : isFunctionExpression ? "return (" + handler.value + ")($event)" : handler.value;
      /* istanbul ignore if */

      return "function($event){" + code + handlerCode + "}";
    }
  }

  function genKeyFilter(keys) {
    return "if(!('button' in $event)&&" + keys.map(genFilterCode).join('&&') + ")return null;";
  }

  function genFilterCode(key) {
    var keyVal = parseInt(key, 10);

    if (keyVal) {
      return "$event.keyCode!==" + keyVal;
    }

    var keyCode = keyCodes[key];
    var keyName = keyNames[key];
    return "_k($event.keyCode," + JSON.stringify(key) + "," + JSON.stringify(keyCode) + "," + "$event.key," + "" + JSON.stringify(keyName) + ")";
  }
  /*  */


  function on(el, dir) {
    if ("development" !== 'production' && dir.modifiers) {
      warn("v-on without argument does not support modifiers.");
    }

    el.wrapListeners = function (code) {
      return "_g(" + code + "," + dir.value + ")";
    };
  }
  /*  */


  function bind$1(el, dir) {
    el.wrapData = function (code) {
      return "_b(" + code + ",'" + el.tag + "'," + dir.value + "," + (dir.modifiers && dir.modifiers.prop ? 'true' : 'false') + (dir.modifiers && dir.modifiers.sync ? ',true' : '') + ")";
    };
  }
  /*  */


  var baseDirectives = {
    on: on,
    bind: bind$1,
    cloak: noop
    /*  */

  };

  var CodegenState = function CodegenState(options) {
    this.options = options;
    this.warn = options.warn || baseWarn;
    this.transforms = pluckModuleFunction(options.modules, 'transformCode');
    this.dataGenFns = pluckModuleFunction(options.modules, 'genData');
    this.directives = extend(extend({}, baseDirectives), options.directives);
    var isReservedTag = options.isReservedTag || no;

    this.maybeComponent = function (el) {
      return !isReservedTag(el.tag);
    };

    this.onceId = 0;
    this.staticRenderFns = [];
  };

  function generate(ast, options) {
    var state = new CodegenState(options);
    var code = ast ? genElement(ast, state) : '_c("div")';
    return {
      render: "with(this){return " + code + "}",
      staticRenderFns: state.staticRenderFns
    };
  }

  function genElement(el, state) {
    if (el.staticRoot && !el.staticProcessed) {
      return genStatic(el, state);
    } else if (el.once && !el.onceProcessed) {
      return genOnce(el, state);
    } else if (el.for && !el.forProcessed) {
      return genFor(el, state);
    } else if (el.if && !el.ifProcessed) {
      return genIf(el, state);
    } else if (el.tag === 'template' && !el.slotTarget) {
      return genChildren(el, state) || 'void 0';
    } else if (el.tag === 'slot') {
      return genSlot(el, state);
    } else {
      // component or element
      var code;

      if (el.component) {
        code = genComponent(el.component, el, state);
      } else {
        var data = el.plain ? undefined : genData$2(el, state);
        var children = el.inlineTemplate ? null : genChildren(el, state, true);
        code = "_c('" + el.tag + "'" + (data ? "," + data : '') + (children ? "," + children : '') + ")";
      } // module transforms


      for (var i = 0; i < state.transforms.length; i++) {
        code = state.transforms[i](el, code);
      }

      return code;
    }
  } // hoist static sub-trees out


  function genStatic(el, state) {
    el.staticProcessed = true;
    state.staticRenderFns.push("with(this){return " + genElement(el, state) + "}");
    return "_m(" + (state.staticRenderFns.length - 1) + (el.staticInFor ? ',true' : '') + ")";
  } // v-once


  function genOnce(el, state) {
    el.onceProcessed = true;

    if (el.if && !el.ifProcessed) {
      return genIf(el, state);
    } else if (el.staticInFor) {
      var key = '';
      var parent = el.parent;

      while (parent) {
        if (parent.for) {
          key = parent.key;
          break;
        }

        parent = parent.parent;
      }

      if (!key) {
        "development" !== 'production' && state.warn("v-once can only be used inside v-for that is keyed. ");
        return genElement(el, state);
      }

      return "_o(" + genElement(el, state) + "," + state.onceId++ + "," + key + ")";
    } else {
      return genStatic(el, state);
    }
  }

  function genIf(el, state, altGen, altEmpty) {
    el.ifProcessed = true; // avoid recursion

    return genIfConditions(el.ifConditions.slice(), state, altGen, altEmpty);
  }

  function genIfConditions(conditions, state, altGen, altEmpty) {
    if (!conditions.length) {
      return altEmpty || '_e()';
    }

    var condition = conditions.shift();

    if (condition.exp) {
      return "(" + condition.exp + ")?" + genTernaryExp(condition.block) + ":" + genIfConditions(conditions, state, altGen, altEmpty);
    } else {
      return "" + genTernaryExp(condition.block);
    } // v-if with v-once should generate code like (a)?_m(0):_m(1)


    function genTernaryExp(el) {
      return altGen ? altGen(el, state) : el.once ? genOnce(el, state) : genElement(el, state);
    }
  }

  function genFor(el, state, altGen, altHelper) {
    var exp = el.for;
    var alias = el.alias;
    var iterator1 = el.iterator1 ? "," + el.iterator1 : '';
    var iterator2 = el.iterator2 ? "," + el.iterator2 : '';

    if ("development" !== 'production' && state.maybeComponent(el) && el.tag !== 'slot' && el.tag !== 'template' && !el.key) {
      state.warn("<" + el.tag + " v-for=\"" + alias + " in " + exp + "\">: component lists rendered with " + "v-for should have explicit keys. " + "See https://vuejs.org/guide/list.html#key for more info.", true
      /* tip */
      );
    }

    el.forProcessed = true; // avoid recursion

    return (altHelper || '_l') + "((" + exp + ")," + "function(" + alias + iterator1 + iterator2 + "){" + "return " + (altGen || genElement)(el, state) + '})';
  }

  function genData$2(el, state) {
    var data = '{'; // directives first.
    // directives may mutate the el's other properties before they are generated.

    var dirs = genDirectives(el, state);

    if (dirs) {
      data += dirs + ',';
    } // key


    if (el.key) {
      data += "key:" + el.key + ",";
    } // ref


    if (el.ref) {
      data += "ref:" + el.ref + ",";
    }

    if (el.refInFor) {
      data += "refInFor:true,";
    } // pre


    if (el.pre) {
      data += "pre:true,";
    } // record original tag name for components using "is" attribute


    if (el.component) {
      data += "tag:\"" + el.tag + "\",";
    } // module data generation functions


    for (var i = 0; i < state.dataGenFns.length; i++) {
      data += state.dataGenFns[i](el);
    } // attributes


    if (el.attrs) {
      data += "attrs:{" + genProps(el.attrs) + "},";
    } // DOM props


    if (el.props) {
      data += "domProps:{" + genProps(el.props) + "},";
    } // event handlers


    if (el.events) {
      data += genHandlers(el.events, false, state.warn) + ",";
    }

    if (el.nativeEvents) {
      data += genHandlers(el.nativeEvents, true, state.warn) + ",";
    } // slot target
    // only for non-scoped slots


    if (el.slotTarget && !el.slotScope) {
      data += "slot:" + el.slotTarget + ",";
    } // scoped slots


    if (el.scopedSlots) {
      data += genScopedSlots(el.scopedSlots, state) + ",";
    } // component v-model


    if (el.model) {
      data += "model:{value:" + el.model.value + ",callback:" + el.model.callback + ",expression:" + el.model.expression + "},";
    } // inline-template


    if (el.inlineTemplate) {
      var inlineTemplate = genInlineTemplate(el, state);

      if (inlineTemplate) {
        data += inlineTemplate + ",";
      }
    }

    data = data.replace(/,$/, '') + '}'; // v-bind data wrap

    if (el.wrapData) {
      data = el.wrapData(data);
    } // v-on data wrap


    if (el.wrapListeners) {
      data = el.wrapListeners(data);
    }

    return data;
  }

  function genDirectives(el, state) {
    var dirs = el.directives;

    if (!dirs) {
      return;
    }

    var res = 'directives:[';
    var hasRuntime = false;
    var i, l, dir, needRuntime;

    for (i = 0, l = dirs.length; i < l; i++) {
      dir = dirs[i];
      needRuntime = true;
      var gen = state.directives[dir.name];

      if (gen) {
        // compile-time directive that manipulates AST.
        // returns true if it also needs a runtime counterpart.
        needRuntime = !!gen(el, dir, state.warn);
      }

      if (needRuntime) {
        hasRuntime = true;
        res += "{name:\"" + dir.name + "\",rawName:\"" + dir.rawName + "\"" + (dir.value ? ",value:(" + dir.value + "),expression:" + JSON.stringify(dir.value) : '') + (dir.arg ? ",arg:\"" + dir.arg + "\"" : '') + (dir.modifiers ? ",modifiers:" + JSON.stringify(dir.modifiers) : '') + "},";
      }
    }

    if (hasRuntime) {
      return res.slice(0, -1) + ']';
    }
  }

  function genInlineTemplate(el, state) {
    var ast = el.children[0];

    if ("development" !== 'production' && (el.children.length !== 1 || ast.type !== 1)) {
      state.warn('Inline-template components must have exactly one child element.');
    }

    if (ast.type === 1) {
      var inlineRenderFns = generate(ast, state.options);
      return "inlineTemplate:{render:function(){" + inlineRenderFns.render + "},staticRenderFns:[" + inlineRenderFns.staticRenderFns.map(function (code) {
        return "function(){" + code + "}";
      }).join(',') + "]}";
    }
  }

  function genScopedSlots(slots, state) {
    return "scopedSlots:_u([" + Object.keys(slots).map(function (key) {
      return genScopedSlot(key, slots[key], state);
    }).join(',') + "])";
  }

  function genScopedSlot(key, el, state) {
    if (el.for && !el.forProcessed) {
      return genForScopedSlot(key, el, state);
    }

    var fn = "function(" + String(el.slotScope) + "){" + "return " + (el.tag === 'template' ? el.if ? el.if + "?" + (genChildren(el, state) || 'undefined') + ":undefined" : genChildren(el, state) || 'undefined' : genElement(el, state)) + "}";
    return "{key:" + key + ",fn:" + fn + "}";
  }

  function genForScopedSlot(key, el, state) {
    var exp = el.for;
    var alias = el.alias;
    var iterator1 = el.iterator1 ? "," + el.iterator1 : '';
    var iterator2 = el.iterator2 ? "," + el.iterator2 : '';
    el.forProcessed = true; // avoid recursion

    return "_l((" + exp + ")," + "function(" + alias + iterator1 + iterator2 + "){" + "return " + genScopedSlot(key, el, state) + '})';
  }

  function genChildren(el, state, checkSkip, altGenElement, altGenNode) {
    var children = el.children;

    if (children.length) {
      var el$1 = children[0]; // optimize single v-for

      if (children.length === 1 && el$1.for && el$1.tag !== 'template' && el$1.tag !== 'slot') {
        return (altGenElement || genElement)(el$1, state);
      }

      var normalizationType = checkSkip ? getNormalizationType(children, state.maybeComponent) : 0;
      var gen = altGenNode || genNode;
      return "[" + children.map(function (c) {
        return gen(c, state);
      }).join(',') + "]" + (normalizationType ? "," + normalizationType : '');
    }
  } // determine the normalization needed for the children array.
  // 0: no normalization needed
  // 1: simple normalization needed (possible 1-level deep nested array)
  // 2: full normalization needed


  function getNormalizationType(children, maybeComponent) {
    var res = 0;

    for (var i = 0; i < children.length; i++) {
      var el = children[i];

      if (el.type !== 1) {
        continue;
      }

      if (needsNormalization(el) || el.ifConditions && el.ifConditions.some(function (c) {
        return needsNormalization(c.block);
      })) {
        res = 2;
        break;
      }

      if (maybeComponent(el) || el.ifConditions && el.ifConditions.some(function (c) {
        return maybeComponent(c.block);
      })) {
        res = 1;
      }
    }

    return res;
  }

  function needsNormalization(el) {
    return el.for !== undefined || el.tag === 'template' || el.tag === 'slot';
  }

  function genNode(node, state) {
    if (node.type === 1) {
      return genElement(node, state);
    }

    if (node.type === 3 && node.isComment) {
      return genComment(node);
    } else {
      return genText(node);
    }
  }

  function genText(text) {
    return "_v(" + (text.type === 2 ? text.expression // no need for () because already wrapped in _s()
    : transformSpecialNewlines(JSON.stringify(text.text))) + ")";
  }

  function genComment(comment) {
    return "_e(" + JSON.stringify(comment.text) + ")";
  }

  function genSlot(el, state) {
    var slotName = el.slotName || '"default"';
    var children = genChildren(el, state);
    var res = "_t(" + slotName + (children ? "," + children : '');
    var attrs = el.attrs && "{" + el.attrs.map(function (a) {
      return camelize(a.name) + ":" + a.value;
    }).join(',') + "}";
    var bind$$1 = el.attrsMap['v-bind'];

    if ((attrs || bind$$1) && !children) {
      res += ",null";
    }

    if (attrs) {
      res += "," + attrs;
    }

    if (bind$$1) {
      res += (attrs ? '' : ',null') + "," + bind$$1;
    }

    return res + ')';
  } // componentName is el.component, take it as argument to shun flow's pessimistic refinement


  function genComponent(componentName, el, state) {
    var children = el.inlineTemplate ? null : genChildren(el, state, true);
    return "_c(" + componentName + "," + genData$2(el, state) + (children ? "," + children : '') + ")";
  }

  function genProps(props) {
    var res = '';

    for (var i = 0; i < props.length; i++) {
      var prop = props[i];
      /* istanbul ignore if */

      {
        res += "\"" + prop.name + "\":" + transformSpecialNewlines(prop.value) + ",";
      }
    }

    return res.slice(0, -1);
  } // #3895, #4268


  function transformSpecialNewlines(text) {
    return text.replace(/\u2028/g, "\\u2028").replace(/\u2029/g, "\\u2029");
  }
  /*  */
  // these keywords should not appear inside expressions, but operators like
  // typeof, instanceof and in are allowed


  var prohibitedKeywordRE = new RegExp('\\b' + ('do,if,for,let,new,try,var,case,else,with,await,break,catch,class,const,' + 'super,throw,while,yield,delete,export,import,return,switch,default,' + 'extends,finally,continue,debugger,function,arguments').split(',').join('\\b|\\b') + '\\b'); // these unary operators should not be used as property/method names

  var unaryOperatorsRE = new RegExp('\\b' + 'delete,typeof,void'.split(',').join('\\s*\\([^\\)]*\\)|\\b') + '\\s*\\([^\\)]*\\)'); // strip strings in expressions

  var stripStringRE = /'(?:[^'\\]|\\.)*'|"(?:[^"\\]|\\.)*"|`(?:[^`\\]|\\.)*\$\{|\}(?:[^`\\]|\\.)*`|`(?:[^`\\]|\\.)*`/g; // detect problematic expressions in a template

  function detectErrors(ast) {
    var errors = [];

    if (ast) {
      checkNode(ast, errors);
    }

    return errors;
  }

  function checkNode(node, errors) {
    if (node.type === 1) {
      for (var name in node.attrsMap) {
        if (dirRE.test(name)) {
          var value = node.attrsMap[name];

          if (value) {
            if (name === 'v-for') {
              checkFor(node, "v-for=\"" + value + "\"", errors);
            } else if (onRE.test(name)) {
              checkEvent(value, name + "=\"" + value + "\"", errors);
            } else {
              checkExpression(value, name + "=\"" + value + "\"", errors);
            }
          }
        }
      }

      if (node.children) {
        for (var i = 0; i < node.children.length; i++) {
          checkNode(node.children[i], errors);
        }
      }
    } else if (node.type === 2) {
      checkExpression(node.expression, node.text, errors);
    }
  }

  function checkEvent(exp, text, errors) {
    var stipped = exp.replace(stripStringRE, '');
    var keywordMatch = stipped.match(unaryOperatorsRE);

    if (keywordMatch && stipped.charAt(keywordMatch.index - 1) !== '$') {
      errors.push("avoid using JavaScript unary operator as property name: " + "\"" + keywordMatch[0] + "\" in expression " + text.trim());
    }

    checkExpression(exp, text, errors);
  }

  function checkFor(node, text, errors) {
    checkExpression(node.for || '', text, errors);
    checkIdentifier(node.alias, 'v-for alias', text, errors);
    checkIdentifier(node.iterator1, 'v-for iterator', text, errors);
    checkIdentifier(node.iterator2, 'v-for iterator', text, errors);
  }

  function checkIdentifier(ident, type, text, errors) {
    if (typeof ident === 'string') {
      try {
        new Function("var " + ident + "=_");
      } catch (e) {
        errors.push("invalid " + type + " \"" + ident + "\" in expression: " + text.trim());
      }
    }
  }

  function checkExpression(exp, text, errors) {
    try {
      new Function("return " + exp);
    } catch (e) {
      var keywordMatch = exp.replace(stripStringRE, '').match(prohibitedKeywordRE);

      if (keywordMatch) {
        errors.push("avoid using JavaScript keyword as property name: " + "\"" + keywordMatch[0] + "\"\n  Raw expression: " + text.trim());
      } else {
        errors.push("invalid expression: " + e.message + " in\n\n" + "    " + exp + "\n\n" + "  Raw expression: " + text.trim() + "\n");
      }
    }
  }
  /*  */


  function createFunction(code, errors) {
    try {
      return new Function(code);
    } catch (err) {
      errors.push({
        err: err,
        code: code
      });
      return noop;
    }
  }

  function createCompileToFunctionFn(compile) {
    var cache = Object.create(null);
    return function compileToFunctions(template, options, vm) {
      options = extend({}, options);
      var warn$$1 = options.warn || warn;
      delete options.warn;
      /* istanbul ignore if */

      {
        // detect possible CSP restriction
        try {
          new Function('return 1');
        } catch (e) {
          if (e.toString().match(/unsafe-eval|CSP/)) {
            warn$$1('It seems you are using the standalone build of Vue.js in an ' + 'environment with Content Security Policy that prohibits unsafe-eval. ' + 'The template compiler cannot work in this environment. Consider ' + 'relaxing the policy to allow unsafe-eval or pre-compiling your ' + 'templates into render functions.');
          }
        }
      } // check cache

      var key = options.delimiters ? String(options.delimiters) + template : template;

      if (cache[key]) {
        return cache[key];
      } // compile


      var compiled = compile(template, options); // check compilation errors/tips

      {
        if (compiled.errors && compiled.errors.length) {
          warn$$1("Error compiling template:\n\n" + template + "\n\n" + compiled.errors.map(function (e) {
            return "- " + e;
          }).join('\n') + '\n', vm);
        }

        if (compiled.tips && compiled.tips.length) {
          compiled.tips.forEach(function (msg) {
            return tip(msg, vm);
          });
        }
      } // turn code into functions

      var res = {};
      var fnGenErrors = [];
      res.render = createFunction(compiled.render, fnGenErrors);
      res.staticRenderFns = compiled.staticRenderFns.map(function (code) {
        return createFunction(code, fnGenErrors);
      }); // check function generation errors.
      // this should only happen if there is a bug in the compiler itself.
      // mostly for codegen development use

      /* istanbul ignore if */

      {
        if ((!compiled.errors || !compiled.errors.length) && fnGenErrors.length) {
          warn$$1("Failed to generate render function:\n\n" + fnGenErrors.map(function (ref) {
            var err = ref.err;
            var code = ref.code;
            return err.toString() + " in\n\n" + code + "\n";
          }).join('\n'), vm);
        }
      }
      return cache[key] = res;
    };
  }
  /*  */


  function createCompilerCreator(baseCompile) {
    return function createCompiler(baseOptions) {
      function compile(template, options) {
        var finalOptions = Object.create(baseOptions);
        var errors = [];
        var tips = [];

        finalOptions.warn = function (msg, tip) {
          (tip ? tips : errors).push(msg);
        };

        if (options) {
          // merge custom modules
          if (options.modules) {
            finalOptions.modules = (baseOptions.modules || []).concat(options.modules);
          } // merge custom directives


          if (options.directives) {
            finalOptions.directives = extend(Object.create(baseOptions.directives || null), options.directives);
          } // copy other options


          for (var key in options) {
            if (key !== 'modules' && key !== 'directives') {
              finalOptions[key] = options[key];
            }
          }
        }

        var compiled = baseCompile(template, finalOptions);
        {
          errors.push.apply(errors, detectErrors(compiled.ast));
        }
        compiled.errors = errors;
        compiled.tips = tips;
        return compiled;
      }

      return {
        compile: compile,
        compileToFunctions: createCompileToFunctionFn(compile)
      };
    };
  }
  /*  */
  // `createCompilerCreator` allows creating compilers that use alternative
  // parser/optimizer/codegen, e.g the SSR optimizing compiler.
  // Here we just export a default compiler using the default parts.


  var createCompiler = createCompilerCreator(function baseCompile(template, options) {
    var ast = parse(template.trim(), options);

    if (options.optimize !== false) {
      optimize(ast, options);
    }

    var code = generate(ast, options);
    return {
      ast: ast,
      render: code.render,
      staticRenderFns: code.staticRenderFns
    };
  });
  /*  */

  var ref$1 = createCompiler(baseOptions);
  var compileToFunctions = ref$1.compileToFunctions;
  /*  */
  // check whether current browser encodes a char inside attribute values

  var div;

  function getShouldDecode(href) {
    div = div || document.createElement('div');
    div.innerHTML = href ? "<a href=\"\n\"/>" : "<div a=\"\n\"/>";
    return div.innerHTML.indexOf('&#10;') > 0;
  } // #3663: IE encodes newlines inside attribute values while other browsers don't


  var shouldDecodeNewlines = inBrowser ? getShouldDecode(false) : false; // #6828: chrome encodes content in a[href]

  var shouldDecodeNewlinesForHref = inBrowser ? getShouldDecode(true) : false;
  /*  */

  var idToTemplate = cached(function (id) {
    var el = query(id);
    return el && el.innerHTML;
  });
  var mount = Vue.prototype.$mount;

  Vue.prototype.$mount = function (el, hydrating) {
    el = el && query(el);
    /* istanbul ignore if */

    if (el === document.body || el === document.documentElement) {
      "development" !== 'production' && warn("Do not mount Vue to <html> or <body> - mount to normal elements instead.");
      return this;
    }

    var options = this.$options; // resolve template/el and convert to render function

    if (!options.render) {
      var template = options.template;

      if (template) {
        if (typeof template === 'string') {
          if (template.charAt(0) === '#') {
            template = idToTemplate(template);
            /* istanbul ignore if */

            if ("development" !== 'production' && !template) {
              warn("Template element not found or is empty: " + options.template, this);
            }
          }
        } else if (template.nodeType) {
          template = template.innerHTML;
        } else {
          {
            warn('invalid template option:' + template, this);
          }
          return this;
        }
      } else if (el) {
        template = getOuterHTML(el);
      }

      if (template) {
        /* istanbul ignore if */
        if ("development" !== 'production' && config.performance && mark) {
          mark('compile');
        }

        var ref = compileToFunctions(template, {
          shouldDecodeNewlines: shouldDecodeNewlines,
          shouldDecodeNewlinesForHref: shouldDecodeNewlinesForHref,
          delimiters: options.delimiters,
          comments: options.comments
        }, this);
        var render = ref.render;
        var staticRenderFns = ref.staticRenderFns;
        options.render = render;
        options.staticRenderFns = staticRenderFns;
        /* istanbul ignore if */

        if ("development" !== 'production' && config.performance && mark) {
          mark('compile end');
          measure("vue " + this._name + " compile", 'compile', 'compile end');
        }
      }
    }

    return mount.call(this, el, hydrating);
  };
  /**
   * Get outerHTML of elements, taking care
   * of SVG elements in IE as well.
   */


  function getOuterHTML(el) {
    if (el.outerHTML) {
      return el.outerHTML;
    } else {
      var container = document.createElement('div');
      container.appendChild(el.cloneNode(true));
      return container.innerHTML;
    }
  }

  Vue.compile = compileToFunctions;
  return Vue;
});