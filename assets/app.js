
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

    function pulseAddToCartButton(btn){
      const defaultLabel = btn.dataset.defaultLabel || btn.textContent.trim() || 'Add to Cart';
      btn.dataset.defaultLabel = defaultLabel;
      btn.textContent = 'Added';

      window.clearTimeout(Number(btn.dataset.resetTimer || 0));
      const timer = window.setTimeout(() => {
        btn.textContent = defaultLabel;
        delete btn.dataset.resetTimer;
      }, 900);

      btn.dataset.resetTimer = String(timer);
    }

    document.addEventListener('click', function(e) {
      const btn = e.target.closest && e.target.closest('.add-cart-btn');
      if (!btn) return;

      e.preventDefault();
      const name = btn.dataset.name;
      const price = Number(btn.dataset.price);

      if(!name || Number.isNaN(price)){
        return;
      }

      cart.push({name, price});
      updateCart();
      pulseAddToCartButton(btn);
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
      cartTotal.textContent = `${total.toFixed(2)} TND`;
      cartCount.textContent = cart.length;
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
        const redirectCartItemsInput = document.getElementById('redirectCartItemsInput');
        const redirectCartTotalInput = document.getElementById('redirectCartTotalInput');
        if (redirectCartItemsInput) {
          redirectCartItemsInput.value = JSON.stringify(cart);
        }
        if (redirectCartTotalInput) {
          redirectCartTotalInput.value = total.toFixed(2);
        }

        if(cartOverlay){
          cartOverlay.classList.remove('active');
        }

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
        if(e.target === orderTypeModal) closeOrderTypeModal();
      });
    }

    if(dineInBtn){
      dineInBtn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        closeOrderTypeModal();
        const cartItemsInput = document.getElementById('redirectCartItemsInput');
        const cartTotalInput = document.getElementById('redirectCartTotalInput');
        if(cartItemsInput && cartTotalInput && cartItemsInput.value){
          window.location.href = `/orders/create-from-cart?cart_items=${encodeURIComponent(cartItemsInput.value)}&order_total=${encodeURIComponent(cartTotalInput.value)}&order_type=DINE_IN&payment_method=CASH`;
        }
      });
    }

    if(deliveryBtn){
      deliveryBtn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        closeOrderTypeModal();
        if(checkoutRedirectForm){
          checkoutRedirectForm.submit();
        }
      });
    }

    // AI REVIEWS LOADER
    const aiReviewsSection = document.getElementById('ai-reviews');
    const aiReviewsGrid = document.getElementById('aiReviewsGrid');
    const aiReviewsEmpty = document.getElementById('aiReviewsEmpty');
    const aiReviewsPageIndicator = document.getElementById('aiReviewsPageIndicator');
    const aiReviewFilterButtons = Array.from(document.querySelectorAll('[data-ai-filter]'));
    const aiReviewPageButtons = Array.from(document.querySelectorAll('[data-ai-page-action]'));
    const aiReviewToggleButtons = Array.from(document.querySelectorAll('[data-ai-view]'));
    const aiReviewState = {
      testimonials: [],
      filtered: [],
      filter: 'all',
      page: 1,
      perPage: 3,
    };

    function normalizeReviewText(review){
      return `${review.review_text || ''} ${review.summary || ''}`.toLowerCase();
    }

    function deriveMentions(review){
      const text = normalizeReviewText(review);
      const mentions = [];
      const keywords = [
        ['fast delivery', 'fast delivery'],
        ['delivery', 'delivery'],
        ['packaging', 'packaging'],
        ['ambiance', 'ambiance'],
        ['dessert', 'dessert'],
        ['service', 'service'],
        ['brunch', 'brunch'],
        ['coffee', 'coffee'],
        ['comfort', 'comfort'],
        ['menu', 'menu'],
        ['chef', 'chef'],
        ['pacing', 'pacing'],
      ];

      keywords.forEach(([needle, label]) => {
        if (text.includes(needle) && !mentions.includes(label)) {
          mentions.push(label);
        }
      });

      return mentions.slice(0, 3);
    }

    function getStars(rating){
      const rounded = Math.round(Number(rating) || 0);
      return Array.from({length: 5}, (_, index) => index < rounded ? '★' : '☆').join('');
    }

    function matchesFilter(review, filter){
      if(filter === 'all') return true;

      const rating = Math.round(Number(review.rating) || 0);
      const text = normalizeReviewText(review);

      if(filter === '5') return rating >= 5;
      if(filter === '4') return rating === 4;
      if(filter === 'fast') return text.includes('fast') || text.includes('delivery');
      if(filter === 'packaging') return text.includes('packaging') || text.includes('boxed');

      return true;
    }

    function renderAiReviews(){
      if(!aiReviewsGrid) return;

      const totalPages = Math.max(1, Math.ceil(aiReviewState.filtered.length / aiReviewState.perPage));
      const page = Math.min(aiReviewState.page, totalPages);
      aiReviewState.page = page;
      const startIndex = (page - 1) * aiReviewState.perPage;
      const visibleReviews = aiReviewState.filtered.slice(startIndex, startIndex + aiReviewState.perPage);

      if(aiReviewsPageIndicator){
        aiReviewsPageIndicator.textContent = `Page ${page} of ${totalPages}`;
      }

      if(!visibleReviews.length){
        aiReviewsGrid.innerHTML = '';
        if(aiReviewsEmpty) aiReviewsEmpty.style.display = 'block';
        return;
      }

      if(aiReviewsEmpty) aiReviewsEmpty.style.display = 'none';
      aiReviewsGrid.innerHTML = visibleReviews.map((review) => {
        const rating = Number(review.rating) || 0;
        const initial = (review.customer_name || 'G').trim().charAt(0).toUpperCase();
        const title = review.summary || 'Guest review';
        const comment = review.review_text || '';
        const tags = deriveMentions(review);

        return `
          <article class="ai-review-card reveal">
            <div class="ai-review-head">
              <div class="ai-review-profile">
                <div class="ai-review-avatar">${initial}</div>
                <div>
                  <h4 class="ai-review-name">${review.customer_name || 'Guest'}</h4>
                  <div class="ai-review-stars" aria-label="${rating} stars">${getStars(rating)}</div>
                </div>
              </div>
              <span class="ai-review-badge">AI Verified</span>
            </div>
            <h4 class="ai-review-title">${title}</h4>
            <p class="ai-review-comment">${comment}</p>
            <div class="ai-review-summary">${review.summary || ''}</div>
            <div class="ai-review-tags">
              <span class="ai-review-tag">${Math.round(rating)} Stars</span>
              <span class="ai-review-tag">Verified</span>
              ${tags.map(tag => `<span class="ai-review-tag">${tag}</span>`).join('')}
            </div>
          </article>
        `;
      }).join('');
    }

    function setAiFilter(filter){
      aiReviewState.filter = filter;
      aiReviewState.page = 1;
      aiReviewState.filtered = aiReviewState.testimonials.filter(review => matchesFilter(review, filter));
      renderAiReviews();
    }

    function setAiPage(delta){
      const totalPages = Math.max(1, Math.ceil(aiReviewState.filtered.length / aiReviewState.perPage));
      aiReviewState.page = Math.min(totalPages, Math.max(1, aiReviewState.page + delta));
      renderAiReviews();
    }

    aiReviewFilterButtons.forEach(button => {
      button.addEventListener('click', () => {
        aiReviewFilterButtons.forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        setAiFilter(button.dataset.aiFilter || 'all');
      });
    });

    aiReviewPageButtons.forEach(button => {
      button.addEventListener('click', () => {
        const action = button.dataset.aiPageAction;
        if(action === 'next') setAiPage(1);
        if(action === 'prev') setAiPage(-1);
      });
    });

    aiReviewToggleButtons.forEach(button => {
      button.addEventListener('click', () => {
        aiReviewToggleButtons.forEach(item => item.classList.remove('active'));
        button.classList.add('active');
      });
    });

    async function loadAiReviews(){
      if(!aiReviewsSection || !aiReviewsGrid) return;

      try {
        const response = await fetch('/feedback/testimonials', {
          headers: { 'Accept': 'application/json' },
        });

        if(!response.ok){
          throw new Error(`API returned ${response.status}`);
        }

        const testimonials = await response.json();
        aiReviewState.testimonials = Array.isArray(testimonials) ? testimonials : [];
        aiReviewState.filtered = aiReviewState.testimonials.slice();
        aiReviewState.page = 1;

        if(!aiReviewState.testimonials.length){
          showFallbackReviews();
          return;
        }

        renderAiReviews();
      } catch(error) {
        console.error('AI reviews error:', error);
        showFallbackReviews();
      }
    }

    function showFallbackReviews(){
      aiReviewState.testimonials = [
        {
          customer_name: 'Amira',
          review_text: 'Super fast delivery and elegant packaging.',
          summary: 'Fast delivery with premium presentation.',
          rating: 5,
        },
        {
          customer_name: 'Zayd',
          review_text: 'Coffee arrived hot and beautifully boxed.',
          summary: 'Warm coffee and beautiful boxing.',
          rating: 5,
        },
        {
          customer_name: 'Salma',
          review_text: 'Quick service with a smooth handoff.',
          summary: 'Efficient handoff and quick service.',
          rating: 4,
        },
      ];
      aiReviewState.filtered = aiReviewState.testimonials.slice();
      aiReviewState.page = 1;
      renderAiReviews();
    }

    loadAiReviews();

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
      if(!name || Number.isNaN(price)){
        return;
      }

      cart.push({name, price});
      updateCart();
      pulseAddToCartButton(btn);
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
        console.log('Checkout button clicked. Cart length:', cart.length);
        
        if(cart.length === 0){
          console.log('Cart is empty');
          window.location.href = '/orders/create-from-cart?validation_only=1';
          return;
        }

        const total = cart.reduce((sum, item)=> sum + item.price, 0);
        const cartItemsInput = document.getElementById('redirectCartItemsInput');
        const cartTotalInput = document.getElementById('redirectCartTotalInput');
        console.log('Cart items:', cart);
        console.log('Total:', total);
        console.log('Modal element:', document.getElementById('orderTypeModal'));
        
        if(cartItemsInput && cartTotalInput){
          cartItemsInput.value = JSON.stringify(cart);
          cartTotalInput.value = total.toFixed(2);
          console.log('Form values set');
        }
        
        // Hide the cart overlay
        const cartOverlay = document.getElementById('cartOverlay');
        if(cartOverlay){
          cartOverlay.classList.remove('active');
          console.log('Cart overlay hidden');
        }
        
        // Show order type selection modal
        const modal = document.getElementById('orderTypeModal');
        console.log('Attempting to show modal:', modal);
        if(modal){
          modal.style.display = 'flex';
          document.body.style.overflow = 'hidden';
          console.log('Modal displayed');
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
    
    // Debug: log button availability
    console.log('Order modal elements found:', {
      orderTypeModal: !!orderTypeModal,
      dineInBtn: !!dineInBtn,
      deliveryBtn: !!deliveryBtn,
      closeOrderTypeBtn: !!closeOrderTypeBtn,
      checkoutRedirectForm: !!checkoutRedirectForm
    });

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
        // Only close if clicking the background overlay, not the inner content or any button
        const modalContent = orderTypeModal.querySelector('div');
        if(e.target === orderTypeModal || (e.target.tagName !== 'BUTTON' && !modalContent.contains(e.target))) {
          closeOrderTypeModal();
        }
      });
    }

    if(dineInBtn){
      dineInBtn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        console.log('=== DINE IN BUTTON CLICKED ===');
        
        const cartItemsInput = document.getElementById('redirectCartItemsInput');
        const cartTotalInput = document.getElementById('redirectCartTotalInput');
        
        if(cartItemsInput && cartTotalInput && cartItemsInput.value){
          closeOrderTypeModal();
          const redirectUrl = `/orders/create-from-cart?cart_items=${encodeURIComponent(cartItemsInput.value)}&order_total=${encodeURIComponent(cartTotalInput.value)}&order_type=DINE_IN&payment_method=CASH`;
          console.log('Dine In redirect to:', redirectUrl);
          window.location.href = redirectUrl;
        } else {
          console.log('ERROR: Cart data not found');
        }
      });
    }

    if(deliveryBtn){
      deliveryBtn.addEventListener('click', (e)=>{
        e.preventDefault();
        e.stopPropagation();
        console.log('=== DELIVERY BUTTON CLICKED ===');
        
        closeOrderTypeModal();
        if (checkoutRedirectForm) {
          console.log('Submitting delivery form to:', checkoutRedirectForm.action);
          checkoutRedirectForm.submit();
        } else {
          console.log('ERROR: checkoutRedirectForm not found!');
        }
      });
    }

    // AI REVIEWS LOADER
    async function initAIReviews(){
      const aiReviewsSection = document.getElementById('ai-reviews');
      const aiReviewsGrid = document.getElementById('aiReviewsGrid');
      const aiReviewsEmpty = document.getElementById('aiReviewsEmpty');
      
      if(!aiReviewsSection) return;
      
      try {
        const response = await fetch('/feedback/testimonials');
        if(!response.ok) throw new Error(`API returned ${response.status}`);
        
        const testimonials = await response.json();
        console.log('Loaded testimonials:', testimonials);
        
        if(!testimonials || testimonials.length === 0){
          aiReviewsGrid.innerHTML = '';
          aiReviewsEmpty.style.display = 'block';
          return;
        }
        
        // Render testimonials
        aiReviewsGrid.innerHTML = testimonials.map((item) => {
          const rating = item.rating || 5;
          const stars = '⭐'.repeat(rating);
          const summary = item.summary || item.review_text || '';
          const title = item.event_name || item.customer_name || 'Guest Review';
          
          return `
            <article class="ai-review-card reveal">
              <div class="ai-review-head">
                <span class="ai-review-badge">AI Summary</span>
                <span class="ai-review-score">${rating}.0</span>
              </div>
              <h4>${title}</h4>
              <p>${summary}</p>
              <div class="ai-review-meta">${stars}</div>
            </article>
          `;
        }).join('');
        
        aiReviewsEmpty.style.display = 'none';
      } catch(error) {
        console.error('Failed to load AI reviews:', error);
        // Show fallback reviews if API fails
        showFallbackReviews();
      }
    }
    
    function showFallbackReviews(){
      const aiReviewsGrid = document.getElementById('aiReviewsGrid');
      const fallbackReviews = [
        {
          title: 'Jazz Night Tasting',
          summary: 'Guests praised the live band and the signature dessert pairing. Service was quick and attentive.',
          rating: 4.9,
          mentions: 'ambiance, dessert, service'
        },
        {
          title: 'Weekend Brunch',
          summary: 'Favorites included the truffle omelette and chilled brew. Seating comfort was highlighted.',
          rating: 4.7,
          mentions: 'brunch, comfort, coffee'
        },
        {
          title: 'Chef Table Experience',
          summary: 'Guests loved the curated menu flow and personal chef touch. Pacing was smooth.',
          rating: 4.8,
          mentions: 'menu, chef, pacing'
        }
      ];
      
      aiReviewsGrid.innerHTML = fallbackReviews.map((review) => `
        <article class="ai-review-card reveal">
          <div class="ai-review-head">
            <span class="ai-review-badge">AI Summary</span>
            <span class="ai-review-score">${review.rating}</span>
          </div>
          <h4>${review.title}</h4>
          <p>${review.summary}</p>
          <div class="ai-review-meta">Top mentions: ${review.mentions}</div>
        </article>
      `).join('');
    }
    
    // Initialize AI reviews when page loads
    initAIReviews();
    
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

