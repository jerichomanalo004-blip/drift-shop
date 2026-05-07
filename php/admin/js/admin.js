// Helper for charts (used in dashboard and reports)
function initChart(elementId, labels, datasets, options = {}) {
    const ctx = document.getElementById(elementId);
    if (!ctx) return;
    return new Chart(ctx.getContext('2d'), {
        type: options.type || 'line',
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } } }
        }, options)
    });
}

// For reports drill-down (if needed)
function initDrillDown(months, brandsData) {
    let currentView = 'brands';
    let drillChart;
    const drillCtx = document.getElementById('drillDownChart');
    if (!drillCtx) return;
    function renderDrillChart(viewData, title) {
        document.getElementById('brandChartTitle').innerText = title;
        const colors = ['#0969da', '#1a7f37', '#cf222e', '#8a63d2', '#d4a72c'];
        const datasets = Object.keys(viewData).map((key, index) => ({
            label: key,
            data: (currentView === 'brands') ? viewData[key].monthly : viewData[key],
            borderColor: colors[index % colors.length],
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6
        }));
        if (drillChart) drillChart.destroy();
        drillChart = new Chart(drillCtx, {
            type: 'line',
            data: { labels: months, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } } },
                onClick: (e, elements) => {
                    if (currentView === 'brands' && elements.length > 0) {
                        const brandName = drillChart.data.datasets[elements[0].datasetIndex].label;
                        currentView = 'categories';
                        document.getElementById('backBtn').style.display = 'block';
                        document.getElementById('drillHint').style.display = 'none';
                        renderDrillChart(brandsData[brandName].categories, `Categories: ${brandName}`);
                    }
                }
            }
        });
    }
    renderDrillChart(brandsData, 'Market Intelligence (Brand)');
    document.getElementById('backBtn')?.addEventListener('click', () => {
        currentView = 'brands';
        document.getElementById('backBtn').style.display = 'none';
        document.getElementById('drillHint').style.display = 'block';
        renderDrillChart(brandsData, 'Market Intelligence (Brand)');
    });
}
