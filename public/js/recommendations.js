(function () {
  const REFRESH_MS = 60000;

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function toStatusClass(status) {
    return String(status || 'SCHEDULED').toLowerCase();
  }

  function createEventCard(event, isAuthenticated) {
    const date = event.date ? new Date(`${event.date}T${event.time || '00:00'}:00`) : null;
    const day = date ? String(date.getDate()).padStart(2, '0') : '--';
    const month = date
      ? date.toLocaleString('en-US', { month: 'short' }).toUpperCase()
      : 'TBD';

    const status = String(event.status || 'SCHEDULED').toUpperCase();
    const safeReason = escapeHtml(event.reason || 'Recommended based on your preferences.');

    let actionHtml = '<span class="rec-register-btn" style="opacity:.75;pointer-events:none;">Closed</span>';

    if (!isAuthenticated) {
      actionHtml = '<a class="rec-login-btn" href="/login">Login to Register</a>';
    } else if (event.can_register && event.register_url) {
      actionHtml = `
        <button type="button" class="rec-register-btn js-ai-register-btn" data-register-url="${escapeHtml(event.register_url)}">Register</button>
      `;
    }

    return `
      <article class="recommendation-card" data-event-id="${Number(event.id)}">
        <span class="match-percentage-badge">${Number(event.match_percentage || 0)}%</span>
        <div class="recommendation-date">
          <span class="recommendation-day">${escapeHtml(day)}</span>
          <span class="recommendation-month">${escapeHtml(month)}</span>
        </div>

        <div class="recommendation-body">
          <h3>${escapeHtml(event.name || 'Donation Event')}</h3>
          <p class="recommendation-charity">🏢 ${escapeHtml(event.charity || 'Unknown charity')}</p>
          <p class="recommendation-meta">${escapeHtml(event.date || 'TBD')} ${escapeHtml(event.time || '')} · <span data-registration-count-for="${Number(event.id)}">${Number(event.registered || 0)}</span> registered</p>
          <p class="recommendation-description">${escapeHtml(event.description || '')}</p>
          <p class="recommendation-reason"><strong>Why recommended?</strong> ${safeReason}</p>

          <div class="recommendation-actions">
            <span class="rec-status-badge rec-status-${escapeHtml(toStatusClass(status))}">${escapeHtml(status)}</span>
            ${actionHtml}
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

    if (role !== 'ROLE_CLIENT') {
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
      const response = await fetch('/api/events/recommendations', {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await response.json();
      if (!response.ok || !data || data.success !== true) {
        throw new Error(data?.message || 'Unable to load recommendations.');
      }

      const recommendations = Array.isArray(data.recommendations) ? data.recommendations : [];
      if (recommendations.length === 0) {
        container.innerHTML = '<div class="recommendation-empty">No recommendation candidates found right now. Check back soon.</div>';
        return;
      }

      container.innerHTML = recommendations
        .map((event) => createEventCard(event, true))
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

    button.disabled = true;
    button.textContent = 'Registering...';

    try {
      const response = await fetch(String(button.dataset.registerUrl || ''), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload?.message || 'Registration failed.');
      }

      button.textContent = 'Registered';
      button.disabled = true;

      loadRecommendations();
    } catch (error) {
      button.textContent = 'Register';
      button.disabled = false;

      window.alert(error instanceof Error ? error.message : 'Registration failed. Please try again.');
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
})();
