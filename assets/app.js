
document.addEventListener('DOMContentLoaded', function() {
    // NAV SCROLL
    const nav = document.getElementById('nav');
    if (nav) {
      window.addEventListener('scroll',()=>{
        nav.classList.toggle('scrolled', window.scrollY > 40);
      });
    }

    // SMOOTH REVEAL
    const observer = new IntersectionObserver((entries)=>{
      entries.forEach((entry)=>{
        if(entry.isIntersecting){
          entry.target.classList.add('show');
          observer.unobserve(entry.target);
        }
      });
    },{threshold:0.15});

    const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    if (revealEls.length) {
      revealEls.forEach(el=>{
        observer.observe(el);
      });
    }

    // CURRENCY CONVERSION (fixed rates from 1 TND)
    const currencyRates = { TND: 1, USD: 0.32, EUR: 0.30, CNY: 2.31 };
    document.querySelectorAll('.currency-select').forEach(select=>{
      select.addEventListener('change', ()=>{
        const footer = select.closest('.menu-card-footer');
        const priceEl = footer ? footer.querySelector('.js-convertible-price') : null;
        if(!priceEl){
          return;
        }

        const basePrice = Number(priceEl.dataset.basePrice || select.dataset.basePrice || 0);
        const currency = Object.prototype.hasOwnProperty.call(currencyRates, select.value) ? select.value : 'TND';
        const converted = basePrice * currencyRates[currency];
        priceEl.textContent = `${converted.toFixed(2)} ${currency}`;
      });
    });

    // BOOKING POPUP
    const bookingPopup = document.getElementById('bookingPopup');
    const openBooking = document.getElementById('openBooking');
    const openBooking2 = document.getElementById('openBooking2');
    const closeBooking = document.getElementById('closeBooking');
    const bookingForm = document.getElementById('bookingForm');

    function showBooking(){
      if(!bookingPopup){
        return;
      }
      bookingPopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideBooking(){
      if(!bookingPopup){
        return;
      }
      bookingPopup.classList.remove('active');
      document.body.style.overflow = '';
    }

    if(openBooking){
      openBooking.addEventListener('click', (e)=>{ e.preventDefault(); showBooking(); });
    }
    if(openBooking2){
      openBooking2.addEventListener('click', (e)=>{ e.preventDefault(); showBooking(); });
    }
    if(closeBooking){
      closeBooking.addEventListener('click', hideBooking);
    }

    if(bookingPopup){
      bookingPopup.addEventListener('click', (e)=>{
        if(e.target === bookingPopup) hideBooking();
      });
    }

    if(bookingForm){
      bookingForm.addEventListener('submit', function(e){
        e.preventDefault();
        alert("Your booking request has been submitted successfully!");
        bookingForm.reset();
        hideBooking();
      });
    }

    // PROFILE POPUP
    const profilePopup = document.getElementById('profilePopup');
    const openProfile = document.getElementById('openProfile');
    const closeProfile = document.getElementById('closeProfile');
    const profileForm = document.getElementById('profileForm');

    function showProfile(){
      if(!profilePopup){
        window.location.href = '/profile';
        return;
      }
      profilePopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideProfile(){
      if(!profilePopup){
        return;
      }
      profilePopup.classList.remove('active');
      document.body.style.overflow = '';
    }

    if(openProfile){
      openProfile.addEventListener('click', showProfile);
    }
    if(closeProfile){
      closeProfile.addEventListener('click', hideProfile);
    }

    if(profilePopup){
      profilePopup.addEventListener('click', (e)=>{
        if(e.target === profilePopup) hideProfile();
      });
    }

    if(profileForm){
      profileForm.addEventListener('submit', async function(e){
        e.preventDefault();

        if(profileForm.dataset.authenticated !== '1'){
          alert('Please sign in first.');
          window.location.href = '/login';
          return;
        }

        const submitBtn = profileForm.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Save Profile';

        try {
          if(submitBtn){
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
          }

          const response = await fetch(profileForm.action, {
            method: 'POST',
            body: new FormData(profileForm),
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
          });

          const payload = await response.json();
          if(!response.ok || !payload.success){
            throw new Error(payload.message || 'Unable to save profile.');
          }

          const fullNameInput = profileForm.querySelector('input[name="full_name"]');
          const phoneInput = profileForm.querySelector('input[name="phone"]');
          const addressInput = profileForm.querySelector('input[name="address"]');
          const emailInput = profileForm.querySelector('input[name="email"]');

          if(fullNameInput && payload.profile && typeof payload.profile.fullName === 'string'){
            fullNameInput.value = payload.profile.fullName;
          }
          if(phoneInput && payload.profile && typeof payload.profile.phone === 'string'){
            phoneInput.value = payload.profile.phone;
          }
          if(addressInput && payload.profile && typeof payload.profile.address === 'string'){
            addressInput.value = payload.profile.address;
          }
          if(emailInput && payload.profile && typeof payload.profile.email === 'string'){
            emailInput.value = payload.profile.email;
          }

          alert(payload.message || 'Your profile has been saved successfully.');
          hideProfile();
        } catch(err){
          alert(err instanceof Error ? err.message : 'Unable to save profile.');
        } finally {
          if(submitBtn){
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
          }
        }
      });
    }

    // CART SYSTEM
    const cartOverlay = document.getElementById('cartOverlay');
    const openCart = document.getElementById('openCart');
    const openCart2 = document.getElementById('openCart2');
    const closeCart = document.getElementById('closeCart');
    const cartItemsContainer = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartCount = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const cartOverlay = document.getElementById('cartOverlay');
    const openCart = document.getElementById('openCart');
    const openCart2 = document.getElementById('openCart2');
    const closeCart = document.getElementById('closeCart');

    let cart = [];

    function showCart(){
      if(!cartOverlay){
        return;
      }
      cartOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideCart(){
      if(!cartOverlay){
        return;
      }
      cartOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    if(openCart){
      openCart.addEventListener('click', showCart);
    }
    if(openCart2){
      openCart2.addEventListener('click', showCart);
    }
    if(closeCart){
      closeCart.addEventListener('click', hideCart);
    }

    if(cartOverlay){
      cartOverlay.addEventListener('click', (e)=>{
        if(e.target === cartOverlay) hideCart();
      });
    }

    // Existing direct bindings (for buttons present at load)
    document.querySelectorAll('.add-cart-btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const name = btn.dataset.name;
        const price = Number(btn.dataset.price);

        cart.push({name, price});
        updateCart();

        btn.textContent = "Added";
        setTimeout(()=> btn.textContent = "Add to Cart", 1200);
      });
    });

    // Event delegation: ensure dynamically inserted or later-updated buttons also work
    document.addEventListener('click', function(e) {
      const btn = e.target.closest && e.target.closest('.add-cart-btn');
      if (!btn) return;
      e.preventDefault();
      const name = btn.dataset.name;
      const price = Number(btn.dataset.price);
      cart.push({name, price});
      updateCart();
      const original = btn.textContent;
      btn.textContent = 'Added';
      setTimeout(()=> btn.textContent = original || 'Add to Cart', 1200);
    });

    function updateCart(){
      if(!cartItemsContainer || !cartTotal || !cartCount){
        return;
      }
      cartItemsContainer.innerHTML = '';

      if(cart.length === 0){
        cartItemsContainer.innerHTML = `<div class="empty-cart">Your luxury cart is currently empty.</div>`;
      } else {
        cart.forEach((item, index)=>{
          const cartItem = document.createElement('div');
          cartItem.classList.add('cart-item');
          cartItem.innerHTML = `
            <div>
              <h4>${item.name}</h4>
              <p>${item.price} TND</p>
            </div>
            <button class="remove-btn" onclick="removeFromCart(${index})">Remove</button>
          `;
          cartItemsContainer.appendChild(cartItem);
        });
      }

      const total = cart.reduce((sum, item)=> sum + item.price, 0);
      cartTotal.textContent = `${total} TND`;
      cartCount.textContent = cart.length;
    }

    function showFrontFlash(message, isError = false){
      const existing = document.getElementById('frontFlashToast');
      if (existing) {
        existing.remove();
      }

      const toast = document.createElement('div');
      toast.id = 'frontFlashToast';
      toast.style.cssText = 'position:fixed;top:92px;left:50%;transform:translateX(-50%);z-index:9999;padding:1rem 1.6rem;border-radius:999px;font-size:.92rem;font-weight:600;color:#fff;box-shadow:0 10px 30px rgba(44,26,14,.25);max-width:90vw;text-align:center;';
      toast.style.background = isError
        ? 'linear-gradient(135deg,#D94040,#a82a2a)'
        : 'linear-gradient(135deg,#2E9E6A,#1e7a52)';
      toast.textContent = `${isError ? '✕' : '✓'} ${message}`;
      document.body.appendChild(toast);

      window.setTimeout(() => {
        toast.remove();
      }, 3500);
    }

    if(checkoutBtn){
      checkoutBtn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        
        if(cart.length === 0){
          window.location.href = '/orders/create-from-cart?validation_only=1';
          return;
        }

        const total = cart.reduce((sum, item)=> sum + item.price, 0);
        const cartItemsInput = document.getElementById('redirectCartItemsInput');
        const cartTotalInput = document.getElementById('redirectCartTotalInput');
        if(cartItemsInput && cartTotalInput){
          cartItemsInput.value = JSON.stringify(cart);
          cartTotalInput.value = total.toFixed(2);
        }
        
        // Hide the cart overlay
        const cartOverlay = document.getElementById('cartOverlay');
        if(cartOverlay){
          cartOverlay.classList.remove('active');
        }
        
        // Show order type selection modal
        const modal = document.getElementById('orderTypeModal');
        if(modal){
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }
      });
    }

    function removeFromCart(index){
      cart.splice(index,1);
      updateCart();
    }

    window.removeFromCart = removeFromCart;

    // ORDER TYPE MODAL
    const orderTypeModal = document.getElementById('orderTypeModal');
    const dineInBtn = document.getElementById('dineInBtn');
    const deliveryBtn = document.getElementById('deliveryBtn');
    const closeOrderTypeBtn = document.getElementById('closeOrderType');
    const orderTypeInput = document.getElementById('orderTypeInput');
    const checkoutRedirectForm = document.getElementById('checkoutRedirectForm');

    function closeOrderTypeModal(){
      if(orderTypeModal){
        orderTypeModal.style.display = 'none';
        document.body.style.overflow = '';
      }
    }

    if(closeOrderTypeBtn){
      closeOrderTypeBtn.addEventListener('click', closeOrderTypeModal);
    }
    
    if(orderTypeModal){
      orderTypeModal.addEventListener('click', (e)=>{
        // Only close if clicking the background overlay, not the inner content
        if(e.target === orderTypeModal) closeOrderTypeModal();
      });
    }

    if(dineInBtn){
      dineInBtn.addEventListener('click', ()=>{
        if (orderTypeInput) {
          orderTypeInput.value = 'DINE_IN';
        }
        if (checkoutRedirectForm) {
          checkoutRedirectForm.submit();
        }
      });
    }

    if(deliveryBtn){
      deliveryBtn.addEventListener('click', ()=>{
        if (orderTypeInput) {
          orderTypeInput.value = 'DELIVERY';
        }
        if (checkoutRedirectForm) {
          checkoutRedirectForm.submit();
        }
      });
    }

    // ESC CLOSE
    document.addEventListener('keydown', (e)=>{
      if(e.key === "Escape"){
        hideBooking();
        hideCart();
        hideProfile();
        closeOrderTypeModal();
      }
    });
});

