"use strict";
(self["webpackChunkbig4_frontend"] = self["webpackChunkbig4_frontend"] || []).push([["app"],{

/***/ "./assets/app.js":
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _styles_app_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./styles/app.css */ "./assets/styles/app.css");
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i.return) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
// Import main CSS so Encore extracts it with the `app` entry

document.addEventListener('DOMContentLoaded', function () {
  // NAV SCROLL
  var nav = document.getElementById('nav');
  if (nav) {
    window.addEventListener('scroll', function () {
      nav.classList.toggle('scrolled', window.scrollY > 40);
    });
  }

  // SMOOTH REVEAL
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('show');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15
  });
  var revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
  if (revealEls.length) {
    revealEls.forEach(function (el) {
      observer.observe(el);
    });
  }

  // CURRENCY CONVERSION (fixed rates from 1 TND)
  var currencyRates = {
    TND: 1,
    USD: 0.32,
    EUR: 0.30,
    CNY: 2.31
  };
  document.querySelectorAll('.currency-select').forEach(function (select) {
    select.addEventListener('change', function () {
      var footer = select.closest('.menu-card-footer');
      var priceEl = footer ? footer.querySelector('.js-convertible-price') : null;
      if (!priceEl) {
        return;
      }
      var basePrice = Number(priceEl.dataset.basePrice || select.dataset.basePrice || 0);
      var currency = Object.prototype.hasOwnProperty.call(currencyRates, select.value) ? select.value : 'TND';
      var converted = basePrice * currencyRates[currency];
      priceEl.textContent = "".concat(converted.toFixed(2), " ").concat(currency);
    });
  });

  // BOOKING POPUP
  var bookingPopup = document.getElementById('bookingPopup');
  var openBooking = document.getElementById('openBooking');
  var openBooking2 = document.getElementById('openBooking2');
  var closeBooking = document.getElementById('closeBooking');
  var bookingForm = document.getElementById('bookingForm');
  function showBooking() {
    if (!bookingPopup) {
      return;
    }
    bookingPopup.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function hideBooking() {
    if (!bookingPopup) {
      return;
    }
    bookingPopup.classList.remove('active');
    document.body.style.overflow = '';
  }
  if (openBooking) {
    openBooking.addEventListener('click', function (e) {
      e.preventDefault();
      showBooking();
    });
  }
  if (openBooking2) {
    openBooking2.addEventListener('click', function (e) {
      e.preventDefault();
      showBooking();
    });
  }
  if (closeBooking) {
    closeBooking.addEventListener('click', hideBooking);
  }
  if (bookingPopup) {
    bookingPopup.addEventListener('click', function (e) {
      if (e.target === bookingPopup) hideBooking();
    });
  }
  if (bookingForm) {
    bookingForm.addEventListener('submit', function (e) {
      e.preventDefault();
      alert("Your booking request has been submitted successfully!");
      bookingForm.reset();
      hideBooking();
    });
  }

  // PROFILE POPUP
  var profilePopup = document.getElementById('profilePopup');
  var openProfile = document.getElementById('openProfile');
  var closeProfile = document.getElementById('closeProfile');
  var profileForm = document.getElementById('profileForm');
  function showProfile() {
    if (!profilePopup) {
      window.location.href = '/profile';
      return;
    }
    profilePopup.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function hideProfile() {
    if (!profilePopup) {
      return;
    }
    profilePopup.classList.remove('active');
    document.body.style.overflow = '';
  }
  if (openProfile) {
    openProfile.addEventListener('click', showProfile);
  }
  if (closeProfile) {
    closeProfile.addEventListener('click', hideProfile);
  }
  if (profilePopup) {
    profilePopup.addEventListener('click', function (e) {
      if (e.target === profilePopup) hideProfile();
    });
  }
  if (profileForm) {
    profileForm.addEventListener('submit', /*#__PURE__*/function () {
      var _ref = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(e) {
        var submitBtn, originalText, response, payload, fullNameInput, phoneInput, addressInput, emailInput, _t;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              e.preventDefault();
              if (!(profileForm.dataset.authenticated !== '1')) {
                _context.n = 1;
                break;
              }
              alert('Please sign in first.');
              window.location.href = '/login';
              return _context.a(2);
            case 1:
              submitBtn = profileForm.querySelector('button[type="submit"]');
              originalText = submitBtn ? submitBtn.textContent : 'Save Profile';
              _context.p = 2;
              if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
              }
              _context.n = 3;
              return fetch(profileForm.action, {
                method: 'POST',
                body: new FormData(profileForm),
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                }
              });
            case 3:
              response = _context.v;
              _context.n = 4;
              return response.json();
            case 4:
              payload = _context.v;
              if (!(!response.ok || !payload.success)) {
                _context.n = 5;
                break;
              }
              throw new Error(payload.message || 'Unable to save profile.');
            case 5:
              fullNameInput = profileForm.querySelector('input[name="full_name"]');
              phoneInput = profileForm.querySelector('input[name="phone"]');
              addressInput = profileForm.querySelector('input[name="address"]');
              emailInput = profileForm.querySelector('input[name="email"]');
              if (fullNameInput && payload.profile && typeof payload.profile.fullName === 'string') {
                fullNameInput.value = payload.profile.fullName;
              }
              if (phoneInput && payload.profile && typeof payload.profile.phone === 'string') {
                phoneInput.value = payload.profile.phone;
              }
              if (addressInput && payload.profile && typeof payload.profile.address === 'string') {
                addressInput.value = payload.profile.address;
              }
              if (emailInput && payload.profile && typeof payload.profile.email === 'string') {
                emailInput.value = payload.profile.email;
              }
              alert(payload.message || 'Your profile has been saved successfully.');
              hideProfile();
              _context.n = 7;
              break;
            case 6:
              _context.p = 6;
              _t = _context.v;
              alert(_t instanceof Error ? _t.message : 'Unable to save profile.');
            case 7:
              _context.p = 7;
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
              }
              return _context.f(7);
            case 8:
              return _context.a(2);
          }
        }, _callee, null, [[2, 6, 7, 8]]);
      }));
      return function (_x) {
        return _ref.apply(this, arguments);
      };
    }());
  }

  // CART SYSTEM
  var cartOverlay = document.getElementById('cartOverlay');
  var openCart = document.getElementById('openCart');
  var openCart2 = document.getElementById('openCart2');
  var closeCart = document.getElementById('closeCart');
  var cartItemsContainer = document.getElementById('cartItems');
  var cartTotal = document.getElementById('cartTotal');
  var cartCount = document.getElementById('cartCount');
  var checkoutBtn = document.getElementById('checkoutBtn');
  var cart = [];
  function showCart() {
    if (!cartOverlay) {
      return;
    }
    cartOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function hideCart() {
    if (!cartOverlay) {
      return;
    }
    cartOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }
  if (openCart) {
    openCart.addEventListener('click', showCart);
  }
  if (openCart2) {
    openCart2.addEventListener('click', showCart);
  }
  if (closeCart) {
    closeCart.addEventListener('click', hideCart);
  }
  if (cartOverlay) {
    cartOverlay.addEventListener('click', function (e) {
      if (e.target === cartOverlay) hideCart();
    });
  }

  // Existing direct bindings (for buttons present at load)
  document.querySelectorAll('.add-cart-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var name = btn.dataset.name;
      var price = Number(btn.dataset.price);
      cart.push({
        name: name,
        price: price
      });
      updateCart();
      btn.textContent = "Added";
      setTimeout(function () {
        return btn.textContent = "Add to Cart";
      }, 1200);
    });
  });

  // Event delegation: ensure dynamically inserted or later-updated buttons also work
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.add-cart-btn');
    if (!btn) return;
    e.preventDefault();
    var name = btn.dataset.name;
    var price = Number(btn.dataset.price);
    cart.push({
      name: name,
      price: price
    });
    updateCart();
    var original = btn.textContent;
    btn.textContent = 'Added';
    setTimeout(function () {
      return btn.textContent = original || 'Add to Cart';
    }, 1200);
  });
  function updateCart() {
    if (!cartItemsContainer || !cartTotal || !cartCount) {
      return;
    }
    cartItemsContainer.innerHTML = '';
    if (cart.length === 0) {
      cartItemsContainer.innerHTML = "<div class=\"empty-cart\">Your luxury cart is currently empty.</div>";
    } else {
      cart.forEach(function (item, index) {
        var cartItem = document.createElement('div');
        cartItem.classList.add('cart-item');
        cartItem.innerHTML = "\n            <div>\n              <h4>".concat(item.name, "</h4>\n              <p>").concat(item.price, " TND</p>\n            </div>\n            <button class=\"remove-btn\" onclick=\"removeFromCart(").concat(index, ")\">Remove</button>\n          ");
        cartItemsContainer.appendChild(cartItem);
      });
    }
    var total = cart.reduce(function (sum, item) {
      return sum + item.price;
    }, 0);
    cartTotal.textContent = "".concat(total, " TND");
    cartCount.textContent = cart.length;
  }
  function showFrontFlash(message) {
    var isError = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var existing = document.getElementById('frontFlashToast');
    if (existing) {
      existing.remove();
    }
    var toast = document.createElement('div');
    toast.id = 'frontFlashToast';
    toast.style.cssText = 'position:fixed;top:92px;left:50%;transform:translateX(-50%);z-index:9999;padding:1rem 1.6rem;border-radius:999px;font-size:.92rem;font-weight:600;color:#fff;box-shadow:0 10px 30px rgba(44,26,14,.25);max-width:90vw;text-align:center;';
    toast.style.background = isError ? 'linear-gradient(135deg,#D94040,#a82a2a)' : 'linear-gradient(135deg,#2E9E6A,#1e7a52)';
    toast.textContent = "".concat(isError ? '✕' : '✓', " ").concat(message);
    document.body.appendChild(toast);
    window.setTimeout(function () {
      toast.remove();
    }, 3500);
  }
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (cart.length === 0) {
        window.location.href = '/orders/create-from-cart?validation_only=1';
        return;
      }
      var total = cart.reduce(function (sum, item) {
        return sum + item.price;
      }, 0);
      var cartItemsInput = document.getElementById('redirectCartItemsInput');
      var cartTotalInput = document.getElementById('redirectCartTotalInput');
      if (cartItemsInput && cartTotalInput) {
        cartItemsInput.value = JSON.stringify(cart);
        cartTotalInput.value = total.toFixed(2);
      }

      // Hide the cart overlay
      var cartOverlay = document.getElementById('cartOverlay');
      if (cartOverlay) {
        cartOverlay.classList.remove('active');
      }

      // Show order type selection modal
      var modal = document.getElementById('orderTypeModal');
      if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }
    });
  }
  function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
  }
  window.removeFromCart = removeFromCart;

  // ORDER TYPE MODAL
  var orderTypeModal = document.getElementById('orderTypeModal');
  var dineInBtn = document.getElementById('dineInBtn');
  var deliveryBtn = document.getElementById('deliveryBtn');
  var closeOrderTypeBtn = document.getElementById('closeOrderType');
  var orderTypeInput = document.getElementById('orderTypeInput');
  var checkoutRedirectForm = document.getElementById('checkoutRedirectForm');
  function closeOrderTypeModal() {
    if (orderTypeModal) {
      orderTypeModal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }
  if (closeOrderTypeBtn) {
    closeOrderTypeBtn.addEventListener('click', closeOrderTypeModal);
  }
  if (orderTypeModal) {
    orderTypeModal.addEventListener('click', function (e) {
      // Only close if clicking the background overlay, not the inner content
      if (e.target === orderTypeModal) closeOrderTypeModal();
    });
  }
  if (dineInBtn) {
    dineInBtn.addEventListener('click', function () {
      if (orderTypeInput) {
        orderTypeInput.value = 'DINE_IN';
      }
      if (checkoutRedirectForm) {
        checkoutRedirectForm.submit();
      }
    });
  }
  if (deliveryBtn) {
    deliveryBtn.addEventListener('click', function () {
      if (orderTypeInput) {
        orderTypeInput.value = 'DELIVERY';
      }
      if (checkoutRedirectForm) {
        checkoutRedirectForm.submit();
      }
    });
  }

  // ESC CLOSE
  document.addEventListener('keydown', function (e) {
    if (e.key === "Escape") {
      hideBooking();
      hideCart();
      hideProfile();
      closeOrderTypeModal();
    }
  });
});

/***/ }),

/***/ "./assets/styles/app.css":
/*!*******************************!*\
  !*** ./assets/styles/app.css ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ var __webpack_exec__ = function(moduleId) { return __webpack_require__(__webpack_require__.s = moduleId); }
/******/ var __webpack_exports__ = (__webpack_exec__("./assets/app.js"));
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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ZW5kLy4vYXNzZXRzL2FwcC5qcyIsIndlYnBhY2s6Ly9iaWc0LWZyb250ZW5kLy4vYXNzZXRzL3N0eWxlcy9hcHAuY3NzPzZiZTYiXSwic291cmNlc0NvbnRlbnQiOlsiXHJcbi8vIEltcG9ydCBtYWluIENTUyBzbyBFbmNvcmUgZXh0cmFjdHMgaXQgd2l0aCB0aGUgYGFwcGAgZW50cnlcclxuaW1wb3J0ICcuL3N0eWxlcy9hcHAuY3NzJztcclxuXHJcbmRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ0RPTUNvbnRlbnRMb2FkZWQnLCBmdW5jdGlvbigpIHtcclxuICAgIC8vIE5BViBTQ1JPTExcclxuICAgIGNvbnN0IG5hdiA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCduYXYnKTtcclxuICAgIGlmIChuYXYpIHtcclxuICAgICAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Njcm9sbCcsKCk9PntcclxuICAgICAgICBuYXYuY2xhc3NMaXN0LnRvZ2dsZSgnc2Nyb2xsZWQnLCB3aW5kb3cuc2Nyb2xsWSA+IDQwKTtcclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgLy8gU01PT1RIIFJFVkVBTFxyXG4gICAgY29uc3Qgb2JzZXJ2ZXIgPSBuZXcgSW50ZXJzZWN0aW9uT2JzZXJ2ZXIoKGVudHJpZXMpPT57XHJcbiAgICAgIGVudHJpZXMuZm9yRWFjaCgoZW50cnkpPT57XHJcbiAgICAgICAgaWYoZW50cnkuaXNJbnRlcnNlY3Rpbmcpe1xyXG4gICAgICAgICAgZW50cnkudGFyZ2V0LmNsYXNzTGlzdC5hZGQoJ3Nob3cnKTtcclxuICAgICAgICAgIG9ic2VydmVyLnVub2JzZXJ2ZShlbnRyeS50YXJnZXQpO1xyXG4gICAgICAgIH1cclxuICAgICAgfSk7XHJcbiAgICB9LHt0aHJlc2hvbGQ6MC4xNX0pO1xyXG5cclxuICAgIGNvbnN0IHJldmVhbEVscyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5yZXZlYWwsIC5yZXZlYWwtbGVmdCwgLnJldmVhbC1yaWdodCcpO1xyXG4gICAgaWYgKHJldmVhbEVscy5sZW5ndGgpIHtcclxuICAgICAgcmV2ZWFsRWxzLmZvckVhY2goZWw9PntcclxuICAgICAgICBvYnNlcnZlci5vYnNlcnZlKGVsKTtcclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgLy8gQ1VSUkVOQ1kgQ09OVkVSU0lPTiAoZml4ZWQgcmF0ZXMgZnJvbSAxIFRORClcclxuICAgIGNvbnN0IGN1cnJlbmN5UmF0ZXMgPSB7IFRORDogMSwgVVNEOiAwLjMyLCBFVVI6IDAuMzAsIENOWTogMi4zMSB9O1xyXG4gICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnLmN1cnJlbmN5LXNlbGVjdCcpLmZvckVhY2goc2VsZWN0PT57XHJcbiAgICAgIHNlbGVjdC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCAoKT0+e1xyXG4gICAgICAgIGNvbnN0IGZvb3RlciA9IHNlbGVjdC5jbG9zZXN0KCcubWVudS1jYXJkLWZvb3RlcicpO1xyXG4gICAgICAgIGNvbnN0IHByaWNlRWwgPSBmb290ZXIgPyBmb290ZXIucXVlcnlTZWxlY3RvcignLmpzLWNvbnZlcnRpYmxlLXByaWNlJykgOiBudWxsO1xyXG4gICAgICAgIGlmKCFwcmljZUVsKXtcclxuICAgICAgICAgIHJldHVybjtcclxuICAgICAgICB9XHJcblxyXG4gICAgICAgIGNvbnN0IGJhc2VQcmljZSA9IE51bWJlcihwcmljZUVsLmRhdGFzZXQuYmFzZVByaWNlIHx8IHNlbGVjdC5kYXRhc2V0LmJhc2VQcmljZSB8fCAwKTtcclxuICAgICAgICBjb25zdCBjdXJyZW5jeSA9IE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChjdXJyZW5jeVJhdGVzLCBzZWxlY3QudmFsdWUpID8gc2VsZWN0LnZhbHVlIDogJ1RORCc7XHJcbiAgICAgICAgY29uc3QgY29udmVydGVkID0gYmFzZVByaWNlICogY3VycmVuY3lSYXRlc1tjdXJyZW5jeV07XHJcbiAgICAgICAgcHJpY2VFbC50ZXh0Q29udGVudCA9IGAke2NvbnZlcnRlZC50b0ZpeGVkKDIpfSAke2N1cnJlbmN5fWA7XHJcbiAgICAgIH0pO1xyXG4gICAgfSk7XHJcblxyXG4gICAgLy8gQk9PS0lORyBQT1BVUFxyXG4gICAgY29uc3QgYm9va2luZ1BvcHVwID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Jvb2tpbmdQb3B1cCcpO1xyXG4gICAgY29uc3Qgb3BlbkJvb2tpbmcgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3BlbkJvb2tpbmcnKTtcclxuICAgIGNvbnN0IG9wZW5Cb29raW5nMiA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcGVuQm9va2luZzInKTtcclxuICAgIGNvbnN0IGNsb3NlQm9va2luZyA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjbG9zZUJvb2tpbmcnKTtcclxuICAgIGNvbnN0IGJvb2tpbmdGb3JtID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Jvb2tpbmdGb3JtJyk7XHJcblxyXG4gICAgZnVuY3Rpb24gc2hvd0Jvb2tpbmcoKXtcclxuICAgICAgaWYoIWJvb2tpbmdQb3B1cCl7XHJcbiAgICAgICAgcmV0dXJuO1xyXG4gICAgICB9XHJcbiAgICAgIGJvb2tpbmdQb3B1cC5jbGFzc0xpc3QuYWRkKCdhY3RpdmUnKTtcclxuICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICdoaWRkZW4nO1xyXG4gICAgfVxyXG4gICAgZnVuY3Rpb24gaGlkZUJvb2tpbmcoKXtcclxuICAgICAgaWYoIWJvb2tpbmdQb3B1cCl7XHJcbiAgICAgICAgcmV0dXJuO1xyXG4gICAgICB9XHJcbiAgICAgIGJvb2tpbmdQb3B1cC5jbGFzc0xpc3QucmVtb3ZlKCdhY3RpdmUnKTtcclxuICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICcnO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKG9wZW5Cb29raW5nKXtcclxuICAgICAgb3BlbkJvb2tpbmcuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoZSk9PnsgZS5wcmV2ZW50RGVmYXVsdCgpOyBzaG93Qm9va2luZygpOyB9KTtcclxuICAgIH1cclxuICAgIGlmKG9wZW5Cb29raW5nMil7XHJcbiAgICAgIG9wZW5Cb29raW5nMi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChlKT0+eyBlLnByZXZlbnREZWZhdWx0KCk7IHNob3dCb29raW5nKCk7IH0pO1xyXG4gICAgfVxyXG4gICAgaWYoY2xvc2VCb29raW5nKXtcclxuICAgICAgY2xvc2VCb29raW5nLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgaGlkZUJvb2tpbmcpO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKGJvb2tpbmdQb3B1cCl7XHJcbiAgICAgIGJvb2tpbmdQb3B1cC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChlKT0+e1xyXG4gICAgICAgIGlmKGUudGFyZ2V0ID09PSBib29raW5nUG9wdXApIGhpZGVCb29raW5nKCk7XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKGJvb2tpbmdGb3JtKXtcclxuICAgICAgYm9va2luZ0Zvcm0uYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgZnVuY3Rpb24oZSl7XHJcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG4gICAgICAgIGFsZXJ0KFwiWW91ciBib29raW5nIHJlcXVlc3QgaGFzIGJlZW4gc3VibWl0dGVkIHN1Y2Nlc3NmdWxseSFcIik7XHJcbiAgICAgICAgYm9va2luZ0Zvcm0ucmVzZXQoKTtcclxuICAgICAgICBoaWRlQm9va2luZygpO1xyXG4gICAgICB9KTtcclxuICAgIH1cclxuXHJcbiAgICAvLyBQUk9GSUxFIFBPUFVQXHJcbiAgICBjb25zdCBwcm9maWxlUG9wdXAgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncHJvZmlsZVBvcHVwJyk7XHJcbiAgICBjb25zdCBvcGVuUHJvZmlsZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcGVuUHJvZmlsZScpO1xyXG4gICAgY29uc3QgY2xvc2VQcm9maWxlID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Nsb3NlUHJvZmlsZScpO1xyXG4gICAgY29uc3QgcHJvZmlsZUZvcm0gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncHJvZmlsZUZvcm0nKTtcclxuXHJcbiAgICBmdW5jdGlvbiBzaG93UHJvZmlsZSgpe1xyXG4gICAgICBpZighcHJvZmlsZVBvcHVwKXtcclxuICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9ICcvcHJvZmlsZSc7XHJcbiAgICAgICAgcmV0dXJuO1xyXG4gICAgICB9XHJcbiAgICAgIHByb2ZpbGVQb3B1cC5jbGFzc0xpc3QuYWRkKCdhY3RpdmUnKTtcclxuICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICdoaWRkZW4nO1xyXG4gICAgfVxyXG4gICAgZnVuY3Rpb24gaGlkZVByb2ZpbGUoKXtcclxuICAgICAgaWYoIXByb2ZpbGVQb3B1cCl7XHJcbiAgICAgICAgcmV0dXJuO1xyXG4gICAgICB9XHJcbiAgICAgIHByb2ZpbGVQb3B1cC5jbGFzc0xpc3QucmVtb3ZlKCdhY3RpdmUnKTtcclxuICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICcnO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKG9wZW5Qcm9maWxlKXtcclxuICAgICAgb3BlblByb2ZpbGUuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBzaG93UHJvZmlsZSk7XHJcbiAgICB9XHJcbiAgICBpZihjbG9zZVByb2ZpbGUpe1xyXG4gICAgICBjbG9zZVByb2ZpbGUuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBoaWRlUHJvZmlsZSk7XHJcbiAgICB9XHJcblxyXG4gICAgaWYocHJvZmlsZVBvcHVwKXtcclxuICAgICAgcHJvZmlsZVBvcHVwLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGUpPT57XHJcbiAgICAgICAgaWYoZS50YXJnZXQgPT09IHByb2ZpbGVQb3B1cCkgaGlkZVByb2ZpbGUoKTtcclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgaWYocHJvZmlsZUZvcm0pe1xyXG4gICAgICBwcm9maWxlRm9ybS5hZGRFdmVudExpc3RlbmVyKCdzdWJtaXQnLCBhc3luYyBmdW5jdGlvbihlKXtcclxuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcblxyXG4gICAgICAgIGlmKHByb2ZpbGVGb3JtLmRhdGFzZXQuYXV0aGVudGljYXRlZCAhPT0gJzEnKXtcclxuICAgICAgICAgIGFsZXJ0KCdQbGVhc2Ugc2lnbiBpbiBmaXJzdC4nKTtcclxuICAgICAgICAgIHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gJy9sb2dpbic7XHJcbiAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBjb25zdCBzdWJtaXRCdG4gPSBwcm9maWxlRm9ybS5xdWVyeVNlbGVjdG9yKCdidXR0b25bdHlwZT1cInN1Ym1pdFwiXScpO1xyXG4gICAgICAgIGNvbnN0IG9yaWdpbmFsVGV4dCA9IHN1Ym1pdEJ0biA/IHN1Ym1pdEJ0bi50ZXh0Q29udGVudCA6ICdTYXZlIFByb2ZpbGUnO1xyXG5cclxuICAgICAgICB0cnkge1xyXG4gICAgICAgICAgaWYoc3VibWl0QnRuKXtcclxuICAgICAgICAgICAgc3VibWl0QnRuLmRpc2FibGVkID0gdHJ1ZTtcclxuICAgICAgICAgICAgc3VibWl0QnRuLnRleHRDb250ZW50ID0gJ1NhdmluZy4uLic7XHJcbiAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgY29uc3QgcmVzcG9uc2UgPSBhd2FpdCBmZXRjaChwcm9maWxlRm9ybS5hY3Rpb24sIHtcclxuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXHJcbiAgICAgICAgICAgIGJvZHk6IG5ldyBGb3JtRGF0YShwcm9maWxlRm9ybSksXHJcbiAgICAgICAgICAgIGhlYWRlcnM6IHtcclxuICAgICAgICAgICAgICAnWC1SZXF1ZXN0ZWQtV2l0aCc6ICdYTUxIdHRwUmVxdWVzdCcsXHJcbiAgICAgICAgICAgICAgJ0FjY2VwdCc6ICdhcHBsaWNhdGlvbi9qc29uJyxcclxuICAgICAgICAgICAgfSxcclxuICAgICAgICAgIH0pO1xyXG5cclxuICAgICAgICAgIGNvbnN0IHBheWxvYWQgPSBhd2FpdCByZXNwb25zZS5qc29uKCk7XHJcbiAgICAgICAgICBpZighcmVzcG9uc2Uub2sgfHwgIXBheWxvYWQuc3VjY2Vzcyl7XHJcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihwYXlsb2FkLm1lc3NhZ2UgfHwgJ1VuYWJsZSB0byBzYXZlIHByb2ZpbGUuJyk7XHJcbiAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgY29uc3QgZnVsbE5hbWVJbnB1dCA9IHByb2ZpbGVGb3JtLnF1ZXJ5U2VsZWN0b3IoJ2lucHV0W25hbWU9XCJmdWxsX25hbWVcIl0nKTtcclxuICAgICAgICAgIGNvbnN0IHBob25lSW5wdXQgPSBwcm9maWxlRm9ybS5xdWVyeVNlbGVjdG9yKCdpbnB1dFtuYW1lPVwicGhvbmVcIl0nKTtcclxuICAgICAgICAgIGNvbnN0IGFkZHJlc3NJbnB1dCA9IHByb2ZpbGVGb3JtLnF1ZXJ5U2VsZWN0b3IoJ2lucHV0W25hbWU9XCJhZGRyZXNzXCJdJyk7XHJcbiAgICAgICAgICBjb25zdCBlbWFpbElucHV0ID0gcHJvZmlsZUZvcm0ucXVlcnlTZWxlY3RvcignaW5wdXRbbmFtZT1cImVtYWlsXCJdJyk7XHJcblxyXG4gICAgICAgICAgaWYoZnVsbE5hbWVJbnB1dCAmJiBwYXlsb2FkLnByb2ZpbGUgJiYgdHlwZW9mIHBheWxvYWQucHJvZmlsZS5mdWxsTmFtZSA9PT0gJ3N0cmluZycpe1xyXG4gICAgICAgICAgICBmdWxsTmFtZUlucHV0LnZhbHVlID0gcGF5bG9hZC5wcm9maWxlLmZ1bGxOYW1lO1xyXG4gICAgICAgICAgfVxyXG4gICAgICAgICAgaWYocGhvbmVJbnB1dCAmJiBwYXlsb2FkLnByb2ZpbGUgJiYgdHlwZW9mIHBheWxvYWQucHJvZmlsZS5waG9uZSA9PT0gJ3N0cmluZycpe1xyXG4gICAgICAgICAgICBwaG9uZUlucHV0LnZhbHVlID0gcGF5bG9hZC5wcm9maWxlLnBob25lO1xyXG4gICAgICAgICAgfVxyXG4gICAgICAgICAgaWYoYWRkcmVzc0lucHV0ICYmIHBheWxvYWQucHJvZmlsZSAmJiB0eXBlb2YgcGF5bG9hZC5wcm9maWxlLmFkZHJlc3MgPT09ICdzdHJpbmcnKXtcclxuICAgICAgICAgICAgYWRkcmVzc0lucHV0LnZhbHVlID0gcGF5bG9hZC5wcm9maWxlLmFkZHJlc3M7XHJcbiAgICAgICAgICB9XHJcbiAgICAgICAgICBpZihlbWFpbElucHV0ICYmIHBheWxvYWQucHJvZmlsZSAmJiB0eXBlb2YgcGF5bG9hZC5wcm9maWxlLmVtYWlsID09PSAnc3RyaW5nJyl7XHJcbiAgICAgICAgICAgIGVtYWlsSW5wdXQudmFsdWUgPSBwYXlsb2FkLnByb2ZpbGUuZW1haWw7XHJcbiAgICAgICAgICB9XHJcblxyXG4gICAgICAgICAgYWxlcnQocGF5bG9hZC5tZXNzYWdlIHx8ICdZb3VyIHByb2ZpbGUgaGFzIGJlZW4gc2F2ZWQgc3VjY2Vzc2Z1bGx5LicpO1xyXG4gICAgICAgICAgaGlkZVByb2ZpbGUoKTtcclxuICAgICAgICB9IGNhdGNoKGVycil7XHJcbiAgICAgICAgICBhbGVydChlcnIgaW5zdGFuY2VvZiBFcnJvciA/IGVyci5tZXNzYWdlIDogJ1VuYWJsZSB0byBzYXZlIHByb2ZpbGUuJyk7XHJcbiAgICAgICAgfSBmaW5hbGx5IHtcclxuICAgICAgICAgIGlmKHN1Ym1pdEJ0bil7XHJcbiAgICAgICAgICAgIHN1Ym1pdEJ0bi5kaXNhYmxlZCA9IGZhbHNlO1xyXG4gICAgICAgICAgICBzdWJtaXRCdG4udGV4dENvbnRlbnQgPSBvcmlnaW5hbFRleHQ7XHJcbiAgICAgICAgICB9XHJcbiAgICAgICAgfVxyXG4gICAgICB9KTtcclxuICAgIH1cclxuXHJcbiAgICAvLyBDQVJUIFNZU1RFTVxyXG4gICAgY29uc3QgY2FydE92ZXJsYXkgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FydE92ZXJsYXknKTtcclxuICAgIGNvbnN0IG9wZW5DYXJ0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29wZW5DYXJ0Jyk7XHJcbiAgICBjb25zdCBvcGVuQ2FydDIgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3BlbkNhcnQyJyk7XHJcbiAgICBjb25zdCBjbG9zZUNhcnQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2xvc2VDYXJ0Jyk7XHJcbiAgICBjb25zdCBjYXJ0SXRlbXNDb250YWluZXIgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FydEl0ZW1zJyk7XHJcbiAgICBjb25zdCBjYXJ0VG90YWwgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FydFRvdGFsJyk7XHJcbiAgICBjb25zdCBjYXJ0Q291bnQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FydENvdW50Jyk7XHJcbiAgICBjb25zdCBjaGVja291dEJ0biA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjaGVja291dEJ0bicpO1xyXG5cclxuICAgIGxldCBjYXJ0ID0gW107XHJcblxyXG4gICAgZnVuY3Rpb24gc2hvd0NhcnQoKXtcclxuICAgICAgaWYoIWNhcnRPdmVybGF5KXtcclxuICAgICAgICByZXR1cm47XHJcbiAgICAgIH1cclxuICAgICAgY2FydE92ZXJsYXkuY2xhc3NMaXN0LmFkZCgnYWN0aXZlJyk7XHJcbiAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnaGlkZGVuJztcclxuICAgIH1cclxuICAgIGZ1bmN0aW9uIGhpZGVDYXJ0KCl7XHJcbiAgICAgIGlmKCFjYXJ0T3ZlcmxheSl7XHJcbiAgICAgICAgcmV0dXJuO1xyXG4gICAgICB9XHJcbiAgICAgIGNhcnRPdmVybGF5LmNsYXNzTGlzdC5yZW1vdmUoJ2FjdGl2ZScpO1xyXG4gICAgICBkb2N1bWVudC5ib2R5LnN0eWxlLm92ZXJmbG93ID0gJyc7XHJcbiAgICB9XHJcblxyXG4gICAgaWYob3BlbkNhcnQpe1xyXG4gICAgICBvcGVuQ2FydC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHNob3dDYXJ0KTtcclxuICAgIH1cclxuICAgIGlmKG9wZW5DYXJ0Mil7XHJcbiAgICAgIG9wZW5DYXJ0Mi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHNob3dDYXJ0KTtcclxuICAgIH1cclxuICAgIGlmKGNsb3NlQ2FydCl7XHJcbiAgICAgIGNsb3NlQ2FydC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGhpZGVDYXJ0KTtcclxuICAgIH1cclxuXHJcbiAgICBpZihjYXJ0T3ZlcmxheSl7XHJcbiAgICAgIGNhcnRPdmVybGF5LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGUpPT57XHJcbiAgICAgICAgaWYoZS50YXJnZXQgPT09IGNhcnRPdmVybGF5KSBoaWRlQ2FydCgpO1xyXG4gICAgICB9KTtcclxuICAgIH1cclxuXHJcbiAgICAvLyBFeGlzdGluZyBkaXJlY3QgYmluZGluZ3MgKGZvciBidXR0b25zIHByZXNlbnQgYXQgbG9hZClcclxuICAgIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5hZGQtY2FydC1idG4nKS5mb3JFYWNoKGJ0bj0+e1xyXG4gICAgICBidG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKT0+e1xyXG4gICAgICAgIGNvbnN0IG5hbWUgPSBidG4uZGF0YXNldC5uYW1lO1xyXG4gICAgICAgIGNvbnN0IHByaWNlID0gTnVtYmVyKGJ0bi5kYXRhc2V0LnByaWNlKTtcclxuXHJcbiAgICAgICAgY2FydC5wdXNoKHtuYW1lLCBwcmljZX0pO1xyXG4gICAgICAgIHVwZGF0ZUNhcnQoKTtcclxuXHJcbiAgICAgICAgYnRuLnRleHRDb250ZW50ID0gXCJBZGRlZFwiO1xyXG4gICAgICAgIHNldFRpbWVvdXQoKCk9PiBidG4udGV4dENvbnRlbnQgPSBcIkFkZCB0byBDYXJ0XCIsIDEyMDApO1xyXG4gICAgICB9KTtcclxuICAgIH0pO1xyXG5cclxuICAgIC8vIEV2ZW50IGRlbGVnYXRpb246IGVuc3VyZSBkeW5hbWljYWxseSBpbnNlcnRlZCBvciBsYXRlci11cGRhdGVkIGJ1dHRvbnMgYWxzbyB3b3JrXHJcbiAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcclxuICAgICAgY29uc3QgYnRuID0gZS50YXJnZXQuY2xvc2VzdCAmJiBlLnRhcmdldC5jbG9zZXN0KCcuYWRkLWNhcnQtYnRuJyk7XHJcbiAgICAgIGlmICghYnRuKSByZXR1cm47XHJcbiAgICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgICAgY29uc3QgbmFtZSA9IGJ0bi5kYXRhc2V0Lm5hbWU7XHJcbiAgICAgIGNvbnN0IHByaWNlID0gTnVtYmVyKGJ0bi5kYXRhc2V0LnByaWNlKTtcclxuICAgICAgY2FydC5wdXNoKHtuYW1lLCBwcmljZX0pO1xyXG4gICAgICB1cGRhdGVDYXJ0KCk7XHJcbiAgICAgIGNvbnN0IG9yaWdpbmFsID0gYnRuLnRleHRDb250ZW50O1xyXG4gICAgICBidG4udGV4dENvbnRlbnQgPSAnQWRkZWQnO1xyXG4gICAgICBzZXRUaW1lb3V0KCgpPT4gYnRuLnRleHRDb250ZW50ID0gb3JpZ2luYWwgfHwgJ0FkZCB0byBDYXJ0JywgMTIwMCk7XHJcbiAgICB9KTtcclxuXHJcbiAgICBmdW5jdGlvbiB1cGRhdGVDYXJ0KCl7XHJcbiAgICAgIGlmKCFjYXJ0SXRlbXNDb250YWluZXIgfHwgIWNhcnRUb3RhbCB8fCAhY2FydENvdW50KXtcclxuICAgICAgICByZXR1cm47XHJcbiAgICAgIH1cclxuICAgICAgY2FydEl0ZW1zQ29udGFpbmVyLmlubmVySFRNTCA9ICcnO1xyXG5cclxuICAgICAgaWYoY2FydC5sZW5ndGggPT09IDApe1xyXG4gICAgICAgIGNhcnRJdGVtc0NvbnRhaW5lci5pbm5lckhUTUwgPSBgPGRpdiBjbGFzcz1cImVtcHR5LWNhcnRcIj5Zb3VyIGx1eHVyeSBjYXJ0IGlzIGN1cnJlbnRseSBlbXB0eS48L2Rpdj5gO1xyXG4gICAgICB9IGVsc2Uge1xyXG4gICAgICAgIGNhcnQuZm9yRWFjaCgoaXRlbSwgaW5kZXgpPT57XHJcbiAgICAgICAgICBjb25zdCBjYXJ0SXRlbSA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xyXG4gICAgICAgICAgY2FydEl0ZW0uY2xhc3NMaXN0LmFkZCgnY2FydC1pdGVtJyk7XHJcbiAgICAgICAgICBjYXJ0SXRlbS5pbm5lckhUTUwgPSBgXHJcbiAgICAgICAgICAgIDxkaXY+XHJcbiAgICAgICAgICAgICAgPGg0PiR7aXRlbS5uYW1lfTwvaDQ+XHJcbiAgICAgICAgICAgICAgPHA+JHtpdGVtLnByaWNlfSBUTkQ8L3A+XHJcbiAgICAgICAgICAgIDwvZGl2PlxyXG4gICAgICAgICAgICA8YnV0dG9uIGNsYXNzPVwicmVtb3ZlLWJ0blwiIG9uY2xpY2s9XCJyZW1vdmVGcm9tQ2FydCgke2luZGV4fSlcIj5SZW1vdmU8L2J1dHRvbj5cclxuICAgICAgICAgIGA7XHJcbiAgICAgICAgICBjYXJ0SXRlbXNDb250YWluZXIuYXBwZW5kQ2hpbGQoY2FydEl0ZW0pO1xyXG4gICAgICAgIH0pO1xyXG4gICAgICB9XHJcblxyXG4gICAgICBjb25zdCB0b3RhbCA9IGNhcnQucmVkdWNlKChzdW0sIGl0ZW0pPT4gc3VtICsgaXRlbS5wcmljZSwgMCk7XHJcbiAgICAgIGNhcnRUb3RhbC50ZXh0Q29udGVudCA9IGAke3RvdGFsfSBUTkRgO1xyXG4gICAgICBjYXJ0Q291bnQudGV4dENvbnRlbnQgPSBjYXJ0Lmxlbmd0aDtcclxuICAgIH1cclxuXHJcbiAgICBmdW5jdGlvbiBzaG93RnJvbnRGbGFzaChtZXNzYWdlLCBpc0Vycm9yID0gZmFsc2Upe1xyXG4gICAgICBjb25zdCBleGlzdGluZyA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdmcm9udEZsYXNoVG9hc3QnKTtcclxuICAgICAgaWYgKGV4aXN0aW5nKSB7XHJcbiAgICAgICAgZXhpc3RpbmcucmVtb3ZlKCk7XHJcbiAgICAgIH1cclxuXHJcbiAgICAgIGNvbnN0IHRvYXN0ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnZGl2Jyk7XHJcbiAgICAgIHRvYXN0LmlkID0gJ2Zyb250Rmxhc2hUb2FzdCc7XHJcbiAgICAgIHRvYXN0LnN0eWxlLmNzc1RleHQgPSAncG9zaXRpb246Zml4ZWQ7dG9wOjkycHg7bGVmdDo1MCU7dHJhbnNmb3JtOnRyYW5zbGF0ZVgoLTUwJSk7ei1pbmRleDo5OTk5O3BhZGRpbmc6MXJlbSAxLjZyZW07Ym9yZGVyLXJhZGl1czo5OTlweDtmb250LXNpemU6LjkycmVtO2ZvbnQtd2VpZ2h0OjYwMDtjb2xvcjojZmZmO2JveC1zaGFkb3c6MCAxMHB4IDMwcHggcmdiYSg0NCwyNiwxNCwuMjUpO21heC13aWR0aDo5MHZ3O3RleHQtYWxpZ246Y2VudGVyOyc7XHJcbiAgICAgIHRvYXN0LnN0eWxlLmJhY2tncm91bmQgPSBpc0Vycm9yXHJcbiAgICAgICAgPyAnbGluZWFyLWdyYWRpZW50KDEzNWRlZywjRDk0MDQwLCNhODJhMmEpJ1xyXG4gICAgICAgIDogJ2xpbmVhci1ncmFkaWVudCgxMzVkZWcsIzJFOUU2QSwjMWU3YTUyKSc7XHJcbiAgICAgIHRvYXN0LnRleHRDb250ZW50ID0gYCR7aXNFcnJvciA/ICfinJUnIDogJ+Kckyd9ICR7bWVzc2FnZX1gO1xyXG4gICAgICBkb2N1bWVudC5ib2R5LmFwcGVuZENoaWxkKHRvYXN0KTtcclxuXHJcbiAgICAgIHdpbmRvdy5zZXRUaW1lb3V0KCgpID0+IHtcclxuICAgICAgICB0b2FzdC5yZW1vdmUoKTtcclxuICAgICAgfSwgMzUwMCk7XHJcbiAgICB9XHJcblxyXG4gICAgaWYoY2hlY2tvdXRCdG4pe1xyXG4gICAgICBjaGVja291dEJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChlKT0+e1xyXG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xyXG4gICAgICAgIFxyXG4gICAgICAgIGlmKGNhcnQubGVuZ3RoID09PSAwKXtcclxuICAgICAgICAgIHdpbmRvdy5sb2NhdGlvbi5ocmVmID0gJy9vcmRlcnMvY3JlYXRlLWZyb20tY2FydD92YWxpZGF0aW9uX29ubHk9MSc7XHJcbiAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBjb25zdCB0b3RhbCA9IGNhcnQucmVkdWNlKChzdW0sIGl0ZW0pPT4gc3VtICsgaXRlbS5wcmljZSwgMCk7XHJcbiAgICAgICAgY29uc3QgY2FydEl0ZW1zSW5wdXQgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncmVkaXJlY3RDYXJ0SXRlbXNJbnB1dCcpO1xyXG4gICAgICAgIGNvbnN0IGNhcnRUb3RhbElucHV0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ3JlZGlyZWN0Q2FydFRvdGFsSW5wdXQnKTtcclxuICAgICAgICBpZihjYXJ0SXRlbXNJbnB1dCAmJiBjYXJ0VG90YWxJbnB1dCl7XHJcbiAgICAgICAgICBjYXJ0SXRlbXNJbnB1dC52YWx1ZSA9IEpTT04uc3RyaW5naWZ5KGNhcnQpO1xyXG4gICAgICAgICAgY2FydFRvdGFsSW5wdXQudmFsdWUgPSB0b3RhbC50b0ZpeGVkKDIpO1xyXG4gICAgICAgIH1cclxuICAgICAgICBcclxuICAgICAgICAvLyBIaWRlIHRoZSBjYXJ0IG92ZXJsYXlcclxuICAgICAgICBjb25zdCBjYXJ0T3ZlcmxheSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjYXJ0T3ZlcmxheScpO1xyXG4gICAgICAgIGlmKGNhcnRPdmVybGF5KXtcclxuICAgICAgICAgIGNhcnRPdmVybGF5LmNsYXNzTGlzdC5yZW1vdmUoJ2FjdGl2ZScpO1xyXG4gICAgICAgIH1cclxuICAgICAgICBcclxuICAgICAgICAvLyBTaG93IG9yZGVyIHR5cGUgc2VsZWN0aW9uIG1vZGFsXHJcbiAgICAgICAgY29uc3QgbW9kYWwgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3JkZXJUeXBlTW9kYWwnKTtcclxuICAgICAgICBpZihtb2RhbCl7XHJcbiAgICAgICAgICBtb2RhbC5zdHlsZS5kaXNwbGF5ID0gJ2ZsZXgnO1xyXG4gICAgICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICdoaWRkZW4nO1xyXG4gICAgICAgIH1cclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgZnVuY3Rpb24gcmVtb3ZlRnJvbUNhcnQoaW5kZXgpe1xyXG4gICAgICBjYXJ0LnNwbGljZShpbmRleCwxKTtcclxuICAgICAgdXBkYXRlQ2FydCgpO1xyXG4gICAgfVxyXG5cclxuICAgIHdpbmRvdy5yZW1vdmVGcm9tQ2FydCA9IHJlbW92ZUZyb21DYXJ0O1xyXG5cclxuICAgIC8vIE9SREVSIFRZUEUgTU9EQUxcclxuICAgIGNvbnN0IG9yZGVyVHlwZU1vZGFsID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yZGVyVHlwZU1vZGFsJyk7XHJcbiAgICBjb25zdCBkaW5lSW5CdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZGluZUluQnRuJyk7XHJcbiAgICBjb25zdCBkZWxpdmVyeUJ0biA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdkZWxpdmVyeUJ0bicpO1xyXG4gICAgY29uc3QgY2xvc2VPcmRlclR5cGVCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2xvc2VPcmRlclR5cGUnKTtcclxuICAgIGNvbnN0IG9yZGVyVHlwZUlucHV0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yZGVyVHlwZUlucHV0Jyk7XHJcbiAgICBjb25zdCBjaGVja291dFJlZGlyZWN0Rm9ybSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjaGVja291dFJlZGlyZWN0Rm9ybScpO1xyXG5cclxuICAgIGZ1bmN0aW9uIGNsb3NlT3JkZXJUeXBlTW9kYWwoKXtcclxuICAgICAgaWYob3JkZXJUeXBlTW9kYWwpe1xyXG4gICAgICAgIG9yZGVyVHlwZU1vZGFsLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XHJcbiAgICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICcnO1xyXG4gICAgICB9XHJcbiAgICB9XHJcblxyXG4gICAgaWYoY2xvc2VPcmRlclR5cGVCdG4pe1xyXG4gICAgICBjbG9zZU9yZGVyVHlwZUJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGNsb3NlT3JkZXJUeXBlTW9kYWwpO1xyXG4gICAgfVxyXG4gICAgXHJcbiAgICBpZihvcmRlclR5cGVNb2RhbCl7XHJcbiAgICAgIG9yZGVyVHlwZU1vZGFsLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGUpPT57XHJcbiAgICAgICAgLy8gT25seSBjbG9zZSBpZiBjbGlja2luZyB0aGUgYmFja2dyb3VuZCBvdmVybGF5LCBub3QgdGhlIGlubmVyIGNvbnRlbnRcclxuICAgICAgICBpZihlLnRhcmdldCA9PT0gb3JkZXJUeXBlTW9kYWwpIGNsb3NlT3JkZXJUeXBlTW9kYWwoKTtcclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgaWYoZGluZUluQnRuKXtcclxuICAgICAgZGluZUluQnRuLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKCk9PntcclxuICAgICAgICBpZiAob3JkZXJUeXBlSW5wdXQpIHtcclxuICAgICAgICAgIG9yZGVyVHlwZUlucHV0LnZhbHVlID0gJ0RJTkVfSU4nO1xyXG4gICAgICAgIH1cclxuICAgICAgICBpZiAoY2hlY2tvdXRSZWRpcmVjdEZvcm0pIHtcclxuICAgICAgICAgIGNoZWNrb3V0UmVkaXJlY3RGb3JtLnN1Ym1pdCgpO1xyXG4gICAgICAgIH1cclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgaWYoZGVsaXZlcnlCdG4pe1xyXG4gICAgICBkZWxpdmVyeUJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpPT57XHJcbiAgICAgICAgaWYgKG9yZGVyVHlwZUlucHV0KSB7XHJcbiAgICAgICAgICBvcmRlclR5cGVJbnB1dC52YWx1ZSA9ICdERUxJVkVSWSc7XHJcbiAgICAgICAgfVxyXG4gICAgICAgIGlmIChjaGVja291dFJlZGlyZWN0Rm9ybSkge1xyXG4gICAgICAgICAgY2hlY2tvdXRSZWRpcmVjdEZvcm0uc3VibWl0KCk7XHJcbiAgICAgICAgfVxyXG4gICAgICB9KTtcclxuICAgIH1cclxuXHJcbiAgICAvLyBFU0MgQ0xPU0VcclxuICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2tleWRvd24nLCAoZSk9PntcclxuICAgICAgaWYoZS5rZXkgPT09IFwiRXNjYXBlXCIpe1xyXG4gICAgICAgIGhpZGVCb29raW5nKCk7XHJcbiAgICAgICAgaGlkZUNhcnQoKTtcclxuICAgICAgICBoaWRlUHJvZmlsZSgpO1xyXG4gICAgICAgIGNsb3NlT3JkZXJUeXBlTW9kYWwoKTtcclxuICAgICAgfVxyXG4gICAgfSk7XHJcbn0pO1xyXG5cclxuIiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307Il0sIm5hbWVzIjpbImUiLCJ0IiwiciIsIlN5bWJvbCIsIm4iLCJpdGVyYXRvciIsIm8iLCJ0b1N0cmluZ1RhZyIsImkiLCJjIiwicHJvdG90eXBlIiwiR2VuZXJhdG9yIiwidSIsIk9iamVjdCIsImNyZWF0ZSIsIl9yZWdlbmVyYXRvckRlZmluZTIiLCJmIiwicCIsInkiLCJHIiwidiIsImEiLCJkIiwiYmluZCIsImxlbmd0aCIsImwiLCJUeXBlRXJyb3IiLCJjYWxsIiwiZG9uZSIsInZhbHVlIiwicmV0dXJuIiwiR2VuZXJhdG9yRnVuY3Rpb24iLCJHZW5lcmF0b3JGdW5jdGlvblByb3RvdHlwZSIsImdldFByb3RvdHlwZU9mIiwic2V0UHJvdG90eXBlT2YiLCJfX3Byb3RvX18iLCJkaXNwbGF5TmFtZSIsIl9yZWdlbmVyYXRvciIsInciLCJtIiwiZGVmaW5lUHJvcGVydHkiLCJfcmVnZW5lcmF0b3JEZWZpbmUiLCJfaW52b2tlIiwiZW51bWVyYWJsZSIsImNvbmZpZ3VyYWJsZSIsIndyaXRhYmxlIiwiYXN5bmNHZW5lcmF0b3JTdGVwIiwiUHJvbWlzZSIsInJlc29sdmUiLCJ0aGVuIiwiX2FzeW5jVG9HZW5lcmF0b3IiLCJhcmd1bWVudHMiLCJhcHBseSIsIl9uZXh0IiwiX3Rocm93IiwiZG9jdW1lbnQiLCJhZGRFdmVudExpc3RlbmVyIiwibmF2IiwiZ2V0RWxlbWVudEJ5SWQiLCJ3aW5kb3ciLCJjbGFzc0xpc3QiLCJ0b2dnbGUiLCJzY3JvbGxZIiwib2JzZXJ2ZXIiLCJJbnRlcnNlY3Rpb25PYnNlcnZlciIsImVudHJpZXMiLCJmb3JFYWNoIiwiZW50cnkiLCJpc0ludGVyc2VjdGluZyIsInRhcmdldCIsImFkZCIsInVub2JzZXJ2ZSIsInRocmVzaG9sZCIsInJldmVhbEVscyIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJlbCIsIm9ic2VydmUiLCJjdXJyZW5jeVJhdGVzIiwiVE5EIiwiVVNEIiwiRVVSIiwiQ05ZIiwic2VsZWN0IiwiZm9vdGVyIiwiY2xvc2VzdCIsInByaWNlRWwiLCJxdWVyeVNlbGVjdG9yIiwiYmFzZVByaWNlIiwiTnVtYmVyIiwiZGF0YXNldCIsImN1cnJlbmN5IiwiaGFzT3duUHJvcGVydHkiLCJjb252ZXJ0ZWQiLCJ0ZXh0Q29udGVudCIsImNvbmNhdCIsInRvRml4ZWQiLCJib29raW5nUG9wdXAiLCJvcGVuQm9va2luZyIsIm9wZW5Cb29raW5nMiIsImNsb3NlQm9va2luZyIsImJvb2tpbmdGb3JtIiwic2hvd0Jvb2tpbmciLCJib2R5Iiwic3R5bGUiLCJvdmVyZmxvdyIsImhpZGVCb29raW5nIiwicmVtb3ZlIiwicHJldmVudERlZmF1bHQiLCJhbGVydCIsInJlc2V0IiwicHJvZmlsZVBvcHVwIiwib3BlblByb2ZpbGUiLCJjbG9zZVByb2ZpbGUiLCJwcm9maWxlRm9ybSIsInNob3dQcm9maWxlIiwibG9jYXRpb24iLCJocmVmIiwiaGlkZVByb2ZpbGUiLCJfcmVmIiwiX2NhbGxlZSIsInN1Ym1pdEJ0biIsIm9yaWdpbmFsVGV4dCIsInJlc3BvbnNlIiwicGF5bG9hZCIsImZ1bGxOYW1lSW5wdXQiLCJwaG9uZUlucHV0IiwiYWRkcmVzc0lucHV0IiwiZW1haWxJbnB1dCIsIl90IiwiX2NvbnRleHQiLCJhdXRoZW50aWNhdGVkIiwiZGlzYWJsZWQiLCJmZXRjaCIsImFjdGlvbiIsIm1ldGhvZCIsIkZvcm1EYXRhIiwiaGVhZGVycyIsImpzb24iLCJvayIsInN1Y2Nlc3MiLCJFcnJvciIsIm1lc3NhZ2UiLCJwcm9maWxlIiwiZnVsbE5hbWUiLCJwaG9uZSIsImFkZHJlc3MiLCJlbWFpbCIsIl94IiwiY2FydE92ZXJsYXkiLCJvcGVuQ2FydCIsIm9wZW5DYXJ0MiIsImNsb3NlQ2FydCIsImNhcnRJdGVtc0NvbnRhaW5lciIsImNhcnRUb3RhbCIsImNhcnRDb3VudCIsImNoZWNrb3V0QnRuIiwiY2FydCIsInNob3dDYXJ0IiwiaGlkZUNhcnQiLCJidG4iLCJuYW1lIiwicHJpY2UiLCJwdXNoIiwidXBkYXRlQ2FydCIsInNldFRpbWVvdXQiLCJvcmlnaW5hbCIsImlubmVySFRNTCIsIml0ZW0iLCJpbmRleCIsImNhcnRJdGVtIiwiY3JlYXRlRWxlbWVudCIsImFwcGVuZENoaWxkIiwidG90YWwiLCJyZWR1Y2UiLCJzdW0iLCJzaG93RnJvbnRGbGFzaCIsImlzRXJyb3IiLCJ1bmRlZmluZWQiLCJleGlzdGluZyIsInRvYXN0IiwiaWQiLCJjc3NUZXh0IiwiYmFja2dyb3VuZCIsInN0b3BQcm9wYWdhdGlvbiIsImNhcnRJdGVtc0lucHV0IiwiY2FydFRvdGFsSW5wdXQiLCJKU09OIiwic3RyaW5naWZ5IiwibW9kYWwiLCJkaXNwbGF5IiwicmVtb3ZlRnJvbUNhcnQiLCJzcGxpY2UiLCJvcmRlclR5cGVNb2RhbCIsImRpbmVJbkJ0biIsImRlbGl2ZXJ5QnRuIiwiY2xvc2VPcmRlclR5cGVCdG4iLCJvcmRlclR5cGVJbnB1dCIsImNoZWNrb3V0UmVkaXJlY3RGb3JtIiwiY2xvc2VPcmRlclR5cGVNb2RhbCIsInN1Ym1pdCIsImtleSJdLCJzb3VyY2VSb290IjoiIn0=