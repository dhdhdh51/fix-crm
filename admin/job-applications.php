<?php
/**
 * Admin — Job Applications
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/functions.php';
requireAdmin();

$db = db();

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS `job_applications` (
    `id`          int(11)      NOT NULL AUTO_INCREMENT,
    `job_id`      int(11)      NOT NULL,
    `job_title`   varchar(200) NOT NULL,
    `name`        varchar(100) NOT NULL,
    `email`       varchar(150) DEFAULT NULL,
    `phone`       varchar(20)  NOT NULL,
    `message`     text         DEFAULT NULL,
    `resume_path` varchar(500) DEFAULT NULL,
    `status`      enum('new','reviewed','contacted','rejected') DEFAULT 'new',
    `applied_at`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_job_id` (`job_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Handle POST: status update or delete ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Security token expired.'];
        header('Location: ' . ADMIN_URL . '/job-applications.php'); exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $appId  = (int)($_POST['app_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['new','reviewed','contacted','rejected'];
        if ($appId && in_array($status, $allowed)) {
            $db->prepare("UPDATE job_applications SET status=? WHERE id=?")->execute([$status, $appId]);
        }
        // AJAX response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success'=>true]); exit;
        }
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Status updated.'];
    }

    if ($action === 'delete') {
        $appId = (int)($_POST['app_id'] ?? 0);
        // Delete resume file too
        $row = $db->prepare("SELECT resume_path FROM job_applications WHERE id=?");
        $row->execute([$appId]);
        $resumePath = $row->fetchColumn();
        if ($resumePath) {
            $uploadsRoot = realpath(SITE_ROOT . '/uploads');
            $fullPath    = realpath(SITE_ROOT . '/' . ltrim($resumePath, '/'));
            if ($uploadsRoot && $fullPath && str_starts_with($fullPath, $uploadsRoot . DIRECTORY_SEPARATOR) && is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
        $db->prepare("DELETE FROM job_applications WHERE id=?")->execute([$appId]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Application deleted.'];
    }

    header('Location: ' . ADMIN_URL . '/job-applications.php' . (isset($_GET['job']) ? '?job=' . (int)$_GET['job'] : '')); exit;
}

// ── Filters ──
$filterJob    = (int)($_GET['job']    ?? 0);
$filterStatus = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($filterJob)    { $where[] = 'a.job_id = ?';          $params[] = $filterJob; }
if ($filterStatus) { $where[] = 'a.status = ?';           $params[] = $filterStatus; }
if ($search)       { $where[] = '(a.name LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)';
                     $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cStmt = $db->prepare("SELECT COUNT(*) FROM job_applications a $whereSQL");
$cStmt->execute($params); $total = (int)$cStmt->fetchColumn();
$pages = ceil($total / $perPage);

$qStmt = $db->prepare("SELECT a.* FROM job_applications a $whereSQL ORDER BY a.applied_at DESC LIMIT ? OFFSET ?");
$idx = 1;
foreach ($params as $p) $qStmt->bindValue($idx++, $p);
$qStmt->bindValue($idx++, (int)$perPage, PDO::PARAM_INT);
$qStmt->bindValue($idx, (int)$offset, PDO::PARAM_INT);
$qStmt->execute();
$applications = $qStmt->fetchAll();

// All jobs for filter dropdown
$allJobs = $db->query("SELECT id, title FROM job_postings ORDER BY title")->fetchAll();

// Stats
$stats = [
    'total'     => (int)$db->query("SELECT COUNT(*) FROM job_applications")->fetchColumn(),
    'new'       => (int)$db->query("SELECT COUNT(*) FROM job_applications WHERE status='new'")->fetchColumn(),
    'reviewed'  => (int)$db->query("SELECT COUNT(*) FROM job_applications WHERE status='reviewed'")->fetchColumn(),
    'contacted' => (int)$db->query("SELECT COUNT(*) FROM job_applications WHERE status='contacted'")->fetchColumn(),
    'rejected'  => (int)$db->query("SELECT COUNT(*) FROM job_applications WHERE status='rejected'")->fetchColumn(),
];

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$csrf       = generateCSRF();
$pageTitle  = 'Job Applications';
$activePage = 'job-applications';
require_once __DIR__ . '/layout-header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-file-alt" style="color:var(--maroon)"></i> Job Applications</h1>
        <p>Review and manage all submitted job applications</p>
    </div>
    <a href="<?= ADMIN_URL ?>/jobs.php" class="btn btn-outline">
        <i class="fas fa-briefcase"></i> Manage Job Postings
    </a>
</div>

<!-- Stats -->
<div class="stats-row" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fas fa-users"></i></div>
        <div><div class="stat-card-value"><?= $stats['total'] ?></div><div class="stat-card-label">Total Applications</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon orange"><i class="fas fa-envelope"></i></div>
        <div><div class="stat-card-value"><?= $stats['new'] ?></div><div class="stat-card-label">New / Unread</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon gold"><i class="fas fa-eye"></i></div>
        <div><div class="stat-card-value"><?= $stats['reviewed'] ?></div><div class="stat-card-label">Reviewed</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="fas fa-phone"></i></div>
        <div><div class="stat-card-value"><?= $stats['contacted'] ?></div><div class="stat-card-label">Contacted</div></div>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:18px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search name, email, phone..." class="form-control" style="max-width:220px;padding:8px 12px">
            <select name="job" class="form-control" style="max-width:220px;padding:8px 12px">
                <option value="">All Positions</option>
                <?php foreach ($allJobs as $j): ?>
                <option value="<?= $j['id'] ?>" <?= $filterJob === (int)$j['id'] ? 'selected' : '' ?>><?= htmlspecialchars($j['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="max-width:150px;padding:8px 12px">
                <option value="">All Status</option>
                <option value="new"       <?= $filterStatus==='new'       ?'selected':'' ?>>New</option>
                <option value="reviewed"  <?= $filterStatus==='reviewed'  ?'selected':'' ?>>Reviewed</option>
                <option value="contacted" <?= $filterStatus==='contacted' ?'selected':'' ?>>Contacted</option>
                <option value="rejected"  <?= $filterStatus==='rejected'  ?'selected':'' ?>>Rejected</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($search || $filterJob || $filterStatus): ?>
            <a href="<?= ADMIN_URL ?>/job-applications.php" class="btn btn-gray">Clear</a>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:13px;color:var(--text-muted)"><?= $total ?> applications</span>
        </form>
    </div>
</div>

<!-- Applications Table -->
<div class="card">
    <div class="table-wrap">
        <?php if (empty($applications)): ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted)">
            <i class="fas fa-file-alt" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px"></i>
            <p>No applications found<?= ($search || $filterJob || $filterStatus) ? ' for these filters' : ' yet' ?>.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Applicant</th>
                    <th>Position</th>
                    <th>Resume</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
            <tr id="row-<?= $app['id'] ?>">
                <td style="color:var(--text-muted);font-size:12px">#<?= $app['id'] ?></td>
                <td>
                    <div style="font-weight:600;font-size:13.5px"><?= htmlspecialchars($app['name']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted)">
                        <a href="mailto:<?= htmlspecialchars($app['email']) ?>" style="color:var(--maroon)"><?= htmlspecialchars($app['email']) ?></a>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted)">
                        <a href="tel:<?= htmlspecialchars($app['phone']) ?>" style="color:inherit"><?= htmlspecialchars($app['phone']) ?></a>
                    </div>
                    <?php if ($app['message']): ?>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;font-style:italic">"<?= htmlspecialchars(mb_substr($app['message'], 0, 80)) ?>…"</div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-maroon"><?= htmlspecialchars($app['job_title']) ?></span>
                </td>
                <td>
                    <?php if ($app['resume_path']): ?>
                    <a href="<?= SITE_URL . '/' . htmlspecialchars($app['resume_path']) ?>" target="_blank"
                       class="btn btn-sm btn-outline" title="Download Resume" style="font-size:12px">
                        <i class="fas fa-download"></i> Resume
                    </a>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <select class="status-select form-control" data-id="<?= $app['id'] ?>"
                            style="padding:5px 8px;font-size:12px;width:120px;
                            border-color:<?= match($app['status']) {
                                'new'       => 'var(--warning)',
                                'reviewed'  => 'var(--info)',
                                'contacted' => 'var(--success)',
                                'rejected'  => 'var(--danger)',
                                default     => 'var(--beige-dark)'
                            } ?>">
                        <option value="new"       <?= $app['status']==='new'       ?'selected':'' ?>>New</option>
                        <option value="reviewed"  <?= $app['status']==='reviewed'  ?'selected':'' ?>>Reviewed</option>
                        <option value="contacted" <?= $app['status']==='contacted' ?'selected':'' ?>>Contacted</option>
                        <option value="rejected"  <?= $app['status']==='rejected'  ?'selected':'' ?>>Rejected</option>
                    </select>
                </td>
                <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d M Y', strtotime($app['applied_at'])) ?><br><?= date('h:i A', strtotime($app['applied_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="mailto:<?= htmlspecialchars($app['email']) ?>" class="btn btn-sm btn-icon btn-gold" title="Send Email">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <a href="tel:<?= htmlspecialchars($app['phone']) ?>" class="btn btn-sm btn-icon btn-outline" title="Call">
                            <i class="fas fa-phone"></i>
                        </a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this application? This will also delete the uploaded resume.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div class="pagination-bar">
            <?php
            $qs = http_build_query(array_filter(['search'=>$search,'job'=>$filterJob,'status'=>$filterStatus]));
            for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?>&<?= $qs ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Inline status update via AJAX
document.querySelectorAll('.status-select').forEach(function(sel) {
    const colors = { new:'var(--warning)', reviewed:'var(--info)', contacted:'var(--success)', rejected:'var(--danger)' };
    sel.addEventListener('change', function() {
        const appId  = this.dataset.id;
        const status = this.value;
        const data   = new FormData();
        data.append('action',      'update_status');
        data.append('app_id',      appId);
        data.append('status',      status);
        data.append('csrf_token',  '<?= $csrf ?>');
        fetch(window.location.href, { method:'POST', body:data, headers:{'X-Requested-With':'XMLHttpRequest'} })
            .then(r => r.json())
            .then(res => { if (res.success) { this.style.borderColor = colors[status] || 'var(--beige-dark)'; } });
    });
});
</script>

<?php require_once __DIR__ . '/layout-footer.php'; ?>
