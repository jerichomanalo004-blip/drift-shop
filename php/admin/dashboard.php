<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../../config/autoload.php';

use Core\Database;
use Models\Product;
use Models\Order;

$db = Database::getInstance()->getConnection();
$adminName = $_SESSION['user_name'] ?? 'Administrator';

$productModel = new Product();
$orderModel = new Order();

// Stats
$totalProducts = (int) $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = (int) $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalRevenue = $orderModel->getTotalRevenue();
$totalCost = $orderModel->getTotalCost();
$profit = $totalRevenue - $totalCost;
$margin = ($totalRevenue > 0) ? ($profit / $totalRevenue) * 100 : 0;
$pendingOrders = $orderModel->getPendingCount();
$lowStockCount = $productModel->getLowStockCount();

// Monthly data for charts (6 months)
$months = [];
$monthlyRev = [];
$monthlyProfit = [];
for ($i = 5; $i >= 0; $i--) {
    $monthName = date('M', strtotime("-$i months"));
    $monthVal = date('Y-m', strtotime("-$i months"));
    $months[] = $monthName;
    $rev = $orderModel->getRevenueByMonth($monthVal);
    $costM = $orderModel->getCostByMonth($monthVal);
    $monthlyRev[] = $rev;
    $monthlyProfit[] = $rev - $costM;
}

// Get current year and month for monthly metrics
$currentYear = date('Y');
$currentMonth = date('m');
$currentMonthName = date('F Y');

// Category sales performance (current month only)
$catPerf = $orderModel->getCategorySalesByMonth($currentYear, $currentMonth);
// Brand sales (current month only)
$brandSales = $orderModel->getBrandSalesByMonth($currentYear, $currentMonth);
// Recent orders
$recentOrders = $orderModel->getRecentOrders(5);

include __DIR__ . '/includes/header.php';
?>
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">₱<?= number_format($profit, 2) ?></div>
        <div class="stat-label">Net Profit</div>
        <div class="stat-sub">Margin: <?= number_format($margin, 1) ?>%</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $pendingOrders ?></div>
        <div class="stat-label">Pending Orders</div>
    </div>
    <div class="stat-card">
        <div class="stat-value <?= $lowStockCount > 0 ? 'warning' : '' ?>"><?= $lowStockCount ?></div>
        <div class="stat-label">Low Stock Items</div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Revenue Trajectory - spans columns 1 and 2, row 1 -->
    <div class="intel-card revenue-card">
        <h3>Revenue Trajectory</h3>
        <div style="height: 300px;"><canvas id="dashboardRevenueChart"></canvas></div>
    </div>

    <!-- Efficiency Metrics - spans rows 1 and 2, column 3 -->
    <div class="intel-card efficiency-card">
        <h3>Efficiency Metrics</h3>
        <p class="small-muted">Category sales for <?= $currentMonthName ?> (goal: 100 units).</p>
        <div class="progress-list">
            <?php foreach ($catPerf as $cat): 
                $units = $cat['units'];
                $percent = min(100, ($units / 100) * 100);
                $goalReached = $units >= 100;
                $barColor = $goalReached ? '#1a7f37' : '#0969da';
            ?>
            <div class="progress-item">
                <div class="progress-label">
                    <?= htmlspecialchars($cat['category_name']) ?>
                    <?php if ($goalReached): ?>
                        <span style="margin-left: 8px; font-size: 12px; background: #1a7f37; color: white; padding: 2px 6px; border-radius: 3px; font-weight: 600;">✓ Goal</span>
                    <?php endif; ?>
                </div>
                <div class="progress-bar"><div style="width: <?= $percent ?>%; background: <?= $barColor ?>;"></div></div>
                <div class="progress-value"><?= $units ?> / 100 units</div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 30px;">
            <h3>Monthly Brand Sales - <?= $currentMonthName ?> (goal: 100 units)</h3>
            <?php foreach ($brandSales as $brand): 
                $units = $brand['units'];
                $percent = min(100, ($units / 100) * 100);
                $goalReached = $units >= 100;
                $barColor = $goalReached ? '#1a7f37' : '#1f6feb';
            ?>
            <div class="brand-item" style="flex-direction: column; align-items: stretch; gap: 6px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="brand-name"><?= htmlspecialchars($brand['brand']) ?></span>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="brand-count"><?= $units ?> / 100 units</span>
                        <?php if ($goalReached): ?>
                            <span style="font-size: 12px; background: #1a7f37; color: white; padding: 2px 6px; border-radius: 3px; font-weight: 600;">✓ Goal</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="progress-bar" style="width: 100%;"><div style="width: <?= $percent ?>%; height: 6px; background: <?= $barColor ?>; border-radius: 6px;"></div></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- NEW: Intelligence-style summary at the bottom with extra space -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-subtle);">
            <h3 style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px;">Efficiency Insight</h3>
            <p style="color: var(--text-muted); font-size: 12px; line-height: 1.5; margin: 0;">
                <?php
                // Calculate average category performance relative to target (monthly)
                $totalCatPercent = 0;
                $catCount = count($catPerf);
                $categoriesReachedGoal = 0;
                foreach ($catPerf as $cat) {
                    $catPercent = min(100, ($cat['units'] / 100) * 100);
                    $totalCatPercent += $catPercent;
                    if ($cat['units'] >= 100) $categoriesReachedGoal++;
                }
                $avgCatPercent = $catCount > 0 ? round($totalCatPercent / $catCount) : 0;
                
                // Find best and worst performing categories this month
                $bestCat = !empty($catPerf) ? $catPerf[0]['category_name'] : 'N/A';
                $worstCatArray = !empty($catPerf) ? end($catPerf) : null;
                $worstCat = $worstCatArray ? $worstCatArray['category_name'] : 'N/A';
                
                // Brand metrics
                $brandCount = count($brandSales);
                $brandsReachedGoal = 0;
                foreach ($brandSales as $brand) {
                    if ($brand['units'] >= 100) $brandsReachedGoal++;
                }
                ?>
                This month, categories are achieving <strong><?= $avgCatPercent ?>%</strong> of the 100‑unit target on average (<strong><?= $categoriesReachedGoal ?>/<?= $catCount ?></strong> at goal). 
                <strong><?= htmlspecialchars($bestCat) ?></strong> leads in sales, while <strong><?= htmlspecialchars($worstCat) ?></strong> shows the lowest traction. 
                <strong><?= $brandsReachedGoal ?>/<?= $brandCount ?></strong> brands have reached the monthly sales target of 100 units.
            </p>
        </div>
    </div>

    <!-- Recent Orders - spans columns 1 and 2, row 2 -->
    <div class="intel-card recent-card">
        <h3>Recent Orders</h3>
        <table class="data-table">
            <thead>
                <tr><th>Order ID</th><th>Date</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                    <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                    <td><span class="status-badge <?= strtolower($order['status']) ?>"><?= $order['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const months = <?= json_encode($months) ?>;
const revenueData = <?= json_encode($monthlyRev) ?>;
const profitData = <?= json_encode($monthlyProfit) ?>;

document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('dashboardRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenueData,
                    borderColor: '#1f2328',
                    backgroundColor: 'rgba(31, 35, 40, 0.05)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: false
                },
                {
                    label: 'Net Profit',
                    data: profitData,
                    borderColor: '#1a7f37',
                    backgroundColor: 'rgba(26, 127, 55, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 8,
                        usePointStyle: true,
                        font: { size: 10, family: 'Inter' }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f2f5' },
                    ticks: {
                        font: { size: 10 },
                        callback: (value) => '₱' + value.toLocaleString()
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
});
</script>
<?php
?>