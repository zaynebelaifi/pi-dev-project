const renderPie = (canvasId, labels, data, colors) => {
  const canvas = document.getElementById(canvasId);
  if (!canvas || typeof window.Chart === 'undefined') {
    return;
  }

  new Chart(canvas, {
    type: 'pie',
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: colors,
          borderWidth: 1,
          borderColor: '#fff'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { boxWidth: 14, font: { size: 11 } }
        }
      }
    }
  });
};

const renderBar = (canvasId, labels, data, color, horizontal = false) => {
  const canvas = document.getElementById(canvasId);
  if (!canvas || typeof window.Chart === 'undefined') {
    return;
  }

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: color,
          borderRadius: 6,
          maxBarThickness: 36
        }
      ]
    },
    options: {
      indexAxis: horizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
};

const setupFilters = () => {
  const form = document.getElementById('analyticsFilterForm');
  if (!form) {
    return;
  }

  ['wastePeriodCombo', 'revenuePeriodCombo', 'revenueSortCombo', 'revenueFromDatePicker', 'revenueToDatePicker']
    .forEach((id) => {
      const control = document.getElementById(id);
      if (control) {
        control.addEventListener('change', () => form.submit());
      }
    });
};

const initAnalytics = () => {
  const data = window.adminAnalytics || {};
  const wasteByTypeData = data.wasteByType || {};
  const stockHealthData = data.stockHealth || {};
  const topWastedData = data.topWasted || {};
  const revenueTrendData = data.revenueTrend || {};

  renderPie(
    'wasteByTypePieChart',
    wasteByTypeData.labels || [],
    wasteByTypeData.data || [],
    ['#ef4444', '#f97316', '#f59e0b', '#14b8a6', '#3b82f6', '#8b5cf6', '#a855f7']
  );

  renderPie(
    'stockHealthPieChart',
    stockHealthData.labels || [],
    stockHealthData.data || [],
    ['#22c55e', '#eab308', '#f97316', '#ef4444', '#64748b']
  );

  renderBar(
    'topWastedIngredientsBarChart',
    topWastedData.labels || [],
    topWastedData.data || [],
    '#dc2626',
    true
  );

  renderBar(
    'revenueTrendBarChart',
    revenueTrendData.labels || [],
    revenueTrendData.data || [],
    '#b8872a',
    false
  );

  setupFilters();
};

document.addEventListener('DOMContentLoaded', initAnalytics);
