(function () {
  const REFRESH_MS = 60000;
  const RECOMMENDATION_ENDPOINT = '/client/food-donation/recommendations';

  function showToast(message, type) {
    if (typeof window.BIG4ShowToast === 'function') {
      window.BIG4ShowToast(message, type);
      return;
    }
    console[type === 'error' ? 'error' : 'log'](message);
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function toStatusClass(status) {
    return String(status || 'Scheduled').toLowerCase().replaceAll(' ', '-');
  }

  function createEventCard(event) {
    const date = event.eventDate ? new Date(event.eventDate) : null;
    const day = date ? String(date.getDate()).padStart(2, '0') : '--';
    const month = date
      ? date.toLocaleString('en-US', { month: 'short' }).toUpperCase()
      : 'TBD';

    const status = String(event.status || 'Scheduled');
    const safeReason = escapeHtml(event.aiReason || 'Recommended for you based on your past registrations.');
    const viewUrl = escapeHtml(event.viewUrl || `/client/food-donation/event/${Number(event.donationEventId || 0)}`);
    const match = Math.max(1, Math.min(100, Number(event.matchPercentage || 80)));
    const registerUrl = escapeHtml(event.registerUrl || '');
    const registerToken = escapeHtml(event.registerToken || '');
    const canRegister = status.toLowerCase() === 'scheduled' && registerUrl !== '' && registerToken !== '';

    return `
      <article class="recommendation-card" data-event-id="${Number(event.donationEventId || 0)}">
        <span class="match-percentage-badge">${match}%</span>
        <div class="recommendation-date">
          <span class="recommendation-day">${escapeHtml(day)}</span>
          <span class="recommendation-month">${escapeHtml(month)}</span>
        </div>

        <div class="recommendation-body">
          <h3>${escapeHtml(event.charityName || ('Event #' + Number(event.donationEventId || 0)))}</h3>
          <p class="recommendation-charity">🏢 ${escapeHtml(event.charityName || 'Unknown charity')}</p>
          <p class="recommendation-meta">${date ? escapeHtml(date.toLocaleString()) : 'TBD'} · ${Number(event.totalQuantity || 0)} items</p>
          <p class="recommendation-reason"><strong>Why recommended?</strong> ${safeReason}</p>

          <div class="recommendation-actions">
            <span class="rec-status-badge rec-status-${escapeHtml(toStatusClass(status))}">${escapeHtml(status)}</span>
            ${canRegister
              ? `<button type="button" class="rec-register-btn js-ai-register-btn" data-register-url="${registerUrl}" data-register-token="${registerToken}">Register</button>`
              : `<a class="rec-register-btn" href="${viewUrl}">View Event</a>`}
          </div>
        </div>
      </article>
    `;
  }

  async function loadRecommendations() {
    const container = document.getElementById('recommendations');
    if (!container) {
      return;
    }

    const isAuthenticated = container.dataset.authenticated === '1';
    const role = String(container.dataset.userRole || '');

    if (!isAuthenticated) {
      container.innerHTML = `
        <div class="recommendation-empty">
          Login to see personalized AI event recommendations.
          <div style="margin-top:.65rem;"><a class="rec-login-btn" href="/login">Login to Register</a></div>
        </div>
      `;
      return;
    }

    if (role !== 'ROLE_CLIENT' && role !== 'ROLE_CUSTOMER') {
      container.innerHTML = '<div class="recommendation-empty">AI recommendations are available for customer accounts only.</div>';
      return;
    }

    const existingCards = container.querySelectorAll('.recommendation-card').length;
    if (existingCards === 0) {
      container.innerHTML = `
        <div class="loading-spinner">
          <div class="spinner"></div>
          <p>Finding perfect events for you...</p>
        </div>
      `;
    }

    try {
      const response = await fetch(RECOMMENDATION_ENDPOINT, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await response.json();
      if (!response.ok || !data || data.success !== true) {
        throw new Error(data?.message || 'Unable to load recommendations.');
      }

      const recommendations = Array.isArray(data.events) ? data.events : [];
      if (recommendations.length === 0) {
        container.innerHTML = `<div class="recommendation-empty">${escapeHtml(data.message || 'No recommendations yet. Register for an event first!')}</div>`;
        return;
      }

      container.innerHTML = recommendations
        .map((event) => createEventCard(event))
        .join('');
    } catch (error) {
      console.error('Recommendations error:', error);
      container.innerHTML = '<div class="recommendation-empty">Could not load AI recommendations right now.</div>';
    }
  }

  document.addEventListener('click', async function (event) {
    const button = event.target.closest('.js-ai-register-btn');
    if (!button) {
      return;
    }

    const registerUrl = String(button.dataset.registerUrl || '');
    const registerToken = String(button.dataset.registerToken || '');
    if (!registerUrl || !registerToken) {
      showToast('Missing registration token. Please refresh and try again.', 'error');
      return;
    }

    button.disabled = true;
    button.textContent = 'Registering...';

    try {
      const payload = new URLSearchParams();
      payload.set('_token', registerToken);

      const response = await fetch(registerUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: payload.toString(),
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data?.message || 'Registration failed.');
      }

      button.textContent = 'Registered';
      button.disabled = true;
      showToast(data.message || 'You are now registered for this event.', 'success');
      document.dispatchEvent(new CustomEvent('recommendations:refresh'));
      document.dispatchEvent(new CustomEvent('registration-state-changed'));
    } catch (error) {
      button.textContent = 'Register';
      button.disabled = false;
      showToast(error instanceof Error ? error.message : 'Registration failed. Please try again.', 'error');
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('recommendations');
    if (!container) {
      return;
    }

    loadRecommendations();
    window.setInterval(loadRecommendations, REFRESH_MS);
  });

  document.addEventListener('recommendations:refresh', function () {
    loadRecommendations();
  });
})();
