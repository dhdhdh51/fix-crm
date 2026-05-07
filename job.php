<?php
/**
 * LuxeEstate Realty - Job Detail & Apply Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/careers.php'); exit; }

// Auto-create job_applications table
try {
    db()->exec("CREATE TABLE IF NOT EXISTS `job_applications` (
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

    $stmt = db()->prepare("SELECT * FROM job_postings WHERE id=? AND status='active'");
    $stmt->execute([$id]);
    $job = $stmt->fetch();
} catch (PDOException $e) {
    $job = null;
}

if (!$job) { header('Location: ' . SITE_URL . '/careers.php'); exit; }

$siteName      = getSetting('site_name', 'LuxeEstate Realty');
$pageMetaTitle = htmlspecialchars($job['title']) . ' | Careers | ' . $siteName;
$pageMetaDesc  = mb_substr(strip_tags($job['description'] ?? ''), 0, 160);
$currentPage   = 'careers';
$csrf          = generateCSRF();
$success       = '';
$errors        = [];

// ── Handle application submission ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    } else {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name))  $errors[] = 'Full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';

        // Resume — mandatory
        $resumePath = '';
        if (empty($_FILES['resume']['name']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Resume is required. Please upload your CV.';
        } elseif ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Resume upload failed (error ' . $_FILES['resume']['error'] . '). Please try again.';
        } else {
            $finfo       = new finfo(FILEINFO_MIME_TYPE);
            $mime        = $finfo->file($_FILES['resume']['tmp_name']);
            $ext         = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowedMime = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $allowedExt  = ['pdf','doc','docx'];

            if (!in_array($mime, $allowedMime) && !in_array($ext, $allowedExt)) {
                $errors[] = 'Only PDF, DOC, or DOCX files are accepted.';
            } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Resume must be under 5MB.';
            } else {
                $resumeDir = UPLOAD_DIR . 'resumes/';
                if (!is_dir($resumeDir)) mkdir($resumeDir, 0755, true);
                $safeOrig   = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['resume']['name']));
                $filename   = 'resume_' . $id . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $resumeDir . $filename)) {
                    $resumePath = 'uploads/resumes/' . $filename;
                } else {
                    $errors[] = 'Could not save resume file. Please try again.';
                }
            }
        }

        if (empty($errors)) {
            // Save to database
            db()->prepare("INSERT INTO job_applications (job_id, job_title, name, email, phone, message, resume_path)
                           VALUES (?, ?, ?, ?, ?, ?, ?)")
               ->execute([$job['id'], $job['title'], $name, $email, $phone, $message, $resumePath]);

            // Email admin
            $notifyEmail = getSetting('contact_email', '');
            if ($notifyEmail) {
                $subject  = "New Application: {$job['title']} — $name";
                $body     = "New job application received.\n\n";
                $body    .= "Position : {$job['title']}\n";
                $body    .= "Applicant: $name\n";
                $body    .= "Email    : $email\n";
                $body    .= "Phone    : $phone\n";
                if ($message) $body .= "\nMessage:\n$message\n";
                $body    .= "\nResume   : " . SITE_URL . '/' . $resumePath;
                $body    .= "\n\nView all applications: " . ADMIN_URL . '/job-applications.php';
                $safeEmail = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : $notifyEmail;
                @mail($notifyEmail, $subject, $body, "From: $safeEmail\r\nReply-To: $safeEmail");
            }
            $success = 'Your application has been submitted! We will contact you within 3 business days.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section style="background:linear-gradient(135deg,var(--maroon-dark) 0%,var(--maroon) 60%,#9B1C1C 100%);padding:110px 0 50px">
    <div class="container">
        <nav style="font-size:13px;color:rgba(255,255,255,.65);margin-bottom:18px">
            <a href="<?= SITE_URL ?>" style="color:rgba(255,255,255,.65)">Home</a> <span style="margin:0 8px">›</span>
            <a href="<?= SITE_URL ?>/careers.php" style="color:rgba(255,255,255,.65)">Careers</a> <span style="margin:0 8px">›</span>
            <span style="color:#fff"><?= htmlspecialchars($job['title']) ?></span>
        </nav>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <?php if ($job['department']): ?>
            <span style="background:rgba(255,255,255,.15);color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.3)"><?= htmlspecialchars($job['department']) ?></span>
            <?php endif; ?>
            <?php if ($job['featured']): ?>
            <span style="background:rgba(220,38,38,.8);color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:20px"><i class="fas fa-fire"></i> Urgent Hiring</span>
            <?php endif; ?>
        </div>
        <h1 style="font-size:2.4rem;font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2"><?= htmlspecialchars($job['title']) ?></h1>
        <div style="display:flex;gap:24px;flex-wrap:wrap;color:rgba(255,255,255,.8);font-size:14px">
            <?php if ($job['location']): ?><span><i class="fas fa-map-marker-alt" style="color:var(--gold);margin-right:6px"></i><?= htmlspecialchars($job['location']) ?></span><?php endif; ?>
            <?php if ($job['experience']): ?><span><i class="fas fa-briefcase" style="color:var(--gold);margin-right:6px"></i><?= htmlspecialchars($job['experience']) ?></span><?php endif; ?>
            <span><i class="fas fa-clock" style="color:var(--gold);margin-right:6px"></i>Full Time</span>
            <span><i class="fas fa-calendar" style="color:var(--gold);margin-right:6px"></i>Posted <?= date('d M Y', strtotime($job['created_at'])) ?></span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 380px;gap:36px;align-items:start" class="job-detail-grid">

            <!-- Left: Details -->
            <div>
                <?php if ($job['description']): ?>
                <div style="background:#fff;border-radius:14px;padding:32px;box-shadow:var(--shadow-sm);margin-bottom:24px">
                    <h2 style="font-size:1.15rem;font-weight:700;color:var(--maroon);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--beige-dark)">
                        <i class="fas fa-file-alt" style="margin-right:8px"></i>Job Description
                    </h2>
                    <div class="job-rich-content"><?= $job['description'] ?></div>
                </div>
                <?php endif; ?>

                <?php if ($job['requirements']): ?>
                <div style="background:#fff;border-radius:14px;padding:32px;box-shadow:var(--shadow-sm);margin-bottom:24px">
                    <h2 style="font-size:1.15rem;font-weight:700;color:var(--maroon);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--beige-dark)">
                        <i class="fas fa-check-square" style="margin-right:8px"></i>Requirements &amp; Skills
                    </h2>
                    <div class="job-rich-content"><?= $job['requirements'] ?></div>
                </div>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/careers.php" style="display:inline-flex;align-items:center;gap:8px;color:var(--maroon);font-weight:600;font-size:14px">
                    <i class="fas fa-arrow-left"></i> All Openings
                </a>
            </div>

            <!-- Right: Apply Form -->
            <div style="position:sticky;top:100px">
                <div style="background:#fff;border-radius:16px;padding:30px;box-shadow:var(--shadow-md);border-top:4px solid var(--maroon)">
                    <h3 style="font-size:1.05rem;font-weight:700;color:var(--maroon);margin-bottom:4px">Apply for this Position</h3>
                    <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px">Resume is mandatory. We'll reply within 3 business days.</p>

                    <?php if ($success): ?>
                    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:20px;text-align:center;color:#16a34a">
                        <i class="fas fa-check-circle" style="font-size:28px;margin-bottom:8px;display:block"></i>
                        <strong>Application Submitted!</strong>
                        <p style="font-size:13px;margin:6px 0 0"><?= htmlspecialchars($success) ?></p>
                    </div>
                    <?php else: ?>

                    <?php if (!empty($errors)): ?>
                    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-bottom:16px;color:#dc2626;font-size:13px">
                        <ul style="margin:0;padding-left:16px">
                            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <div style="margin-bottom:12px">
                            <label style="font-size:11px;font-weight:700;color:var(--text);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px">Full Name *</label>
                            <input type="text" name="name" required placeholder="Rahul Sharma"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" class="form-control">
                        </div>
                        <div style="margin-bottom:12px">
                            <label style="font-size:11px;font-weight:700;color:var(--text);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px">Email *</label>
                            <input type="email" name="email" required placeholder="rahul@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="form-control">
                        </div>
                        <div style="margin-bottom:12px">
                            <label style="font-size:11px;font-weight:700;color:var(--text);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px">Phone *</label>
                            <input type="tel" name="phone" required placeholder="+91 98765 43210"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" class="form-control">
                        </div>
                        <div style="margin-bottom:12px">
                            <label style="font-size:11px;font-weight:700;color:var(--text);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px">Cover Message</label>
                            <textarea name="message" rows="3" placeholder="Why are you a great fit?" class="form-control"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <!-- Resume — MANDATORY -->
                        <div style="margin-bottom:18px">
                            <label style="font-size:11px;font-weight:700;color:var(--text);display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px">
                                Resume / CV * <span style="color:var(--danger);font-size:10px">(Required)</span>
                            </label>
                            <div id="resumeDz" onclick="document.getElementById('resumeFile').click()"
                                 style="border:2px dashed var(--maroon);border-radius:10px;padding:18px;text-align:center;cursor:pointer;background:rgba(128,0,0,.03);transition:.2s">
                                <i class="fas fa-file-upload" style="font-size:20px;color:var(--maroon);display:block;margin-bottom:6px"></i>
                                <p id="resumeLbl" style="font-size:12px;color:var(--text-muted);margin:0">
                                    Click to upload Resume<br><span style="font-size:10px">PDF, DOC, DOCX — Max 5MB</span>
                                </p>
                            </div>
                            <input type="file" id="resumeFile" name="resume" required
                                   accept=".pdf,.doc,.docx" style="display:none">
                        </div>

                        <button type="submit" style="width:100%;background:var(--maroon);color:#fff;padding:13px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:background .2s"
                                onmouseover="this.style.background='var(--maroon-dark)'" onmouseout="this.style.background='var(--maroon)'">
                            <i class="fas fa-paper-plane" style="margin-right:8px"></i>Submit Application
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
(function(){
    const inp = document.getElementById('resumeFile');
    const lbl = document.getElementById('resumeLbl');
    const dz  = document.getElementById('resumeDz');
    if (!inp) return;
    inp.addEventListener('change', function() {
        if (this.files[0]) {
            lbl.innerHTML = '<i class="fas fa-check-circle" style="color:green"></i> ' + this.files[0].name;
            dz.style.borderColor = 'green';
            dz.style.background  = 'rgba(22,163,74,.04)';
        }
    });
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.style.background='rgba(128,0,0,.07)'; });
    dz.addEventListener('dragleave', () => { dz.style.background='rgba(128,0,0,.03)'; });
    dz.addEventListener('drop', e => {
        e.preventDefault();
        inp.files = e.dataTransfer.files;
        inp.dispatchEvent(new Event('change'));
    });
})();
</script>

<style>
.job-rich-content { color:var(--text-muted); line-height:1.8; font-size:14px; }
.job-rich-content p  { margin-bottom:10px; }
.job-rich-content ul,.job-rich-content ol { padding-left:20px; margin-bottom:10px; }
.job-rich-content li { margin-bottom:4px; }
.job-rich-content h2,.job-rich-content h3 { color:var(--maroon); margin:16px 0 8px; font-weight:700; }
@media (max-width:860px) { .job-detail-grid { grid-template-columns:1fr !important; } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
