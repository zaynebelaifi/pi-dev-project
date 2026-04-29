(function () {
  const selector = '[data-admin-delete-form]';
  if (!document.querySelector(selector)) {
    return;
  }

  const getToastStack = () => {
    let stack = document.querySelector('.admin-toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'admin-toast-stack';
      document.body.appendChild(stack);
    }

    return stack;
  };

  const showToast = (message, type) => {
    const stack = getToastStack();
    const toast = document.createElement('div');
    toast.className = `admin-toast ${type === 'error' ? 'error' : 'success'}`;
    toast.textContent = message;
    stack.appendChild(toast);

    window.setTimeout(() => {
      toast.remove();
    }, 2200);
  };

  if (typeof window !== 'undefined' && typeof window.BIG4AdminToast !== 'function') {
    window.BIG4AdminToast = showToast;
  }

  const updateCounter = (table) => {
    if (!table) {
      return;
    }

    const card = table.closest('.tbl-card');
    const counter = card ? card.querySelector('.tbl-head > div:last-child') : null;
    const rows = table.querySelectorAll('tbody tr[data-event-id]');
    if (!counter) {
      return;
    }

    const singular = counter.getAttribute('data-counter-noun-singular') || 'event';
    const plural = counter.getAttribute('data-counter-noun-plural') || 'events';
    const count = rows.length;
    counter.textContent = `${count} ${count === 1 ? singular : plural}`;
  };

  const ensureEmptyState = (table) => {
    if (!table) {
      return;
    }

    const tableBody = table.querySelector('tbody');
    if (!tableBody) {
      return;
    }

    const rows = tableBody.querySelectorAll('tr[data-event-id]');
    if (rows.length > 0) {
      return;
    }

    const existingEmptyRow = tableBody.querySelector('.no-results-row');
    if (existingEmptyRow) {
      return;
    }

    const emptyRow = document.createElement('tr');
    emptyRow.className = 'no-results-row';
    const colspan = table.querySelectorAll('thead th').length || 1;
    const emptyMessage = table.getAttribute('data-empty-message') || 'No donation events found. Create the first event to start tracking donations.';
    emptyRow.innerHTML = `<td colspan="${colspan}" class="no-results">${emptyMessage}</td>`;
    tableBody.appendChild(emptyRow);
  };

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest(selector);
    if (!form) {
      return;
    }

    event.preventDefault();

    const deleteButton = form.querySelector('[data-admin-delete-button]');
    const row = form.closest('tr[data-event-id]');
    const table = row ? row.closest('table[data-live-table]') : document.querySelector('table[data-live-table]');
    const defaultLabel = deleteButton?.getAttribute('data-label-default') || 'Delete';
    const loadingLabel = deleteButton?.getAttribute('data-label-loading') || 'Deleting...';
    const deleteUrl = form.getAttribute('data-delete-url') || form.action;
    const deleteMethod = (form.getAttribute('data-delete-method') || 'POST').toUpperCase();

    if (deleteButton) {
      deleteButton.disabled = true;
      deleteButton.textContent = loadingLabel;
    }

    if (row) {
      row.classList.add('event-row-deleting');
    }

    try {
      const requestBody = new URLSearchParams(new FormData(form)).toString();
      const response = await fetch(deleteUrl, {
        method: deleteMethod,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: requestBody,
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Unable to delete this event right now.');
      }

      if (row) {
        row.classList.add('event-row-fade-out');
        window.setTimeout(() => {
          row.remove();
          updateCounter(table);
          ensureEmptyState(table);
        }, 290);
      }

      showToast(payload.message || 'Event deleted successfully.', 'success');
    } catch (error) {
      if (row) {
        row.classList.remove('event-row-deleting');
      }
      if (deleteButton) {
        deleteButton.disabled = false;
        deleteButton.textContent = defaultLabel;
      }

      const message = error instanceof Error ? error.message : 'Network error while deleting event.';
      showToast(message, 'error');
      return;
    }
  });
})();
