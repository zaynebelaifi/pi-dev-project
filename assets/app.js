
document.addEventListener('DOMContentLoaded', function() {
    // NAV SCROLL
    const nav = document.getElementById('nav');
    window.addEventListener('scroll',()=>{
      nav.classList.toggle('scrolled', window.scrollY > 40);
    });

    // SMOOTH REVEAL
    const observer = new IntersectionObserver((entries)=>{
      entries.forEach((entry)=>{
        if(entry.isIntersecting){
          entry.target.classList.add('show');
          observer.unobserve(entry.target);
        }
      });
    },{threshold:0.15});

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el=>{
      observer.observe(el);
    });

    // BOOKING POPUP
    const bookingPopup = document.getElementById('bookingPopup');
    const openBooking = document.getElementById('openBooking');
    const openBooking2 = document.getElementById('openBooking2');
    const closeBooking = document.getElementById('closeBooking');
    const bookingForm = document.getElementById('bookingForm');

    function showBooking(){
      bookingPopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideBooking(){
      bookingPopup.classList.remove('active');
      document.body.style.overflow = '';
    }

    openBooking.addEventListener('click', (e)=>{ e.preventDefault(); showBooking(); });
    openBooking2.addEventListener('click', (e)=>{ e.preventDefault(); showBooking(); });
    closeBooking.addEventListener('click', hideBooking);

    bookingPopup.addEventListener('click', (e)=>{
      if(e.target === bookingPopup) hideBooking();
    });

    bookingForm.addEventListener('submit', function(e){
      e.preventDefault();
      alert("Your booking request has been submitted successfully!");
      bookingForm.reset();
      hideBooking();
    });

    // PROFILE POPUP
    const profilePopup = document.getElementById('profilePopup');
    const openProfile = document.getElementById('openProfile');
    const closeProfile = document.getElementById('closeProfile');
    const profileForm = document.getElementById('profileForm');

    function showProfile(){
      profilePopup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideProfile(){
      profilePopup.classList.remove('active');
      document.body.style.overflow = '';
    }

    openProfile.addEventListener('click', showProfile);
    closeProfile.addEventListener('click', hideProfile);

    profilePopup.addEventListener('click', (e)=>{
      if(e.target === profilePopup) hideProfile();
    });

    profileForm.addEventListener('submit', function(e){
      e.preventDefault();
      alert("Your profile has been saved successfully!");
      profileForm.reset();
      hideProfile();
    });

    // CART SYSTEM
    const cartItemsContainer = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartCount = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    let cart = [];

    function showCart(){
      cartOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function hideCart(){
      cartOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    openCart.addEventListener('click', showCart);
    openCart2.addEventListener('click', showCart);
    closeCart.addEventListener('click', hideCart);

    cartOverlay.addEventListener('click', (e)=>{
      if(e.target === cartOverlay) hideCart();
    });

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

    function updateCart(){
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

    checkoutBtn.addEventListener('click', ()=>{
      if(cart.length === 0){
        alert("Your cart is empty.");
        return;
      }

      const total = cart.reduce((sum, item)=> sum + item.price, 0);
      document.getElementById('redirectCartItemsInput').value = JSON.stringify(cart);
      document.getElementById('redirectCartTotalInput').value = total.toFixed(2);
      document.getElementById('checkoutRedirectForm').submit();
    });

    function removeFromCart(index){
      cart.splice(index,1);
      updateCart();
    }

    window.removeFromCart = removeFromCart;

    // ESC CLOSE
    document.addEventListener('keydown', (e)=>{
      if(e.key === "Escape"){
        hideBooking();
        hideCart();
        hideProfile();
      }
    });
});

