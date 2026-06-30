<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth('admin');
require_once __DIR__ . '/../includes/functions.php';
$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as c FROM bookings");
$stats['total'] = $stmt->fetch()['c'];

foreach (['completed','pending','cancelled','accepted','rejected'] as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE status = ?");
    $stmt->execute([$s]);
    $stats[$s] = $stmt->fetch()['c'];
}
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as rev FROM bookings WHERE status = 'completed'");
$stats['revenue'] = $stmt->fetch()['rev'];

$stmt = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM bookings GROUP BY DATE(created_at) ORDER BY d ASC LIMIT 30");
$chartData = $stmt->fetchAll();
$chartLabels = json_encode(array_column($chartData, 'd'));
$chartValues = json_encode(array_column($chartData, 'c'));

$stmt = $pdo->query("SELECT status, COUNT(*) as c FROM bookings GROUP BY status");
$statusData = $stmt->fetchAll();
$statusLabels = json_encode(array_column($statusData, 'status'));
$statusValues = json_encode(array_column($statusData, 'c'));
?>
<div class="container-fluid py-4">
    <h2 class="mb-4">Admin Dashboard</h2>
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-briefcase fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['total'] ?></h3>
                    <p class="text-muted mb-0">Total Jobs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['completed'] ?></h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clock fs-1 text-warning mb-2"></i>
                    <h3 class="fw-bold"><?= $stats['pending'] ?></h3>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-currency-rupee fs-1 text-success mb-2"></i>
                    <h3 class="fw-bold"><?= formatCurrency($stats['revenue']) ?></h3>
                    <p class="text-muted mb-0">Revenue</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 fw-bold">Bookings Over Time</div>
                <div class="card-body">
                    <canvas id="bookingsLineChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 fw-bold">By Status</div>
                <div class="card-body">
                    <canvas id="statusDonutChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $extraJS = '
new Chart(document.getElementById("bookingsLineChart"), {
    type: "line",
    data: { labels: ' . $chartLabels . ', datasets: [{ label: "Bookings", data: ' . $chartValues . ', borderColor: "#2563eb", backgroundColor: "rgba(37,99,235,0.1)", fill: true, tension: 0.3 }] },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
new Chart(document.getElementById("statusDonutChart"), {
    type: "doughnut",
    data: { labels: ' . $statusLabels . ', datasets: [{ data: ' . $statusValues . ', backgroundColor: ["#f59e0b","#3b82f6","#ef4444","#10b981","#6b7280"] }] },
    options: { responsive: true, plugins: { legend: { position: "bottom" } } }
});
';
require_once __DIR__ . '/../includes/footer.php'; ?>