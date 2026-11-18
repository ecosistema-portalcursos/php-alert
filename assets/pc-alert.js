/*!
 * PcAlert - Biblioteca de alertas (estilo SweetAlert)
 * Licença MIT
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.PcAlert = factory();
  }
})(typeof window !== 'undefined' ? window : this, function () {
  'use strict';

  var P = 'pc-alert-';

  var DismissReason = {
    backdrop: 'backdrop',
    cancel: 'cancel',
    close: 'close',
    esc: 'esc',
    timer: 'timer'
  };

  var defaults = {
    title: '',
    text: '',
    html: '',
    icon: null,               // success | error | warning | info | question
    iconColor: null,
    footer: '',
    imageUrl: null,
    imageWidth: null,
    imageHeight: null,
    imageAlt: '',
    width: null,
    padding: null,
    background: null,
    color: null,
    theme: 'light',           // light | dark
    position: 'center',       // top, top-start, top-end, center, center-start, center-end, bottom, bottom-start, bottom-end
    toast: false,
    backdrop: true,
    timer: null,
    timerProgressBar: false,
    showConfirmButton: true,
    showDenyButton: false,
    showCancelButton: false,
    showCloseButton: false,
    confirmButtonText: 'OK',
    denyButtonText: 'Não',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: null,
    denyButtonColor: null,
    cancelButtonColor: null,
    reverseButtons: false,
    focusConfirm: true,
    focusCancel: false,
    allowOutsideClick: true,
    allowEscapeKey: true,
    allowEnterKey: true,
    input: null,              // text | email | password | number | tel | url | textarea | select | checkbox
    inputLabel: '',
    inputPlaceholder: '',
    inputValue: '',
    inputOptions: {},
    inputAttributes: {},
    inputValidator: null,
    validationMessage: null,
    preConfirm: null,
    preDeny: null,
    showLoaderOnConfirm: false,
    customClass: {},
    didOpen: null,
    willClose: null,
    didClose: null
  };

  var ICONS = {
    success: '<svg viewBox="0 0 52 52"><path d="M14 27 l8 8 l16 -18"/></svg>',
    error: '<svg viewBox="0 0 52 52"><line x1="16" y1="16" x2="36" y2="36"/><line x1="36" y1="16" x2="16" y2="36"/></svg>',
    warning: '<span class="' + P + 'icon-content">!</span>',
    info: '<span class="' + P + 'icon-content">i</span>',
    question: '<span class="' + P + 'icon-content">?</span>'
  };

  var state = null; // alerta atual

  function assign(target) {
    for (var i = 1; i < arguments.length; i++) {
      var src = arguments[i];
      if (!src) continue;
      for (var k in src) {
        if (Object.prototype.hasOwnProperty.call(src, k)) target[k] = src[k];
      }
    }
    return target;
  }

  function el(tag, cls, attrs) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (attrs) for (var k in attrs) e.setAttribute(k, attrs[k]);
    return e;
  }

  function addCustom(node, opts, key) {
    var c = opts.customClass && opts.customClass[key];
    if (c) String(c).split(/\s+/).forEach(function (x) { if (x) node.classList.add(x); });
  }

  function setContent(node, value) {
    if (value === null || value === undefined || value === '') return false;
    if (value instanceof HTMLElement) {
      node.appendChild(value);
    } else {
      node.innerHTML = String(value);
    }
    return true;
  }

  function normalizeArgs(args) {
    if (typeof args[0] === 'object' && args[0] !== null) return args[0];
    return { title: args[0] || '', text: args[1] || '', icon: args[2] || null };
  }

  function buildInput(opts) {
    var type = opts.input;
    var input;
    if (type === 'textarea') {
      input = el('textarea', P + 'textarea');
      input.value = opts.inputValue || '';
    } else if (type === 'select') {
      input = el('select', P + 'select');
      if (opts.inputPlaceholder) {
        var ph = el('option', null, { value: '', disabled: '' });
        ph.textContent = opts.inputPlaceholder;
        ph.selected = !opts.inputValue;
        input.appendChild(ph);
      }
      var options = opts.inputOptions || {};
      var entries = Array.isArray(options) ? options : Object.keys(options).map(function (k) { return [k, options[k]]; });
      entries.forEach(function (pair) {
        var o = el('option', null, { value: pair[0] });
        o.textContent = pair[1];
        if (String(pair[0]) === String(opts.inputValue)) o.selected = true;
        input.appendChild(o);
      });
    } else if (type === 'checkbox') {
      var wrap = el('label', P + 'checkbox');
      input = el('input', null, { type: 'checkbox' });
      input.checked = !!opts.inputValue;
      wrap.appendChild(input);
      var span = el('span');
      span.textContent = ' ' + (opts.inputPlaceholder || '');
      wrap.appendChild(span);
      input._wrapper = wrap;
    } else {
      input = el('input', P + 'input', { type: type || 'text' });
      input.value = opts.inputValue || '';
    }
    if (opts.inputPlaceholder && type !== 'select' && type !== 'checkbox') input.placeholder = opts.inputPlaceholder;
    var attrs = opts.inputAttributes || {};
    for (var a in attrs) input.setAttribute(a, attrs[a]);
    addCustom(input, opts, 'input');
    return input;
  }

  function getInputValue(input, type) {
    if (!input) return undefined;
    if (type === 'checkbox') return input.checked ? 1 : 0;
    if (type === 'number') return input.value === '' ? '' : Number(input.value);
    return input.value;
  }

  function lockBody(toast) {
    document.body.classList.add(P + 'shown');
    if (toast) document.body.classList.add(P + 'toast-shown');
  }

  function unlockBody() {
    document.body.classList.remove(P + 'shown', P + 'toast-shown');
  }

  function Timer(cb, delay) {
    var id, start, remaining = delay, running = false;
    this.start = function () {
      if (running) return;
      running = true;
      start = Date.now();
      id = setTimeout(cb, remaining);
    };
    this.stop = function () {
      if (!running) return;
      running = false;
      clearTimeout(id);
      remaining -= Date.now() - start;
    };
    this.isRunning = function () { return running; };
    this.getLeft = function () { return running ? remaining - (Date.now() - start) : remaining; };
    this.increase = function (n) { var r = running; this.stop(); remaining += n; if (r) this.start(); };
    this.clear = function () { running = false; clearTimeout(id); };
  }

  function render(opts, resolve) {
    // Fecha um alerta anterior, se houver
    if (state) closeInternal({ isConfirmed: false, isDenied: false, isDismissed: true, dismiss: 'replaced' }, true);

    var toast = !!opts.toast;
    var container = el('div', P + 'container ' + P + (opts.position || 'center'));
    container.classList.add(P + 'theme-' + (opts.theme || 'light'));
    if (toast) container.classList.add(P + 'toast-container', P + 'no-backdrop');
    if (!opts.backdrop && !toast) container.classList.add(P + 'no-backdrop');
    if (typeof opts.backdrop === 'string') container.style.background = opts.backdrop;
    addCustom(container, opts, 'container');

    var popup = el('div', P + 'popup' + (toast ? ' ' + P + 'toast' : ''), {
      role: toast ? 'alert' : 'dialog',
      'aria-modal': toast ? 'false' : 'true',
      'aria-live': 'assertive',
      tabindex: '-1'
    });
    if (opts.width) popup.style.width = typeof opts.width === 'number' ? opts.width + 'px' : opts.width;
    if (opts.padding) popup.style.padding = typeof opts.padding === 'number' ? opts.padding + 'px' : opts.padding;
    if (opts.background) popup.style.background = opts.background;
    if (opts.color) popup.style.color = opts.color;
    addCustom(popup, opts, 'popup');

    var closeBtn = null;
    if (opts.showCloseButton) {
      closeBtn = el('button', P + 'close', { type: 'button', 'aria-label': 'Fechar' });
      closeBtn.innerHTML = '&times;';
      addCustom(closeBtn, opts, 'closeButton');
    }

    if (opts.icon && ICONS[opts.icon]) {
      var icon = el('div', P + 'icon ' + P + opts.icon + ' ' + P + 'animate');
      icon.innerHTML = ICONS[opts.icon];
      if (opts.iconColor) { icon.style.color = opts.iconColor; icon.style.borderColor = opts.iconColor; }
      addCustom(icon, opts, 'icon');
      popup.appendChild(icon);
    }

    if (opts.imageUrl) {
      var img = el('img', P + 'image', { src: opts.imageUrl, alt: opts.imageAlt || '' });
      if (opts.imageWidth) img.style.width = typeof opts.imageWidth === 'number' ? opts.imageWidth + 'px' : opts.imageWidth;
      if (opts.imageHeight) img.style.height = typeof opts.imageHeight === 'number' ? opts.imageHeight + 'px' : opts.imageHeight;
      addCustom(img, opts, 'image');
      popup.appendChild(img);
    }

    var title = el('h2', P + 'title', { id: P + 'title' });
    if (setContent(title, opts.title)) {
      popup.setAttribute('aria-labelledby', P + 'title');
      addCustom(title, opts, 'title');
      popup.appendChild(title);
    }

    if (toast && closeBtn) popup.appendChild(closeBtn);

    var body = el('div', P + 'html', { id: P + 'html' });
    var hasBody = false;
    if (opts.html) {
      hasBody = setContent(body, opts.html);
    } else if (opts.text) {
      body.textContent = String(opts.text);
      hasBody = true;
    }
    if (hasBody) {
      popup.setAttribute('aria-describedby', P + 'html');
      addCustom(body, opts, 'htmlContainer');
      popup.appendChild(body);
    }

    var input = null;
    if (opts.input) {
      if (opts.inputLabel) {
        var label = el('label', P + 'input-label');
        label.textContent = opts.inputLabel;
        label.style.display = 'block';
        label.style.marginTop = '1em';
        popup.appendChild(label);
      }
      input = buildInput(opts);
      popup.appendChild(input._wrapper || input);
    }

    var validation = el('div', P + 'validation', { role: 'alert' });
    addCustom(validation, opts, 'validationMessage');
    popup.appendChild(validation);

    var actions = el('div', P + 'actions' + (opts.reverseButtons ? ' ' + P + 'reverse' : ''));
    addCustom(actions, opts, 'actions');
    var loader = el('div', P + 'loader');
    actions.appendChild(loader);

    function makeButton(kind, text, color, show) {
      if (!show) return null;
      var b = el('button', P + 'button ' + P + kind, { type: 'button' });
      b.innerHTML = text;
      if (color) b.style.background = color;
      addCustom(b, opts, kind + 'Button');
      actions.appendChild(b);
      return b;
    }

    var confirmBtn = makeButton('confirm', opts.confirmButtonText, opts.confirmButtonColor, opts.showConfirmButton);
    var denyBtn = makeButton('deny', opts.denyButtonText, opts.denyButtonColor, opts.showDenyButton);
    var cancelBtn = makeButton('cancel', opts.cancelButtonText, opts.cancelButtonColor, opts.showCancelButton);

    if (confirmBtn || denyBtn || cancelBtn) popup.appendChild(actions);

    if (opts.footer) {
      var footer = el('div', P + 'footer');
      setContent(footer, opts.footer);
      addCustom(footer, opts, 'footer');
      popup.appendChild(footer);
    }

    if (!toast && closeBtn) popup.appendChild(closeBtn);

    var timerBar = null;
    if (opts.timer && opts.timerProgressBar) {
      var wrap = el('div', P + 'timer-bar-wrap');
      timerBar = el('div', P + 'timer-bar');
      addCustom(timerBar, opts, 'timerProgressBar');
      wrap.appendChild(timerBar);
      popup.appendChild(wrap);
    }

    container.appendChild(popup);
    document.body.appendChild(container);

    var previousFocus = document.activeElement;

    state = {
      opts: opts,
      resolve: resolve,
      container: container,
      popup: popup,
      input: input,
      validation: validation,
      actions: actions,
      confirmBtn: confirmBtn,
      denyBtn: denyBtn,
      cancelBtn: cancelBtn,
      closeBtn: closeBtn,
      timerBar: timerBar,
      timer: null,
      previousFocus: previousFocus,
      keyHandler: null
    };
    var current = state;

    lockBody(toast);

    // Animação de entrada
    popup.classList.add(P + 'show');
    requestAnimationFrame(function () {
      if (!toast && opts.backdrop !== false && typeof opts.backdrop !== 'string') {
        container.classList.add(P + 'backdrop-show');
      }
    });

    // Eventos
    if (confirmBtn) confirmBtn.addEventListener('click', function () { handleConfirm(current); });
    if (denyBtn) denyBtn.addEventListener('click', function () { handleDeny(current); });
    if (cancelBtn) cancelBtn.addEventListener('click', function () { dismiss(current, DismissReason.cancel); });
    if (closeBtn) closeBtn.addEventListener('click', function () { dismiss(current, DismissReason.close); });

    var mouseDownOnPopup = false;
    popup.addEventListener('mousedown', function () { mouseDownOnPopup = true; });
    container.addEventListener('mouseup', function () {
      setTimeout(function () { mouseDownOnPopup = false; }, 0);
    });
    container.addEventListener('click', function (e) {
      if (e.target !== container || mouseDownOnPopup || toast) return;
      var allow = typeof opts.allowOutsideClick === 'function' ? opts.allowOutsideClick() : opts.allowOutsideClick;
      if (allow && opts.backdrop !== false) dismiss(current, DismissReason.backdrop);
    });

    if (input) {
      input.addEventListener('input', function () { resetValidation(current); });
    }

    current.keyHandler = function (e) {
      if (state !== current) return;
      if (e.key === 'Escape' || e.key === 'Esc') {
        var allow = typeof opts.allowEscapeKey === 'function' ? opts.allowEscapeKey() : opts.allowEscapeKey;
        if (allow) { e.preventDefault(); dismiss(current, DismissReason.esc); }
      } else if (e.key === 'Enter' && opts.allowEnterKey && !e.isComposing) {
        var t = e.target;
        if (t && t.tagName === 'TEXTAREA') return;
        if (t && t.tagName === 'BUTTON' && t !== confirmBtn) return;
        if (popup.contains(t) && confirmBtn && !toast) { e.preventDefault(); handleConfirm(current); }
      } else if (e.key === 'Tab' && !toast) {
        trapFocus(popup, e);
      }
    };
    document.addEventListener('keydown', current.keyHandler);

    // Foco
    if (!toast) {
      setTimeout(function () {
        if (state !== current) return;
        if (input) {
          input.focus();
          if (input.select && opts.input !== 'select' && opts.input !== 'checkbox') input.select();
        } else if (opts.focusCancel && cancelBtn) cancelBtn.focus();
        else if (opts.focusConfirm && confirmBtn) confirmBtn.focus();
        else popup.focus();
      }, 10);
    }

    // Timer
    if (opts.timer) {
      current.timer = new Timer(function () { dismiss(current, DismissReason.timer); }, opts.timer);
      current.timer.start();
      if (timerBar) animateTimerBar(current);
      if (toast) {
        popup.addEventListener('mouseenter', function () { stopTimer(); });
        popup.addEventListener('mouseleave', function () { resumeTimer(); });
      }
    }

    if (typeof opts.didOpen === 'function') opts.didOpen(popup);
  }

  function animateTimerBar(s) {
    if (!s.timerBar || !s.timer) return;
    var left = s.timer.getLeft();
    var total = s.opts.timer;
    s.timerBar.style.transition = 'none';
    s.timerBar.style.width = (left / total * 100) + '%';
    // força reflow
    void s.timerBar.offsetWidth;
    if (s.timer.isRunning()) {
      s.timerBar.style.transition = 'width ' + (left / 1000) + 's linear';
      s.timerBar.style.width = '0%';
    }
  }

  function trapFocus(popup, e) {
    var focusables = Array.prototype.slice.call(
      popup.querySelectorAll('button:not([disabled]), input, select, textarea, [href], [tabindex]:not([tabindex="-1"])')
    ).filter(function (n) { return n.offsetParent !== null; });
    if (!focusables.length) { e.preventDefault(); popup.focus(); return; }
    var first = focusables[0];
    var last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    else if (!popup.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
  }

  function showValidation(s, msg) {
    if (!s) return;
    s.validation.innerHTML = msg;
    s.validation.classList.add(P + 'visible');
    if (s.input) { s.input.classList.add(P + 'invalid'); s.input.focus(); }
  }

  function resetValidation(s) {
    if (!s) return;
    s.validation.classList.remove(P + 'visible');
    s.validation.innerHTML = '';
    if (s.input) s.input.classList.remove(P + 'invalid');
  }

  function setLoading(s, on) {
    if (!s) return;
    s.actions.classList.toggle(P + 'loading', !!on);
    s.popup.classList.toggle(P + 'loading', !!on);
    [s.confirmBtn, s.denyBtn, s.cancelBtn].forEach(function (b) { if (b) b.disabled = !!on; });
    if (s.input) s.input.disabled = !!on;
  }

  function nativeValidity(s) {
    var input = s.input;
    if (!input || !input.checkValidity) return null;
    if (!input.checkValidity()) {
      return s.opts.validationMessage || input.validationMessage || 'Valor inválido';
    }
    if (s.opts.input === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
      return s.opts.validationMessage || 'Endereço de e-mail inválido';
    }
    return null;
  }

  function handleConfirm(s) {
    if (s !== state || s.busy) return;
    var value = s.input ? getInputValue(s.input, s.opts.input) : true;

    var nativeError = s.input ? nativeValidity(s) : null;
    if (nativeError) { showValidation(s, nativeError); return; }

    s.busy = true;
    var chain = Promise.resolve();

    if (s.input && typeof s.opts.inputValidator === 'function') {
      chain = chain.then(function () { return s.opts.inputValidator(value); }).then(function (err) {
        if (err) { showValidation(s, err); throw { __pcValidation: true }; }
      });
    }

    if (typeof s.opts.preConfirm === 'function') {
      chain = chain.then(function () {
        resetValidation(s);
        if (s.opts.showLoaderOnConfirm) setLoading(s, true);
        return s.opts.preConfirm(value);
      }).then(function (res) {
        setLoading(s, false);
        if (res === false) throw { __pcValidation: true };
        if (res !== undefined && res !== true) value = res;
      });
    }

    chain.then(function () {
      s.busy = false;
      if (s !== state) return;
      closeInternal({ isConfirmed: true, isDenied: false, isDismissed: false, value: value });
    }).catch(function (err) {
      s.busy = false;
      setLoading(s, false);
      if (err && err.__pcValidation) return;
      showValidation(s, 'Erro: ' + (err && err.message ? err.message : err));
    });
  }

  function handleDeny(s) {
    if (s !== state || s.busy) return;
    var value = s.input ? getInputValue(s.input, s.opts.input) : false;
    if (typeof s.opts.preDeny === 'function') {
      s.busy = true;
      Promise.resolve(s.opts.preDeny(value)).then(function (res) {
        s.busy = false;
        if (res === false) return;
        if (res !== undefined && res !== true) value = res;
        closeInternal({ isConfirmed: false, isDenied: true, isDismissed: false, value: value });
      }).catch(function (err) {
        s.busy = false;
        showValidation(s, 'Erro: ' + (err && err.message ? err.message : err));
      });
      return;
    }
    closeInternal({ isConfirmed: false, isDenied: true, isDismissed: false, value: value });
  }

  function dismiss(s, reason) {
    if (s !== state) return;
    closeInternal({ isConfirmed: false, isDenied: false, isDismissed: true, dismiss: reason });
  }

  function closeInternal(result, immediate) {
    var s = state;
    if (!s) return;
    state = null;

    if (s.timer) s.timer.clear();
    document.removeEventListener('keydown', s.keyHandler);
    if (typeof s.opts.willClose === 'function') s.opts.willClose(s.popup);

    var finish = function () {
      if (s.container.parentNode) s.container.parentNode.removeChild(s.container);
      if (!state) unlockBody();
      if (!s.opts.toast && s.previousFocus && s.previousFocus.focus && document.body.contains(s.previousFocus)) {
        try { s.previousFocus.focus(); } catch (e) { /* ignora */ }
      }
      if (typeof s.opts.didClose === 'function') s.opts.didClose();
    };

    if (immediate) {
      finish();
    } else {
      s.popup.classList.remove(P + 'show');
      s.popup.classList.add(P + 'hide');
      s.container.classList.remove(P + 'backdrop-show');
      setTimeout(finish, 150);
    }

    s.resolve(assign({ isConfirmed: false, isDenied: false, isDismissed: false, value: undefined }, result));
  }

  // ---------------------------------------------------------------- API ---

  function fire() {
    var userOpts = normalizeArgs(arguments);
    var opts = assign({}, defaults, this && this.__mixin ? this.__mixin : null, userOpts);
    if (opts.toast) {
      if (userOpts.position === undefined && !(this && this.__mixin && this.__mixin.position)) opts.position = 'top-end';
      if (userOpts.showConfirmButton === undefined && !(this && this.__mixin && 'showConfirmButton' in this.__mixin)) opts.showConfirmButton = false;
    }
    return new Promise(function (resolve) {
      var run = function () { render(opts, resolve); };
      if (document.body) run();
      else document.addEventListener('DOMContentLoaded', run);
    });
  }

  function stopTimer() {
    if (!state || !state.timer) return undefined;
    state.timer.stop();
    animateTimerBar(state);
    return state.timer.getLeft();
  }

  function resumeTimer() {
    if (!state || !state.timer) return undefined;
    state.timer.start();
    animateTimerBar(state);
    return state.timer.getLeft();
  }

  var PcAlert = {
    version: '1.0.0',
    DismissReason: DismissReason,
    fire: fire,
    mixin: function (mixinOpts) {
      var base = this && this.__mixin ? this.__mixin : {};
      var merged = assign({}, base, mixinOpts);
      var child = assign({}, PcAlert, { __mixin: merged });
      child.fire = function () { return fire.apply(child, arguments); };
      return child;
    },
    close: function (result) {
      if (!state) return;
      closeInternal(assign({ isConfirmed: false, isDenied: false, isDismissed: true }, result || {}));
    },
    isVisible: function () { return !!state; },
    isLoading: function () { return !!(state && state.actions.classList.contains(P + 'loading')); },
    showLoading: function () { setLoading(state, true); },
    hideLoading: function () { setLoading(state, false); },
    showValidationMessage: function (msg) { showValidation(state, msg); },
    resetValidationMessage: function () { resetValidation(state); },
    clickConfirm: function () { if (state) handleConfirm(state); },
    clickDeny: function () { if (state) handleDeny(state); },
    clickCancel: function () { if (state) dismiss(state, DismissReason.cancel); },
    getPopup: function () { return state ? state.popup : null; },
    getContainer: function () { return state ? state.container : null; },
    getTitle: function () { return state ? state.popup.querySelector('.' + P + 'title') : null; },
    getHtmlContainer: function () { return state ? state.popup.querySelector('.' + P + 'html') : null; },
    getInput: function () { return state ? state.input : null; },
    getConfirmButton: function () { return state ? state.confirmBtn : null; },
    getDenyButton: function () { return state ? state.denyBtn : null; },
    getCancelButton: function () { return state ? state.cancelBtn : null; },
    getTimerLeft: function () { return state && state.timer ? state.timer.getLeft() : undefined; },
    stopTimer: stopTimer,
    resumeTimer: resumeTimer,
    isTimerRunning: function () { return !!(state && state.timer && state.timer.isRunning()); },
    increaseTimer: function (ms) {
      if (!state || !state.timer) return undefined;
      state.timer.increase(ms);
      animateTimerBar(state);
      return state.timer.getLeft();
    },
    /** Define os padrões globais (ex.: textos dos botões em outro idioma). */
    setDefaults: function (opts) { assign(defaults, opts); },
    /** Executa uma fila de alertas vindos do PHP (usado internamente pelo PcAlert PHP). */
    queue: function (list) {
      var self = this;
      return (list || []).reduce(function (p, item) {
        return p.then(function () { return self.fire(item); });
      }, Promise.resolve());
    }
  };

  return PcAlert;
});
