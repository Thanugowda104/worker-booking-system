<?php
// admin/categories.php
require_once __DIR__ . '/../includes/auth.php';
requireAuth('admin');
require_once __DIR__ . '/../includes/functions.php';
$user = getUser();
$showNavbar = true;
require_once __DIR__ . '/../includes/header.php';

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? 'bi-grid');
    if (empty($name)) { $error = 'Name is required.'; }
    else {
        $check = $pdo->prepare("SELECT id FROM service_categories WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            $error = 'Category already exists.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO service_categories (name, description, icon) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $icon]);
                flashMessage('success', 'Category added successfully.');
                redirect('http://localhost/WBS/admin/categories.php');
            } catch (Exception $e) { $error = 'Category already exists or insert failed.'; }
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM service_categories WHERE id = ?");
    $stmt->execute([$id]);
    flashMessage('danger', 'Category deleted.');
    redirect('http://localhost/WBS/admin/categories.php');
}

$stmt = $pdo->prepare("SELECT * FROM service_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<div class="container-fluid py-4">
    <h2 class="mb-4">Manage Categories</h2>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 fw-bold">Add Category</div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="mb-3"><label class="form-label">Icon (e.g. bi-brush)</label><input type="text" name="icon" class="form-control" value="bi-grid"></div>
                        <button type="submit" class="btn btn-primary w-100">Add</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Icon</th><th>Name</th><th>Description</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><i class="bi <?= $cat['icon'] ?>"></i></td>
                                <td class="fw-semibold"><?= sanitize($cat['name']) ?></td>
                                <td><small class="text-muted"><?= sanitize($cat['description']) ?></small></td>
                                <td>
                                    <a href="http://localhost/WBS/admin/categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($categories)): ?><div class="text-center py-5 text-muted">No categories yet.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>