(self["webpackChunkbig4_frontend"] = self["webpackChunkbig4_frontend"] || []).push([["app"],{

/***/ "./assets/app.js":
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
/***/ (function() {

function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i.return) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
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
  function pulseAddToCartButton(btn) {
    if (!btn) {
      return;
    }
    var defaultLabel = btn.dataset.defaultLabel || btn.textContent.trim() || 'Add to Cart';
    btn.dataset.defaultLabel = defaultLabel;
    btn.textContent = 'Added';
    window.clearTimeout(Number(btn.dataset.resetTimer || 0));
    var timer = window.setTimeout(function () {
      btn.textContent = defaultLabel;
      delete btn.dataset.resetTimer;
    }, 900);
    btn.dataset.resetTimer = String(timer);
  }
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.add-cart-btn');
    if (!btn) return;
    e.preventDefault();
    var name = btn.dataset.name;
    var price = Number(btn.dataset.price);
    if (!name || Number.isNaN(price)) {
      return;
    }
    cart.push({
      name: name,
      price: price
    });
    updateCart();
    pulseAddToCartButton(btn);
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

/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ var __webpack_exec__ = function(moduleId) { return __webpack_require__(__webpack_require__.s = moduleId); }
/******/ var __webpack_exports__ = (__webpack_exec__("./assets/app.js"));
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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Li9hc3NldHMvYXBwLmpzIl0sInNvdXJjZXNDb250ZW50IjpbIlxyXG5kb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XHJcbiAgICAvLyBOQVYgU0NST0xMXHJcbiAgICBjb25zdCBuYXYgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnbmF2Jyk7XHJcbiAgICBpZiAobmF2KSB7XHJcbiAgICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdzY3JvbGwnLCgpPT57XHJcbiAgICAgICAgbmF2LmNsYXNzTGlzdC50b2dnbGUoJ3Njcm9sbGVkJywgd2luZG93LnNjcm9sbFkgPiA0MCk7XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIC8vIFNNT09USCBSRVZFQUxcclxuICAgIGNvbnN0IG9ic2VydmVyID0gbmV3IEludGVyc2VjdGlvbk9ic2VydmVyKChlbnRyaWVzKT0+e1xyXG4gICAgICBlbnRyaWVzLmZvckVhY2goKGVudHJ5KT0+e1xyXG4gICAgICAgIGlmKGVudHJ5LmlzSW50ZXJzZWN0aW5nKXtcclxuICAgICAgICAgIGVudHJ5LnRhcmdldC5jbGFzc0xpc3QuYWRkKCdzaG93Jyk7XHJcbiAgICAgICAgICBvYnNlcnZlci51bm9ic2VydmUoZW50cnkudGFyZ2V0KTtcclxuICAgICAgICB9XHJcbiAgICAgIH0pO1xyXG4gICAgfSx7dGhyZXNob2xkOjAuMTV9KTtcclxuXHJcbiAgICBjb25zdCByZXZlYWxFbHMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcucmV2ZWFsLCAucmV2ZWFsLWxlZnQsIC5yZXZlYWwtcmlnaHQnKTtcclxuICAgIGlmIChyZXZlYWxFbHMubGVuZ3RoKSB7XHJcbiAgICAgIHJldmVhbEVscy5mb3JFYWNoKGVsPT57XHJcbiAgICAgICAgb2JzZXJ2ZXIub2JzZXJ2ZShlbCk7XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIC8vIENVUlJFTkNZIENPTlZFUlNJT04gKGZpeGVkIHJhdGVzIGZyb20gMSBUTkQpXHJcbiAgICBjb25zdCBjdXJyZW5jeVJhdGVzID0geyBUTkQ6IDEsIFVTRDogMC4zMiwgRVVSOiAwLjMwLCBDTlk6IDIuMzEgfTtcclxuICAgIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5jdXJyZW5jeS1zZWxlY3QnKS5mb3JFYWNoKHNlbGVjdD0+e1xyXG4gICAgICBzZWxlY3QuYWRkRXZlbnRMaXN0ZW5lcignY2hhbmdlJywgKCk9PntcclxuICAgICAgICBjb25zdCBmb290ZXIgPSBzZWxlY3QuY2xvc2VzdCgnLm1lbnUtY2FyZC1mb290ZXInKTtcclxuICAgICAgICBjb25zdCBwcmljZUVsID0gZm9vdGVyID8gZm9vdGVyLnF1ZXJ5U2VsZWN0b3IoJy5qcy1jb252ZXJ0aWJsZS1wcmljZScpIDogbnVsbDtcclxuICAgICAgICBpZighcHJpY2VFbCl7XHJcbiAgICAgICAgICByZXR1cm47XHJcbiAgICAgICAgfVxyXG5cclxuICAgICAgICBjb25zdCBiYXNlUHJpY2UgPSBOdW1iZXIocHJpY2VFbC5kYXRhc2V0LmJhc2VQcmljZSB8fCBzZWxlY3QuZGF0YXNldC5iYXNlUHJpY2UgfHwgMCk7XHJcbiAgICAgICAgY29uc3QgY3VycmVuY3kgPSBPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwoY3VycmVuY3lSYXRlcywgc2VsZWN0LnZhbHVlKSA/IHNlbGVjdC52YWx1ZSA6ICdUTkQnO1xyXG4gICAgICAgIGNvbnN0IGNvbnZlcnRlZCA9IGJhc2VQcmljZSAqIGN1cnJlbmN5UmF0ZXNbY3VycmVuY3ldO1xyXG4gICAgICAgIHByaWNlRWwudGV4dENvbnRlbnQgPSBgJHtjb252ZXJ0ZWQudG9GaXhlZCgyKX0gJHtjdXJyZW5jeX1gO1xyXG4gICAgICB9KTtcclxuICAgIH0pO1xyXG5cclxuICAgIC8vIEJPT0tJTkcgUE9QVVBcclxuICAgIGNvbnN0IGJvb2tpbmdQb3B1cCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdib29raW5nUG9wdXAnKTtcclxuICAgIGNvbnN0IG9wZW5Cb29raW5nID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29wZW5Cb29raW5nJyk7XHJcbiAgICBjb25zdCBvcGVuQm9va2luZzIgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3BlbkJvb2tpbmcyJyk7XHJcbiAgICBjb25zdCBjbG9zZUJvb2tpbmcgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2xvc2VCb29raW5nJyk7XHJcbiAgICBjb25zdCBib29raW5nRm9ybSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdib29raW5nRm9ybScpO1xyXG5cclxuICAgIGZ1bmN0aW9uIHNob3dCb29raW5nKCl7XHJcbiAgICAgIGlmKCFib29raW5nUG9wdXApe1xyXG4gICAgICAgIHJldHVybjtcclxuICAgICAgfVxyXG4gICAgICBib29raW5nUG9wdXAuY2xhc3NMaXN0LmFkZCgnYWN0aXZlJyk7XHJcbiAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnaGlkZGVuJztcclxuICAgIH1cclxuICAgIGZ1bmN0aW9uIGhpZGVCb29raW5nKCl7XHJcbiAgICAgIGlmKCFib29raW5nUG9wdXApe1xyXG4gICAgICAgIHJldHVybjtcclxuICAgICAgfVxyXG4gICAgICBib29raW5nUG9wdXAuY2xhc3NMaXN0LnJlbW92ZSgnYWN0aXZlJyk7XHJcbiAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnJztcclxuICAgIH1cclxuXHJcbiAgICBpZihvcGVuQm9va2luZyl7XHJcbiAgICAgIG9wZW5Cb29raW5nLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGUpPT57IGUucHJldmVudERlZmF1bHQoKTsgc2hvd0Jvb2tpbmcoKTsgfSk7XHJcbiAgICB9XHJcbiAgICBpZihvcGVuQm9va2luZzIpe1xyXG4gICAgICBvcGVuQm9va2luZzIuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoZSk9PnsgZS5wcmV2ZW50RGVmYXVsdCgpOyBzaG93Qm9va2luZygpOyB9KTtcclxuICAgIH1cclxuICAgIGlmKGNsb3NlQm9va2luZyl7XHJcbiAgICAgIGNsb3NlQm9va2luZy5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGhpZGVCb29raW5nKTtcclxuICAgIH1cclxuXHJcbiAgICBpZihib29raW5nUG9wdXApe1xyXG4gICAgICBib29raW5nUG9wdXAuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoZSk9PntcclxuICAgICAgICBpZihlLnRhcmdldCA9PT0gYm9va2luZ1BvcHVwKSBoaWRlQm9va2luZygpO1xyXG4gICAgICB9KTtcclxuICAgIH1cclxuXHJcbiAgICBpZihib29raW5nRm9ybSl7XHJcbiAgICAgIGJvb2tpbmdGb3JtLmFkZEV2ZW50TGlzdGVuZXIoJ3N1Ym1pdCcsIGZ1bmN0aW9uKGUpe1xyXG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcclxuICAgICAgICBhbGVydChcIllvdXIgYm9va2luZyByZXF1ZXN0IGhhcyBiZWVuIHN1Ym1pdHRlZCBzdWNjZXNzZnVsbHkhXCIpO1xyXG4gICAgICAgIGJvb2tpbmdGb3JtLnJlc2V0KCk7XHJcbiAgICAgICAgaGlkZUJvb2tpbmcoKTtcclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgLy8gUFJPRklMRSBQT1BVUFxyXG4gICAgY29uc3QgcHJvZmlsZVBvcHVwID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ3Byb2ZpbGVQb3B1cCcpO1xyXG4gICAgY29uc3Qgb3BlblByb2ZpbGUgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnb3BlblByb2ZpbGUnKTtcclxuICAgIGNvbnN0IGNsb3NlUHJvZmlsZSA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjbG9zZVByb2ZpbGUnKTtcclxuICAgIGNvbnN0IHByb2ZpbGVGb3JtID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ3Byb2ZpbGVGb3JtJyk7XHJcblxyXG4gICAgZnVuY3Rpb24gc2hvd1Byb2ZpbGUoKXtcclxuICAgICAgaWYoIXByb2ZpbGVQb3B1cCl7XHJcbiAgICAgICAgd2luZG93LmxvY2F0aW9uLmhyZWYgPSAnL3Byb2ZpbGUnO1xyXG4gICAgICAgIHJldHVybjtcclxuICAgICAgfVxyXG4gICAgICBwcm9maWxlUG9wdXAuY2xhc3NMaXN0LmFkZCgnYWN0aXZlJyk7XHJcbiAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnaGlkZGVuJztcclxuICAgIH1cclxuICAgIGZ1bmN0aW9uIGhpZGVQcm9maWxlKCl7XHJcbiAgICAgIGlmKCFwcm9maWxlUG9wdXApe1xyXG4gICAgICAgIHJldHVybjtcclxuICAgICAgfVxyXG4gICAgICBwcm9maWxlUG9wdXAuY2xhc3NMaXN0LnJlbW92ZSgnYWN0aXZlJyk7XHJcbiAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnJztcclxuICAgIH1cclxuXHJcbiAgICBpZihvcGVuUHJvZmlsZSl7XHJcbiAgICAgIG9wZW5Qcm9maWxlLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgc2hvd1Byb2ZpbGUpO1xyXG4gICAgfVxyXG4gICAgaWYoY2xvc2VQcm9maWxlKXtcclxuICAgICAgY2xvc2VQcm9maWxlLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgaGlkZVByb2ZpbGUpO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKHByb2ZpbGVQb3B1cCl7XHJcbiAgICAgIHByb2ZpbGVQb3B1cC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChlKT0+e1xyXG4gICAgICAgIGlmKGUudGFyZ2V0ID09PSBwcm9maWxlUG9wdXApIGhpZGVQcm9maWxlKCk7XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKHByb2ZpbGVGb3JtKXtcclxuICAgICAgcHJvZmlsZUZvcm0uYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgYXN5bmMgZnVuY3Rpb24oZSl7XHJcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xyXG5cclxuICAgICAgICBpZihwcm9maWxlRm9ybS5kYXRhc2V0LmF1dGhlbnRpY2F0ZWQgIT09ICcxJyl7XHJcbiAgICAgICAgICBhbGVydCgnUGxlYXNlIHNpZ24gaW4gZmlyc3QuJyk7XHJcbiAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9ICcvbG9naW4nO1xyXG4gICAgICAgICAgcmV0dXJuO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgY29uc3Qgc3VibWl0QnRuID0gcHJvZmlsZUZvcm0ucXVlcnlTZWxlY3RvcignYnV0dG9uW3R5cGU9XCJzdWJtaXRcIl0nKTtcclxuICAgICAgICBjb25zdCBvcmlnaW5hbFRleHQgPSBzdWJtaXRCdG4gPyBzdWJtaXRCdG4udGV4dENvbnRlbnQgOiAnU2F2ZSBQcm9maWxlJztcclxuXHJcbiAgICAgICAgdHJ5IHtcclxuICAgICAgICAgIGlmKHN1Ym1pdEJ0bil7XHJcbiAgICAgICAgICAgIHN1Ym1pdEJ0bi5kaXNhYmxlZCA9IHRydWU7XHJcbiAgICAgICAgICAgIHN1Ym1pdEJ0bi50ZXh0Q29udGVudCA9ICdTYXZpbmcuLi4nO1xyXG4gICAgICAgICAgfVxyXG5cclxuICAgICAgICAgIGNvbnN0IHJlc3BvbnNlID0gYXdhaXQgZmV0Y2gocHJvZmlsZUZvcm0uYWN0aW9uLCB7XHJcbiAgICAgICAgICAgIG1ldGhvZDogJ1BPU1QnLFxyXG4gICAgICAgICAgICBib2R5OiBuZXcgRm9ybURhdGEocHJvZmlsZUZvcm0pLFxyXG4gICAgICAgICAgICBoZWFkZXJzOiB7XHJcbiAgICAgICAgICAgICAgJ1gtUmVxdWVzdGVkLVdpdGgnOiAnWE1MSHR0cFJlcXVlc3QnLFxyXG4gICAgICAgICAgICAgICdBY2NlcHQnOiAnYXBwbGljYXRpb24vanNvbicsXHJcbiAgICAgICAgICAgIH0sXHJcbiAgICAgICAgICB9KTtcclxuXHJcbiAgICAgICAgICBjb25zdCBwYXlsb2FkID0gYXdhaXQgcmVzcG9uc2UuanNvbigpO1xyXG4gICAgICAgICAgaWYoIXJlc3BvbnNlLm9rIHx8ICFwYXlsb2FkLnN1Y2Nlc3Mpe1xyXG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IocGF5bG9hZC5tZXNzYWdlIHx8ICdVbmFibGUgdG8gc2F2ZSBwcm9maWxlLicpO1xyXG4gICAgICAgICAgfVxyXG5cclxuICAgICAgICAgIGNvbnN0IGZ1bGxOYW1lSW5wdXQgPSBwcm9maWxlRm9ybS5xdWVyeVNlbGVjdG9yKCdpbnB1dFtuYW1lPVwiZnVsbF9uYW1lXCJdJyk7XHJcbiAgICAgICAgICBjb25zdCBwaG9uZUlucHV0ID0gcHJvZmlsZUZvcm0ucXVlcnlTZWxlY3RvcignaW5wdXRbbmFtZT1cInBob25lXCJdJyk7XHJcbiAgICAgICAgICBjb25zdCBhZGRyZXNzSW5wdXQgPSBwcm9maWxlRm9ybS5xdWVyeVNlbGVjdG9yKCdpbnB1dFtuYW1lPVwiYWRkcmVzc1wiXScpO1xyXG4gICAgICAgICAgY29uc3QgZW1haWxJbnB1dCA9IHByb2ZpbGVGb3JtLnF1ZXJ5U2VsZWN0b3IoJ2lucHV0W25hbWU9XCJlbWFpbFwiXScpO1xyXG5cclxuICAgICAgICAgIGlmKGZ1bGxOYW1lSW5wdXQgJiYgcGF5bG9hZC5wcm9maWxlICYmIHR5cGVvZiBwYXlsb2FkLnByb2ZpbGUuZnVsbE5hbWUgPT09ICdzdHJpbmcnKXtcclxuICAgICAgICAgICAgZnVsbE5hbWVJbnB1dC52YWx1ZSA9IHBheWxvYWQucHJvZmlsZS5mdWxsTmFtZTtcclxuICAgICAgICAgIH1cclxuICAgICAgICAgIGlmKHBob25lSW5wdXQgJiYgcGF5bG9hZC5wcm9maWxlICYmIHR5cGVvZiBwYXlsb2FkLnByb2ZpbGUucGhvbmUgPT09ICdzdHJpbmcnKXtcclxuICAgICAgICAgICAgcGhvbmVJbnB1dC52YWx1ZSA9IHBheWxvYWQucHJvZmlsZS5waG9uZTtcclxuICAgICAgICAgIH1cclxuICAgICAgICAgIGlmKGFkZHJlc3NJbnB1dCAmJiBwYXlsb2FkLnByb2ZpbGUgJiYgdHlwZW9mIHBheWxvYWQucHJvZmlsZS5hZGRyZXNzID09PSAnc3RyaW5nJyl7XHJcbiAgICAgICAgICAgIGFkZHJlc3NJbnB1dC52YWx1ZSA9IHBheWxvYWQucHJvZmlsZS5hZGRyZXNzO1xyXG4gICAgICAgICAgfVxyXG4gICAgICAgICAgaWYoZW1haWxJbnB1dCAmJiBwYXlsb2FkLnByb2ZpbGUgJiYgdHlwZW9mIHBheWxvYWQucHJvZmlsZS5lbWFpbCA9PT0gJ3N0cmluZycpe1xyXG4gICAgICAgICAgICBlbWFpbElucHV0LnZhbHVlID0gcGF5bG9hZC5wcm9maWxlLmVtYWlsO1xyXG4gICAgICAgICAgfVxyXG5cclxuICAgICAgICAgIGFsZXJ0KHBheWxvYWQubWVzc2FnZSB8fCAnWW91ciBwcm9maWxlIGhhcyBiZWVuIHNhdmVkIHN1Y2Nlc3NmdWxseS4nKTtcclxuICAgICAgICAgIGhpZGVQcm9maWxlKCk7XHJcbiAgICAgICAgfSBjYXRjaChlcnIpe1xyXG4gICAgICAgICAgYWxlcnQoZXJyIGluc3RhbmNlb2YgRXJyb3IgPyBlcnIubWVzc2FnZSA6ICdVbmFibGUgdG8gc2F2ZSBwcm9maWxlLicpO1xyXG4gICAgICAgIH0gZmluYWxseSB7XHJcbiAgICAgICAgICBpZihzdWJtaXRCdG4pe1xyXG4gICAgICAgICAgICBzdWJtaXRCdG4uZGlzYWJsZWQgPSBmYWxzZTtcclxuICAgICAgICAgICAgc3VibWl0QnRuLnRleHRDb250ZW50ID0gb3JpZ2luYWxUZXh0O1xyXG4gICAgICAgICAgfVxyXG4gICAgICAgIH1cclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgLy8gQ0FSVCBTWVNURU1cclxuICAgIGNvbnN0IGNhcnRPdmVybGF5ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2NhcnRPdmVybGF5Jyk7XHJcbiAgICBjb25zdCBvcGVuQ2FydCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcGVuQ2FydCcpO1xyXG4gICAgY29uc3Qgb3BlbkNhcnQyID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29wZW5DYXJ0MicpO1xyXG4gICAgY29uc3QgY2xvc2VDYXJ0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Nsb3NlQ2FydCcpO1xuICAgIGNvbnN0IGNhcnRJdGVtc0NvbnRhaW5lciA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjYXJ0SXRlbXMnKTtcbiAgICBjb25zdCBjYXJ0VG90YWwgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FydFRvdGFsJyk7XG4gICAgY29uc3QgY2FydENvdW50ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2NhcnRDb3VudCcpO1xuICAgIGNvbnN0IGNoZWNrb3V0QnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2NoZWNrb3V0QnRuJyk7XG5cbiAgICBsZXQgY2FydCA9IFtdO1xuXHJcbiAgICBmdW5jdGlvbiBzaG93Q2FydCgpe1xyXG4gICAgICBpZighY2FydE92ZXJsYXkpe1xyXG4gICAgICAgIHJldHVybjtcclxuICAgICAgfVxyXG4gICAgICBjYXJ0T3ZlcmxheS5jbGFzc0xpc3QuYWRkKCdhY3RpdmUnKTtcclxuICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vdmVyZmxvdyA9ICdoaWRkZW4nO1xyXG4gICAgfVxyXG4gICAgZnVuY3Rpb24gaGlkZUNhcnQoKXtcclxuICAgICAgaWYoIWNhcnRPdmVybGF5KXtcclxuICAgICAgICByZXR1cm47XHJcbiAgICAgIH1cclxuICAgICAgY2FydE92ZXJsYXkuY2xhc3NMaXN0LnJlbW92ZSgnYWN0aXZlJyk7XHJcbiAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnJztcclxuICAgIH1cclxuXHJcbiAgICBpZihvcGVuQ2FydCl7XHJcbiAgICAgIG9wZW5DYXJ0LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgc2hvd0NhcnQpO1xyXG4gICAgfVxyXG4gICAgaWYob3BlbkNhcnQyKXtcclxuICAgICAgb3BlbkNhcnQyLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgc2hvd0NhcnQpO1xyXG4gICAgfVxyXG4gICAgaWYoY2xvc2VDYXJ0KXtcclxuICAgICAgY2xvc2VDYXJ0LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgaGlkZUNhcnQpO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKGNhcnRPdmVybGF5KXtcbiAgICAgIGNhcnRPdmVybGF5LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGUpPT57XG4gICAgICAgIGlmKGUudGFyZ2V0ID09PSBjYXJ0T3ZlcmxheSkgaGlkZUNhcnQoKTtcbiAgICAgIH0pO1xuICAgIH1cblxuICAgIGZ1bmN0aW9uIHB1bHNlQWRkVG9DYXJ0QnV0dG9uKGJ0bil7XG4gICAgICBpZighYnRuKXtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuXG4gICAgICBjb25zdCBkZWZhdWx0TGFiZWwgPSBidG4uZGF0YXNldC5kZWZhdWx0TGFiZWwgfHwgYnRuLnRleHRDb250ZW50LnRyaW0oKSB8fCAnQWRkIHRvIENhcnQnO1xuICAgICAgYnRuLmRhdGFzZXQuZGVmYXVsdExhYmVsID0gZGVmYXVsdExhYmVsO1xuICAgICAgYnRuLnRleHRDb250ZW50ID0gJ0FkZGVkJztcblxuICAgICAgd2luZG93LmNsZWFyVGltZW91dChOdW1iZXIoYnRuLmRhdGFzZXQucmVzZXRUaW1lciB8fCAwKSk7XG4gICAgICBjb25zdCB0aW1lciA9IHdpbmRvdy5zZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgYnRuLnRleHRDb250ZW50ID0gZGVmYXVsdExhYmVsO1xuICAgICAgICBkZWxldGUgYnRuLmRhdGFzZXQucmVzZXRUaW1lcjtcbiAgICAgIH0sIDkwMCk7XG5cbiAgICAgIGJ0bi5kYXRhc2V0LnJlc2V0VGltZXIgPSBTdHJpbmcodGltZXIpO1xuICAgIH1cblxuICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuICAgICAgY29uc3QgYnRuID0gZS50YXJnZXQuY2xvc2VzdCAmJiBlLnRhcmdldC5jbG9zZXN0KCcuYWRkLWNhcnQtYnRuJyk7XG4gICAgICBpZiAoIWJ0bikgcmV0dXJuO1xuXG4gICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICBjb25zdCBuYW1lID0gYnRuLmRhdGFzZXQubmFtZTtcbiAgICAgIGNvbnN0IHByaWNlID0gTnVtYmVyKGJ0bi5kYXRhc2V0LnByaWNlKTtcblxuICAgICAgaWYoIW5hbWUgfHwgTnVtYmVyLmlzTmFOKHByaWNlKSl7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cblxuICAgICAgY2FydC5wdXNoKHtuYW1lLCBwcmljZX0pO1xuICAgICAgdXBkYXRlQ2FydCgpO1xuICAgICAgcHVsc2VBZGRUb0NhcnRCdXR0b24oYnRuKTtcbiAgICB9KTtcblxyXG4gICAgZnVuY3Rpb24gdXBkYXRlQ2FydCgpe1xyXG4gICAgICBpZighY2FydEl0ZW1zQ29udGFpbmVyIHx8ICFjYXJ0VG90YWwgfHwgIWNhcnRDb3VudCl7XHJcbiAgICAgICAgcmV0dXJuO1xyXG4gICAgICB9XHJcbiAgICAgIGNhcnRJdGVtc0NvbnRhaW5lci5pbm5lckhUTUwgPSAnJztcclxuXHJcbiAgICAgIGlmKGNhcnQubGVuZ3RoID09PSAwKXtcclxuICAgICAgICBjYXJ0SXRlbXNDb250YWluZXIuaW5uZXJIVE1MID0gYDxkaXYgY2xhc3M9XCJlbXB0eS1jYXJ0XCI+WW91ciBsdXh1cnkgY2FydCBpcyBjdXJyZW50bHkgZW1wdHkuPC9kaXY+YDtcclxuICAgICAgfSBlbHNlIHtcclxuICAgICAgICBjYXJ0LmZvckVhY2goKGl0ZW0sIGluZGV4KT0+e1xyXG4gICAgICAgICAgY29uc3QgY2FydEl0ZW0gPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcclxuICAgICAgICAgIGNhcnRJdGVtLmNsYXNzTGlzdC5hZGQoJ2NhcnQtaXRlbScpO1xyXG4gICAgICAgICAgY2FydEl0ZW0uaW5uZXJIVE1MID0gYFxyXG4gICAgICAgICAgICA8ZGl2PlxyXG4gICAgICAgICAgICAgIDxoND4ke2l0ZW0ubmFtZX08L2g0PlxyXG4gICAgICAgICAgICAgIDxwPiR7aXRlbS5wcmljZX0gVE5EPC9wPlxyXG4gICAgICAgICAgICA8L2Rpdj5cclxuICAgICAgICAgICAgPGJ1dHRvbiBjbGFzcz1cInJlbW92ZS1idG5cIiBvbmNsaWNrPVwicmVtb3ZlRnJvbUNhcnQoJHtpbmRleH0pXCI+UmVtb3ZlPC9idXR0b24+XHJcbiAgICAgICAgICBgO1xyXG4gICAgICAgICAgY2FydEl0ZW1zQ29udGFpbmVyLmFwcGVuZENoaWxkKGNhcnRJdGVtKTtcclxuICAgICAgICB9KTtcclxuICAgICAgfVxyXG5cclxuICAgICAgY29uc3QgdG90YWwgPSBjYXJ0LnJlZHVjZSgoc3VtLCBpdGVtKT0+IHN1bSArIGl0ZW0ucHJpY2UsIDApO1xyXG4gICAgICBjYXJ0VG90YWwudGV4dENvbnRlbnQgPSBgJHt0b3RhbH0gVE5EYDtcclxuICAgICAgY2FydENvdW50LnRleHRDb250ZW50ID0gY2FydC5sZW5ndGg7XHJcbiAgICB9XHJcblxyXG4gICAgZnVuY3Rpb24gc2hvd0Zyb250Rmxhc2gobWVzc2FnZSwgaXNFcnJvciA9IGZhbHNlKXtcclxuICAgICAgY29uc3QgZXhpc3RpbmcgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZnJvbnRGbGFzaFRvYXN0Jyk7XHJcbiAgICAgIGlmIChleGlzdGluZykge1xyXG4gICAgICAgIGV4aXN0aW5nLnJlbW92ZSgpO1xyXG4gICAgICB9XHJcblxyXG4gICAgICBjb25zdCB0b2FzdCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xyXG4gICAgICB0b2FzdC5pZCA9ICdmcm9udEZsYXNoVG9hc3QnO1xyXG4gICAgICB0b2FzdC5zdHlsZS5jc3NUZXh0ID0gJ3Bvc2l0aW9uOmZpeGVkO3RvcDo5MnB4O2xlZnQ6NTAlO3RyYW5zZm9ybTp0cmFuc2xhdGVYKC01MCUpO3otaW5kZXg6OTk5OTtwYWRkaW5nOjFyZW0gMS42cmVtO2JvcmRlci1yYWRpdXM6OTk5cHg7Zm9udC1zaXplOi45MnJlbTtmb250LXdlaWdodDo2MDA7Y29sb3I6I2ZmZjtib3gtc2hhZG93OjAgMTBweCAzMHB4IHJnYmEoNDQsMjYsMTQsLjI1KTttYXgtd2lkdGg6OTB2dzt0ZXh0LWFsaWduOmNlbnRlcjsnO1xyXG4gICAgICB0b2FzdC5zdHlsZS5iYWNrZ3JvdW5kID0gaXNFcnJvclxyXG4gICAgICAgID8gJ2xpbmVhci1ncmFkaWVudCgxMzVkZWcsI0Q5NDA0MCwjYTgyYTJhKSdcclxuICAgICAgICA6ICdsaW5lYXItZ3JhZGllbnQoMTM1ZGVnLCMyRTlFNkEsIzFlN2E1MiknO1xyXG4gICAgICB0b2FzdC50ZXh0Q29udGVudCA9IGAke2lzRXJyb3IgPyAn4pyVJyA6ICfinJMnfSAke21lc3NhZ2V9YDtcclxuICAgICAgZG9jdW1lbnQuYm9keS5hcHBlbmRDaGlsZCh0b2FzdCk7XHJcblxyXG4gICAgICB3aW5kb3cuc2V0VGltZW91dCgoKSA9PiB7XHJcbiAgICAgICAgdG9hc3QucmVtb3ZlKCk7XHJcbiAgICAgIH0sIDM1MDApO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKGNoZWNrb3V0QnRuKXtcclxuICAgICAgY2hlY2tvdXRCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoZSk9PntcclxuICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XHJcbiAgICAgICAgZS5zdG9wUHJvcGFnYXRpb24oKTtcclxuICAgICAgICBcclxuICAgICAgICBpZihjYXJ0Lmxlbmd0aCA9PT0gMCl7XHJcbiAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9ICcvb3JkZXJzL2NyZWF0ZS1mcm9tLWNhcnQ/dmFsaWRhdGlvbl9vbmx5PTEnO1xyXG4gICAgICAgICAgcmV0dXJuO1xyXG4gICAgICAgIH1cclxuXHJcbiAgICAgICAgY29uc3QgdG90YWwgPSBjYXJ0LnJlZHVjZSgoc3VtLCBpdGVtKT0+IHN1bSArIGl0ZW0ucHJpY2UsIDApO1xyXG4gICAgICAgIGNvbnN0IGNhcnRJdGVtc0lucHV0ID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ3JlZGlyZWN0Q2FydEl0ZW1zSW5wdXQnKTtcclxuICAgICAgICBjb25zdCBjYXJ0VG90YWxJbnB1dCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdyZWRpcmVjdENhcnRUb3RhbElucHV0Jyk7XHJcbiAgICAgICAgaWYoY2FydEl0ZW1zSW5wdXQgJiYgY2FydFRvdGFsSW5wdXQpe1xyXG4gICAgICAgICAgY2FydEl0ZW1zSW5wdXQudmFsdWUgPSBKU09OLnN0cmluZ2lmeShjYXJ0KTtcclxuICAgICAgICAgIGNhcnRUb3RhbElucHV0LnZhbHVlID0gdG90YWwudG9GaXhlZCgyKTtcclxuICAgICAgICB9XHJcbiAgICAgICAgXHJcbiAgICAgICAgLy8gSGlkZSB0aGUgY2FydCBvdmVybGF5XHJcbiAgICAgICAgY29uc3QgY2FydE92ZXJsYXkgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2FydE92ZXJsYXknKTtcclxuICAgICAgICBpZihjYXJ0T3ZlcmxheSl7XHJcbiAgICAgICAgICBjYXJ0T3ZlcmxheS5jbGFzc0xpc3QucmVtb3ZlKCdhY3RpdmUnKTtcclxuICAgICAgICB9XHJcbiAgICAgICAgXHJcbiAgICAgICAgLy8gU2hvdyBvcmRlciB0eXBlIHNlbGVjdGlvbiBtb2RhbFxyXG4gICAgICAgIGNvbnN0IG1vZGFsID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ29yZGVyVHlwZU1vZGFsJyk7XHJcbiAgICAgICAgaWYobW9kYWwpe1xyXG4gICAgICAgICAgbW9kYWwuc3R5bGUuZGlzcGxheSA9ICdmbGV4JztcclxuICAgICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnaGlkZGVuJztcclxuICAgICAgICB9XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIGZ1bmN0aW9uIHJlbW92ZUZyb21DYXJ0KGluZGV4KXtcclxuICAgICAgY2FydC5zcGxpY2UoaW5kZXgsMSk7XHJcbiAgICAgIHVwZGF0ZUNhcnQoKTtcclxuICAgIH1cclxuXHJcbiAgICB3aW5kb3cucmVtb3ZlRnJvbUNhcnQgPSByZW1vdmVGcm9tQ2FydDtcclxuXHJcbiAgICAvLyBPUkRFUiBUWVBFIE1PREFMXHJcbiAgICBjb25zdCBvcmRlclR5cGVNb2RhbCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmRlclR5cGVNb2RhbCcpO1xyXG4gICAgY29uc3QgZGluZUluQnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2RpbmVJbkJ0bicpO1xyXG4gICAgY29uc3QgZGVsaXZlcnlCdG4gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnZGVsaXZlcnlCdG4nKTtcclxuICAgIGNvbnN0IGNsb3NlT3JkZXJUeXBlQnRuID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2Nsb3NlT3JkZXJUeXBlJyk7XHJcbiAgICBjb25zdCBvcmRlclR5cGVJbnB1dCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdvcmRlclR5cGVJbnB1dCcpO1xyXG4gICAgY29uc3QgY2hlY2tvdXRSZWRpcmVjdEZvcm0gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnY2hlY2tvdXRSZWRpcmVjdEZvcm0nKTtcclxuXHJcbiAgICBmdW5jdGlvbiBjbG9zZU9yZGVyVHlwZU1vZGFsKCl7XHJcbiAgICAgIGlmKG9yZGVyVHlwZU1vZGFsKXtcclxuICAgICAgICBvcmRlclR5cGVNb2RhbC5zdHlsZS5kaXNwbGF5ID0gJ25vbmUnO1xyXG4gICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUub3ZlcmZsb3cgPSAnJztcclxuICAgICAgfVxyXG4gICAgfVxyXG5cclxuICAgIGlmKGNsb3NlT3JkZXJUeXBlQnRuKXtcclxuICAgICAgY2xvc2VPcmRlclR5cGVCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBjbG9zZU9yZGVyVHlwZU1vZGFsKTtcclxuICAgIH1cclxuICAgIFxyXG4gICAgaWYob3JkZXJUeXBlTW9kYWwpe1xyXG4gICAgICBvcmRlclR5cGVNb2RhbC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIChlKT0+e1xyXG4gICAgICAgIC8vIE9ubHkgY2xvc2UgaWYgY2xpY2tpbmcgdGhlIGJhY2tncm91bmQgb3ZlcmxheSwgbm90IHRoZSBpbm5lciBjb250ZW50XHJcbiAgICAgICAgaWYoZS50YXJnZXQgPT09IG9yZGVyVHlwZU1vZGFsKSBjbG9zZU9yZGVyVHlwZU1vZGFsKCk7XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKGRpbmVJbkJ0bil7XHJcbiAgICAgIGRpbmVJbkJ0bi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpPT57XHJcbiAgICAgICAgaWYgKG9yZGVyVHlwZUlucHV0KSB7XHJcbiAgICAgICAgICBvcmRlclR5cGVJbnB1dC52YWx1ZSA9ICdESU5FX0lOJztcclxuICAgICAgICB9XHJcbiAgICAgICAgaWYgKGNoZWNrb3V0UmVkaXJlY3RGb3JtKSB7XHJcbiAgICAgICAgICBjaGVja291dFJlZGlyZWN0Rm9ybS5zdWJtaXQoKTtcclxuICAgICAgICB9XHJcbiAgICAgIH0pO1xyXG4gICAgfVxyXG5cclxuICAgIGlmKGRlbGl2ZXJ5QnRuKXtcclxuICAgICAgZGVsaXZlcnlCdG4uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCAoKT0+e1xyXG4gICAgICAgIGlmIChvcmRlclR5cGVJbnB1dCkge1xyXG4gICAgICAgICAgb3JkZXJUeXBlSW5wdXQudmFsdWUgPSAnREVMSVZFUlknO1xyXG4gICAgICAgIH1cclxuICAgICAgICBpZiAoY2hlY2tvdXRSZWRpcmVjdEZvcm0pIHtcclxuICAgICAgICAgIGNoZWNrb3V0UmVkaXJlY3RGb3JtLnN1Ym1pdCgpO1xyXG4gICAgICAgIH1cclxuICAgICAgfSk7XHJcbiAgICB9XHJcblxyXG4gICAgLy8gRVNDIENMT1NFXHJcbiAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdrZXlkb3duJywgKGUpPT57XHJcbiAgICAgIGlmKGUua2V5ID09PSBcIkVzY2FwZVwiKXtcclxuICAgICAgICBoaWRlQm9va2luZygpO1xyXG4gICAgICAgIGhpZGVDYXJ0KCk7XHJcbiAgICAgICAgaGlkZVByb2ZpbGUoKTtcclxuICAgICAgICBjbG9zZU9yZGVyVHlwZU1vZGFsKCk7XHJcbiAgICAgIH1cclxuICAgIH0pO1xyXG59KTtcclxuXHJcbiJdLCJuYW1lcyI6WyJlIiwidCIsInIiLCJTeW1ib2wiLCJuIiwiaXRlcmF0b3IiLCJvIiwidG9TdHJpbmdUYWciLCJpIiwiYyIsInByb3RvdHlwZSIsIkdlbmVyYXRvciIsInUiLCJPYmplY3QiLCJjcmVhdGUiLCJfcmVnZW5lcmF0b3JEZWZpbmUyIiwiZiIsInAiLCJ5IiwiRyIsInYiLCJhIiwiZCIsImJpbmQiLCJsZW5ndGgiLCJsIiwiVHlwZUVycm9yIiwiY2FsbCIsImRvbmUiLCJ2YWx1ZSIsInJldHVybiIsIkdlbmVyYXRvckZ1bmN0aW9uIiwiR2VuZXJhdG9yRnVuY3Rpb25Qcm90b3R5cGUiLCJnZXRQcm90b3R5cGVPZiIsInNldFByb3RvdHlwZU9mIiwiX19wcm90b19fIiwiZGlzcGxheU5hbWUiLCJfcmVnZW5lcmF0b3IiLCJ3IiwibSIsImRlZmluZVByb3BlcnR5IiwiX3JlZ2VuZXJhdG9yRGVmaW5lIiwiX2ludm9rZSIsImVudW1lcmFibGUiLCJjb25maWd1cmFibGUiLCJ3cml0YWJsZSIsImFzeW5jR2VuZXJhdG9yU3RlcCIsIlByb21pc2UiLCJyZXNvbHZlIiwidGhlbiIsIl9hc3luY1RvR2VuZXJhdG9yIiwiYXJndW1lbnRzIiwiYXBwbHkiLCJfbmV4dCIsIl90aHJvdyIsImRvY3VtZW50IiwiYWRkRXZlbnRMaXN0ZW5lciIsIm5hdiIsImdldEVsZW1lbnRCeUlkIiwid2luZG93IiwiY2xhc3NMaXN0IiwidG9nZ2xlIiwic2Nyb2xsWSIsIm9ic2VydmVyIiwiSW50ZXJzZWN0aW9uT2JzZXJ2ZXIiLCJlbnRyaWVzIiwiZm9yRWFjaCIsImVudHJ5IiwiaXNJbnRlcnNlY3RpbmciLCJ0YXJnZXQiLCJhZGQiLCJ1bm9ic2VydmUiLCJ0aHJlc2hvbGQiLCJyZXZlYWxFbHMiLCJxdWVyeVNlbGVjdG9yQWxsIiwiZWwiLCJvYnNlcnZlIiwiY3VycmVuY3lSYXRlcyIsIlRORCIsIlVTRCIsIkVVUiIsIkNOWSIsInNlbGVjdCIsImZvb3RlciIsImNsb3Nlc3QiLCJwcmljZUVsIiwicXVlcnlTZWxlY3RvciIsImJhc2VQcmljZSIsIk51bWJlciIsImRhdGFzZXQiLCJjdXJyZW5jeSIsImhhc093blByb3BlcnR5IiwiY29udmVydGVkIiwidGV4dENvbnRlbnQiLCJjb25jYXQiLCJ0b0ZpeGVkIiwiYm9va2luZ1BvcHVwIiwib3BlbkJvb2tpbmciLCJvcGVuQm9va2luZzIiLCJjbG9zZUJvb2tpbmciLCJib29raW5nRm9ybSIsInNob3dCb29raW5nIiwiYm9keSIsInN0eWxlIiwib3ZlcmZsb3ciLCJoaWRlQm9va2luZyIsInJlbW92ZSIsInByZXZlbnREZWZhdWx0IiwiYWxlcnQiLCJyZXNldCIsInByb2ZpbGVQb3B1cCIsIm9wZW5Qcm9maWxlIiwiY2xvc2VQcm9maWxlIiwicHJvZmlsZUZvcm0iLCJzaG93UHJvZmlsZSIsImxvY2F0aW9uIiwiaHJlZiIsImhpZGVQcm9maWxlIiwiX3JlZiIsIl9jYWxsZWUiLCJzdWJtaXRCdG4iLCJvcmlnaW5hbFRleHQiLCJyZXNwb25zZSIsInBheWxvYWQiLCJmdWxsTmFtZUlucHV0IiwicGhvbmVJbnB1dCIsImFkZHJlc3NJbnB1dCIsImVtYWlsSW5wdXQiLCJfdCIsIl9jb250ZXh0IiwiYXV0aGVudGljYXRlZCIsImRpc2FibGVkIiwiZmV0Y2giLCJhY3Rpb24iLCJtZXRob2QiLCJGb3JtRGF0YSIsImhlYWRlcnMiLCJqc29uIiwib2siLCJzdWNjZXNzIiwiRXJyb3IiLCJtZXNzYWdlIiwicHJvZmlsZSIsImZ1bGxOYW1lIiwicGhvbmUiLCJhZGRyZXNzIiwiZW1haWwiLCJfeCIsImNhcnRPdmVybGF5Iiwib3BlbkNhcnQiLCJvcGVuQ2FydDIiLCJjbG9zZUNhcnQiLCJjYXJ0SXRlbXNDb250YWluZXIiLCJjYXJ0VG90YWwiLCJjYXJ0Q291bnQiLCJjaGVja291dEJ0biIsImNhcnQiLCJzaG93Q2FydCIsImhpZGVDYXJ0IiwicHVsc2VBZGRUb0NhcnRCdXR0b24iLCJidG4iLCJkZWZhdWx0TGFiZWwiLCJ0cmltIiwiY2xlYXJUaW1lb3V0IiwicmVzZXRUaW1lciIsInRpbWVyIiwic2V0VGltZW91dCIsIlN0cmluZyIsIm5hbWUiLCJwcmljZSIsImlzTmFOIiwicHVzaCIsInVwZGF0ZUNhcnQiLCJpbm5lckhUTUwiLCJpdGVtIiwiaW5kZXgiLCJjYXJ0SXRlbSIsImNyZWF0ZUVsZW1lbnQiLCJhcHBlbmRDaGlsZCIsInRvdGFsIiwicmVkdWNlIiwic3VtIiwic2hvd0Zyb250Rmxhc2giLCJpc0Vycm9yIiwidW5kZWZpbmVkIiwiZXhpc3RpbmciLCJ0b2FzdCIsImlkIiwiY3NzVGV4dCIsImJhY2tncm91bmQiLCJzdG9wUHJvcGFnYXRpb24iLCJjYXJ0SXRlbXNJbnB1dCIsImNhcnRUb3RhbElucHV0IiwiSlNPTiIsInN0cmluZ2lmeSIsIm1vZGFsIiwiZGlzcGxheSIsInJlbW92ZUZyb21DYXJ0Iiwic3BsaWNlIiwib3JkZXJUeXBlTW9kYWwiLCJkaW5lSW5CdG4iLCJkZWxpdmVyeUJ0biIsImNsb3NlT3JkZXJUeXBlQnRuIiwib3JkZXJUeXBlSW5wdXQiLCJjaGVja291dFJlZGlyZWN0Rm9ybSIsImNsb3NlT3JkZXJUeXBlTW9kYWwiLCJzdWJtaXQiLCJrZXkiXSwic291cmNlUm9vdCI6IiJ9