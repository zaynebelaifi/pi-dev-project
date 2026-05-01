(function () {
  const REFRESH_MS = 60000;
  const RECOMMENDATION_ENDPOINT = '/client/food-donation/recommendations';
  let recommendationsRequestController = null;

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

  function removeRecommendationCard(eventId) {
    const card = document.querySelector(`.recommendation-card[data-event-id="${Number(eventId || 0)}"]`);
    if (!card) {
      return;
    }

    const container = card.closest('[data-recommended-events]');
    card.style.transition = 'opacity 0.22s ease, transform 0.22s ease';
    card.style.opacity = '0';
    card.style.transform = 'translateY(8px)';

    window.setTimeout(() => {
      card.remove();
      if (container && container.querySelectorAll('.recommendation-card').length === 0) {
        container.innerHTML = '<div class="recommendation-empty">No more AI recommendations right now.</div>';
      }
    }, 230);
  }

  function setRegisterButtonLoading(button, isLoading, loadingText, defaultText) {
    if (!button) {
      return;
    }

    const fallbackDefault = String(defaultText || button.dataset.defaultText || button.textContent || 'Register');
    if (!button.dataset.defaultText) {
      button.dataset.defaultText = fallbackDefault;
    }

    if (isLoading) {
      button.disabled = true;
      button.classList.add('is-registering');
      button.textContent = String(loadingText || 'Registering...');
      return;
    }

    button.classList.remove('is-registering');
    button.disabled = false;
    button.textContent = fallbackDefault;
  }

  function setRecommendationsUpdating(container, isUpdating, message) {
    if (!container) {
      return;
    }

    const existingLoader = container.querySelector('.recommendations-inline-loader');

    if (!isUpdating) {
      container.classList.remove('is-updating');
      if (existingLoader) {
        existingLoader.remove();
      }
      return;
    }

    container.classList.add('is-updating');
    if (existingLoader) {
      existingLoader.querySelector('.loader-text').textContent = String(message || 'Updating recommendations...');
      return;
    }

    const loader = document.createElement('div');
    loader.className = 'recommendations-inline-loader';
    loader.innerHTML = `<span class="spinner" aria-hidden="true"></span><span class="loader-text">${escapeHtml(message || 'Updating recommendations...')}</span>`;
    container.prepend(loader);
  }

  async function loadRecommendations(options) {
    const opts = options || {};
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
    } else if (opts.forceLoading === true) {
      setRecommendationsUpdating(container, true, opts.instant === true ? 'Refreshing recommendations...' : 'Updating recommendations...');
    }

    if (recommendationsRequestController) {
      recommendationsRequestController.abort();
    }

    recommendationsRequestController = new AbortController();

    try {
      const endpoint = opts.instant === true ? `${RECOMMENDATION_ENDPOINT}?instant=1` : RECOMMENDATION_ENDPOINT;
      const response = await fetch(endpoint, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        signal: recommendationsRequestController.signal,
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
      container.classList.add('recommendations-fade-in');
      window.setTimeout(() => container.classList.remove('recommendations-fade-in'), 240);
    } catch (error) {
      if (error && error.name === 'AbortError') {
        return;
      }
      console.error('Recommendations error:', error);
      container.innerHTML = '<div class="recommendation-empty">Could not load AI recommendations right now.</div>';
    } finally {
      setRecommendationsUpdating(container, false);
      recommendationsRequestController = null;
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

    const defaultLabel = String(button.textContent || 'Register');
    setRegisterButtonLoading(button, true, 'Registering...', defaultLabel);

    const recommendationCard = button.closest('.recommendation-card');
    const eventId = Number(recommendationCard?.dataset.eventId || 0);

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

      button.classList.remove('is-registering');
      button.textContent = 'Registered';
      button.disabled = true;

      if (eventId > 0) {
        removeRecommendationCard(eventId);
      }

      showToast(data.message || 'You are now registered for this event.', 'success');

      document.dispatchEvent(new CustomEvent('registration-state-changed', {
        detail: {
          eventId,
          registrationCount: Number(data?.registration_count || 0),
          event: data?.registered_event || null,
        },
      }));

      document.dispatchEvent(new CustomEvent('recommendations:refresh', {
        detail: {
          instant: true,
          forceLoading: true,
        },
      }));
    } catch (error) {
      setRegisterButtonLoading(button, false, '', defaultLabel);
      showToast(error instanceof Error ? error.message : 'Registration failed. Please try again.', 'error');
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('recommendations');
    if (!container) {
      return;
    }

    loadRecommendations();
    window.setInterval(() => loadRecommendations(), REFRESH_MS);
  });

  document.addEventListener('recommendations:refresh', function (event) {
    loadRecommendations(event?.detail || {});
  });
})();
