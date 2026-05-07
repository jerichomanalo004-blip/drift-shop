<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Models\Order;

$db = Database::getInstance()->getConnection();
$orderModel = new Order();

// 6-month data
$months = [];
$monthlyRev = [];
$monthlyProfit = [];
$monthlyCogs = [];
for ($i = 5; $i >= 0; $i--) {
    $monthName = date('M', strtotime("-$i months"));
    $monthVal = date('Y-m', strtotime("-$i months"));
    $months[] = $monthName;
    $rev = $orderModel->getRevenueByMonth($monthVal);
    $cost = $orderModel->getCostByMonth($monthVal);
    $monthlyRev[] = $rev;
    $monthlyProfit[] = $rev - $cost;
    $monthlyCogs[] = $cost;   // COGS
}
$totalRevenue = array_sum($monthlyRev);
$totalProfit = array_sum($monthlyProfit);
$profitMargin = ($totalRevenue > 0) ? ($totalProfit / $totalRevenue) * 100 : 0;

// Brand drill-down data (keep as is)
$brandsData = [];
$brandRes = $db->query("SELECT id, brand FROM categories GROUP BY brand");
while ($b = $brandRes->fetch()) {
    $brandName = $b['brand'];
    $brandsData[$brandName]['monthly'] = [];
    for ($i = 5; $i >= 0; $i--) {
        $mVal = date('Y-m', strtotime("-$i months"));
        $stmt = $db->prepare("SELECT SUM(o.total_amount) as total 
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id
            JOIN product_variants pv ON oi.variant_id = pv.id
            JOIN products p ON pv.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE c.brand = ? AND o.status != 'Cancelled' AND o.created_at LIKE ?");
        $stmt->execute([$brandName, "$mVal%"]);
        $brandsData[$brandName]['monthly'][] = (float)$stmt->fetchColumn();
    }
    // Get categories under this brand
    $catRes = $db->prepare("SELECT id, category_name FROM categories WHERE brand = ?");
    $catRes->execute([$brandName]);
    while ($c = $catRes->fetch()) {
        $catName = $c['category_name'];
        $catMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $mVal = date('Y-m', strtotime("-$i months"));
            $stmt = $db->prepare("SELECT SUM(oi.quantity * oi.price_at_purchase) as total 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id
                JOIN product_variants pv ON oi.variant_id = pv.id
                JOIN products p ON pv.product_id = p.id
                WHERE p.category_id = ? AND o.status != 'Cancelled' AND o.created_at LIKE ?");
            $stmt->execute([$c['id'], "$mVal%"]);
            $catMonthly[] = (float)$stmt->fetchColumn();
        }
        $brandsData[$brandName]['categories'][$catName] = $catMonthly;
    }
}

// Recent sales for table
$recentSales = $db->query("SELECT orders.*, users.first_name, users.last_name 
    FROM orders JOIN users ON orders.user_id = users.id ORDER BY created_at DESC LIMIT 6")->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="top-box">
    <div class="brand-label">DRIFT / BI</div>
    <h1>Business Intelligence Terminal</h1>
</div>

<div class="main-content">
    <div class="kpi-row">
        <div class="kpi-box"><label>6-Month Revenue</label><span>₱<?= number_format($totalRevenue, 2) ?></span></div>
        <div class="kpi-box"><label>Net Profit</label><span style="color:#1a7f37;">₱<?= number_format($totalProfit, 2) ?></span></div>
        <div class="kpi-box"><label>Avg. Profit Margin</label><span><?= number_format($profitMargin, 1) ?>%</span></div>
        <div class="kpi-box"><label>Market Velocity</label><span style="color:#1a7f37;">+4.2%</span></div>
    </div>

    <div class="stats-grid">
        <div class="report-section mi-section">
            <div class="report-header" style="display:flex; justify-content:space-between;">
                <h3 id="brandChartTitle">Market Intelligence</h3>
                <button id="backBtn" style="display:none; background:#24292f; color:white; border:none; font-size:10px; padding:4px 8px; border-radius:4px; cursor:pointer;">← BACK</button>
            </div>
            <div class="report-body">
                <div class="chart-container" style="height:300px;"><canvas id="drillDownChart"></canvas></div>
                <p id="drillHint" style="font-size:10px; color:var(--text-muted); text-align:center; margin-top:10px;">Click a line point to drill into Categories</p>
            </div>
        </div>

        <div class="report-section pa-section">
            <div class="report-header"><h3>Profitability Analysis (Revenue vs. COGS)</h3></div>
            <div class="report-body">
                <div style="height:300px;"><canvas id="profitabilityChart"></canvas></div>
                <p style="font-size: 11px; color: var(--suite-text-sub); margin-top: 10px; text-align: center;">
                    Revenue minus Cost of Goods Sold reveals gross profit margin.
                </p>
            </div>
        </div>
        
        <div class="report-section rf-section">
            <div class="report-header"><h3>Recent Fulfillment</h3></div>
            <div class="report-body" style="padding:0;">
                <table class="data-table">
                    <thead>
                        <tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td>#<?= $sale['id'] ?></td>
                            <td><?= $sale['first_name'] ?></td>
                            <td>₱<?= number_format($sale['total_amount'], 0) ?></td>
                            <td><span class="badge-small <?= strtolower($sale['status']) ?>"><?= $sale['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section is-section">
            <div class="report-header"><h3>Intelligence Summary</h3></div>
            <div class="report-body">
                <p style="color: var(--suite-text-sub); font-size: 14px; line-height: 1.6; margin: 0;">
                    Analysis of the <strong>DRIFT</strong> dataset indicates a capital concentration in <strong><?= htmlspecialchars(array_key_first($brandsData) ?? 'N/A') ?></strong> assets. 
                    Peak performance was identified in <strong><?= $months[array_search(max($monthlyRev), $monthlyRev)] ?></strong>. 
                    <br><br>
                    <strong>Operational Strategy:</strong> Reallocate resources toward inventory turnover for underperforming categories to optimize cash liquidity.
                </p>
            </div>
        </div>
    </div>
    <div class="footer">DRIFT Analytical Suite © 2026 | System Status: Optimal</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const months = <?= json_encode($months) ?>;
const revenueData = <?= json_encode($monthlyRev) ?>;
const profitData = <?= json_encode($monthlyProfit) ?>;
const cogsData = <?= json_encode($monthlyCogs) ?>;
const brandsData = <?= json_encode($brandsData) ?>;

// Profitability chart (Revenue vs COGS)
new Chart(document.getElementById('profitabilityChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Revenue',
                data: revenueData,
                type: 'line',
                borderColor: '#0969da',
                backgroundColor: 'rgba(9,105,218,0.05)',
                borderWidth: 2,
                tension: 0.4,
                fill: false,
                yAxisID: 'y'
            },
            {
                label: 'COGS (Cost)',
                data: cogsData,
                type: 'bar',
                backgroundColor: 'rgba(207, 34, 46, 0.6)',
                borderColor: '#cf222e',
                borderWidth: 1,
                yAxisID: 'y'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ₱${ctx.raw.toLocaleString()}` } },
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true, ticks: { callback: (val) => '₱' + val.toLocaleString() } }
        }
    }
});

// Drill-down chart (unchanged, keep as before)
let currentView = 'brands';
let drillChart;
function renderDrillChart(viewData, title) {
    document.getElementById('brandChartTitle').innerText = title;
    const colors = ['#0969da', '#1a7f37', '#cf222e', '#8a63d2', '#d4a72c'];
    const datasets = Object.keys(viewData).map((key, idx) => ({
        label: key,
        data: (currentView === 'brands') ? viewData[key].monthly : viewData[key],
        borderColor: colors[idx % colors.length],
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6
    }));
    if (drillChart) drillChart.destroy();
    drillChart = new Chart(document.getElementById('drillDownChart'), {
        type: 'line',
        data: { labels: months, datasets: datasets },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } } },
            onClick: (e, elements) => {
                if (currentView === 'brands' && elements.length) {
                    const brand = drillChart.data.datasets[elements[0].datasetIndex].label;
                    currentView = 'categories';
                    document.getElementById('backBtn').style.display = 'block';
                    document.getElementById('drillHint').style.display = 'none';
                    renderDrillChart(brandsData[brand].categories, `Categories: ${brand}`);
                }
            }
        }
    });
}
document.getElementById('backBtn')?.addEventListener('click', () => {
    currentView = 'brands';
    document.getElementById('backBtn').style.display = 'none';
    document.getElementById('drillHint').style.display = 'block';
    renderDrillChart(brandsData, 'Market Intelligence (Brand)');
});
renderDrillChart(brandsData, 'Market Intelligence (Brand)');
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>