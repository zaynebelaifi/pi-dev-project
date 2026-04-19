
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

    // BOOKING POPUP
    const bookingPopup = document.getElementById('bookingPopup');
    const openBooking = document.getElementById('openBooking');
    const openBooking2 = document.getElementById('openBooking2');
    const closeBooking = document.getElementById('closeBooking');
    const bookingForm = document.getElementById('bookingForm');

    function showBooking(){
      if (!bookingPopup) return;
      bookingPopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideBooking(){
      if (!bookingPopup) return;
      bookingPopup.classList.remove('active');
      document.body.style.overflow = '';
    }

    if (openBooking) {
      openBooking.addEventListener('click', (e)=>{ e.preventDefault(); showBooking(); });
    }
    if (openBooking2) {
      openBooking2.addEventListener('click', (e)=>{ e.preventDefault(); showBooking(); });
    }
    if (closeBooking) {
      closeBooking.addEventListener('click', hideBooking);
    }

    if (bookingPopup) {
      bookingPopup.addEventListener('click', (e)=>{
        if(e.target === bookingPopup) hideBooking();
      });
    }

    if (bookingForm) {
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
      if (!profilePopup) return;
      profilePopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideProfile(){
      if (!profilePopup) return;
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
      profilePopup.addEventListener('click', (e)=>{
        if(e.target === profilePopup) hideProfile();
      });
    }

    if (profileForm) {
      profileForm.addEventListener('submit', function(e){
        e.preventDefault();
        alert("Your profile has been saved successfully!");
        profileForm.reset();
        hideProfile();
      });
    }

    // CART SYSTEM
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
      if (!cartOverlay) return;
      cartOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideCart(){
      if (!cartOverlay) return;
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
      if (!cartItemsContainer || !cartTotal || !cartCount) return;
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

    if (checkoutBtn) {
      checkoutBtn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        
        if(cart.length === 0){
          alert("Your cart is empty.");
          return;
        }

        const total = cart.reduce((sum, item)=> sum + item.price, 0);
        const redirectCartItemsInput = document.getElementById('redirectCartItemsInput');
        const redirectCartTotalInput = document.getElementById('redirectCartTotalInput');
        if (redirectCartItemsInput) {
          redirectCartItemsInput.value = JSON.stringify(cart);
        }
        if (redirectCartTotalInput) {
          redirectCartTotalInput.value = total.toFixed(2);
        }
        
        // Hide the cart overlay
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

