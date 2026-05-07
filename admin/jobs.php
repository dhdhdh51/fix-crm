<?php
/**
 * Admin — Job Postings
 * POST processing runs before ANY output so header('Location:') works.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/functions.php';
requireAdmin();

$db = db();

// Auto-create table if it doesn't exist yet
$db->exec("CREATE TABLE IF NOT EXISTS `job_postings` (
    `id`           int(11)      NOT NULL AUTO_INCREMENT,
    `title`        varchar(200) NOT NULL,
    `department`   varchar(100) DEFAULT NULL,
    `location`     varchar(150) DEFAULT NULL,
    `experience`   varchar(100) DEFAULT NULL,
    `description`  longtext     DEFAULT NULL,
    `requirements` longtext     DEFAULT NULL,
    `status`       enum('active','inactive') DEFAULT 'active',
    `featured`     tinyint(1)   DEFAULT 0,
    `sort_order`   int(11)      DEFAULT 0,
    `created_at`   timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Handle POST before any output ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Security token expired. Please try again.'];
        header('Location: ' . ADMIN_URL . '/jobs.php'); exit;
    }

    $action = trim($_POST['action'] ?? '');

    if ($action === 'delete') {
        $db->prepare("DELETE FROM job_postings WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Job posting deleted.'];
        header('Location: ' . ADMIN_URL . '/jobs.php'); exit;
    }

    if (in_array($action, ['add_job', 'update_job'])) {
        $title        = trim($_POST['title'] ?? '');
        $department   = trim($_POST['department'] ?? '');
        $location     = trim($_POST['location'] ?? '');
        $experience   = trim($_POST['experience'] ?? '');
        // Quill sends HTML via hidden inputs
        $description  = $_POST['description_html'] ?? trim($_POST['description'] ?? '');
        $requirements = $_POST['requirements_html'] ?? trim($_POST['requirements'] ?? '');
        $status       = trim($_POST['status'] ?? 'active');
        $featured     = isset($_POST['featured']) ? 1 : 0;
        $sort_order   = (int)($_POST['sort_order'] ?? 0);
        $editId       = (int)($_POST['edit_id'] ?? 0);

        if (empty($title)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Job title is required.'];
            header('Location: ' . ADMIN_URL . '/jobs.php'); exit;
        }

        // Basic HTML sanitization — strip dangerous tags, keep formatting
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><span>';
        $description  = strip_tags($description,  $allowed);
        $requirements = strip_tags($requirements, $allowed);

        try {
            if ($editId) {
                $db->prepare("UPDATE job_postings SET title=?,department=?,location=?,experience=?,
                    description=?,requirements=?,status=?,featured=?,sort_order=?,updated_at=NOW() WHERE id=?")
                   ->execute([$title,$department,$location,$experience,$description,$requirements,$status,$featured,$sort_order,$editId]);
            } else {
                $db->prepare("INSERT INTO job_postings (title,department,location,experience,description,requirements,status,featured,sort_order)
                    VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([$title,$department,$location,$experience,$description,$requirements,$status,$featured,$sort_order]);
            }
            $_SESSION['flash'] = ['type' => 'success', 'msg' => $editId ? 'Job posting updated.' : 'Job posting added.'];
        } catch (PDOException $e) {
            error_log('Job save error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Database error: ' . $e->getMessage()];
        }
        header('Location: ' . ADMIN_URL . '/jobs.php'); exit;
    }
}

// ── Edit mode ──
$editJob = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM job_postings WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editJob = $s->fetch() ?: null;
}

$jobs  = $db->query("SELECT * FROM job_postings ORDER BY sort_order ASC, created_at DESC")->fetchAll();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$csrf       = generateCSRF();
$pageTitle  = 'Job Postings';
$activePage = 'jobs';
require_once __DIR__ . '/layout-header.php';
?>

<!-- Quill editor CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

<div class="page-header">
    <div>
        <h1><i class="fas fa-briefcase" style="color:var(--maroon)"></i> Job Postings</h1>
        <p>Manage job openings shown on the Careers page</p>
    </div>
    <button onclick="toggleForm()" class="btn btn-gold" id="addJobBtn">
        <i class="fas fa-plus"></i> <?= $editJob ? 'Edit Job' : 'Add Job' ?>
    </button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Add / Edit Form -->
<div id="jobFormWrap" style="display:<?= $editJob ? 'block' : 'none' ?>;margin-bottom:20px">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= $editJob ? 'Edit: ' . htmlspecialchars($editJob['title']) : 'New Job Posting' ?></span>
            <button type="button" onclick="toggleForm(false)" class="btn btn-sm btn-gray"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <form method="POST" id="jobForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="<?= $editJob ? 'update_job' : 'add_job' ?>">
                <?php if ($editJob): ?>
                <input type="hidden" name="edit_id" value="<?= $editJob['id'] ?>">
                <?php endif; ?>
                <!-- Quill output hidden fields -->
                <input type="hidden" name="description_html" id="descriptionHtml">
                <input type="hidden" name="requirements_html" id="requirementsHtml">

                <div class="form-grid" style="margin-bottom:18px">
                    <div class="form-group form-full">
                        <label class="form-label">Job Title *</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($editJob['title'] ?? '') ?>"
                               class="form-control" placeholder="e.g. Senior Property Consultant" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" value="<?= htmlspecialchars($editJob['department'] ?? '') ?>"
                               class="form-control" placeholder="e.g. Sales" list="deptList">
                        <datalist id="deptList">
                            <option value="Sales"><option value="Marketing"><option value="Operations">
                            <option value="Finance"><option value="HR"><option value="Research"><option value="Administration">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" value="<?= htmlspecialchars($editJob['location'] ?? '') ?>"
                               class="form-control" placeholder="e.g. Bangalore / Hybrid">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Experience Required</label>
                        <input type="text" name="experience" value="<?= htmlspecialchars($editJob['experience'] ?? '') ?>"
                               class="form-control" placeholder="e.g. 2+ years">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active"   <?= ($editJob['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active (Visible)</option>
                            <option value="inactive" <?= ($editJob['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" value="<?= (int)($editJob['sort_order'] ?? 0) ?>"
                               class="form-control" min="0" placeholder="0 = first">
                        <span class="form-hint">Lower number appears first</span>
                    </div>
                </div>

                <!-- Rich text: Description -->
                <div class="form-group" style="margin-bottom:20px">
                    <label class="form-label">Job Description</label>
                    <div id="descriptionEditor" style="min-height:180px;background:#fff;border-radius:0 0 8px 8px"><?= $editJob['description'] ?? '' ?></div>
                </div>

                <!-- Rich text: Requirements -->
                <div class="form-group" style="margin-bottom:20px">
                    <label class="form-label">Requirements / Skills</label>
                    <div id="requirementsEditor" style="min-height:140px;background:#fff;border-radius:0 0 8px 8px"><?= $editJob['requirements'] ?? '' ?></div>
                </div>

                <div class="form-check" style="margin-bottom:18px">
                    <input type="checkbox" name="featured" id="featuredCb" <?= !empty($editJob['featured']) ? 'checked' : '' ?>>
                    <label for="featuredCb" class="form-check-label">
                        <i class="fas fa-fire" style="color:var(--danger)"></i> Mark as Urgent Hiring
                    </label>
                </div>

                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-gold" id="saveJobBtn">
                        <i class="fas fa-save"></i> <?= $editJob ? 'Update Job' : 'Post Job' ?>
                    </button>
                    <button type="button" onclick="toggleForm(false)" class="btn btn-gray">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Job List -->
<div class="card">
    <div class="table-wrap">
        <?php if (empty($jobs)): ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted)">
            <i class="fas fa-briefcase" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px"></i>
            No job postings yet.
            <button onclick="toggleForm()" class="btn btn-sm btn-gold" style="margin-left:8px">Add First Job</button>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Job Title</th><th>Department</th><th>Location</th>
                    <th>Experience</th><th>Status</th><th>Urgent</th><th>Posted</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($jobs as $j): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:13.5px"><?= htmlspecialchars($j['title']) ?></div>
                    <?php if ($j['description']): ?>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= mb_substr(strip_tags($j['description']), 0, 70) ?>...</div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($j['department']): ?>
                    <span class="badge badge-maroon"><?= htmlspecialchars($j['department']) ?></span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:12px">
                    <?php if ($j['location']): ?>
                    <i class="fas fa-map-marker-alt" style="color:var(--maroon);font-size:10px"></i> <?= htmlspecialchars($j['location']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($j['experience'] ?: '—') ?></td>
                <td><span class="badge <?= $j['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>"><?= ucfirst($j['status']) ?></span></td>
                <td style="text-align:center">
                    <i class="fas fa-fire" style="color:<?= $j['featured'] ? 'var(--danger)' : 'var(--beige-dark)' ?>"></i>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($j['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="<?= SITE_URL ?>/job/<?= $j['id'] ?>" target="_blank"
                           class="btn btn-sm btn-icon btn-gray" title="Preview"><i class="fas fa-eye"></i></a>
                        <a href="?edit=<?= $j['id'] ?>#jobFormWrap" onclick="toggleForm(true)"
                           class="btn btn-sm btn-icon btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this job posting?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $j['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Quill JS -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
const toolbarOptions = [
    ['bold','italic','underline'],
    [{'list':'ordered'},{'list':'bullet'}],
    [{'header':[2,3,false]}],
    ['link','clean']
];

const descEditor = new Quill('#descriptionEditor', {
    theme: 'snow', modules: { toolbar: toolbarOptions }
});
const reqEditor = new Quill('#requirementsEditor', {
    theme: 'snow', modules: { toolbar: toolbarOptions }
});

// Pre-fill on edit
<?php if ($editJob && $editJob['description']): ?>
descEditor.root.innerHTML = <?= json_encode($editJob['description']) ?>;
<?php endif; ?>
<?php if ($editJob && $editJob['requirements']): ?>
reqEditor.root.innerHTML = <?= json_encode($editJob['requirements']) ?>;
<?php endif; ?>

// On submit, copy HTML into hidden fields
document.getElementById('jobForm').addEventListener('submit', function() {
    document.getElementById('descriptionHtml').value  = descEditor.root.innerHTML;
    document.getElementById('requirementsHtml').value = reqEditor.root.innerHTML;
});

function toggleForm(show) {
    const wrap = document.getElementById('jobFormWrap');
    const btn  = document.getElementById('addJobBtn');
    const visible = (show === undefined) ? wrap.style.display === 'none' : show;
    wrap.style.display = visible ? 'block' : 'none';
    if (btn) btn.innerHTML = visible
        ? '<i class="fas fa-times"></i> Cancel'
        : '<i class="fas fa-plus"></i> Add Job';
    if (visible) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
<?php if ($editJob): ?>
document.addEventListener('DOMContentLoaded', () => toggleForm(true));
<?php endif; ?>
</script>

<style>
/* Quill editor admin overrides */
.ql-toolbar { border-radius: 8px 8px 0 0 !important; border-color: var(--beige-dark) !important; }
.ql-container { border-radius: 0 0 8px 8px !important; border-color: var(--beige-dark) !important; font-family: 'DM Sans', sans-serif; font-size: 13.5px; }
.ql-editor { min-height: 140px; }
.ql-editor:focus { outline: none; }
</style>

<?php require_once __DIR__ . '/layout-footer.php'; ?>
