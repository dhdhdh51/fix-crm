<?php
/**
 * LuxeEstate Realty - Job Detail & Apply Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . SITE_URL . '/careers.php');
    exit;
}

// Auto-create table guard (in case visited before admin panel)
try {
    $job = db()->prepare("SELECT * FROM job_postings WHERE id=? AND status='active'");
    $job->execute([$id]);
    $job = $job->fetch();
} catch (PDOException $e) {
    $job = null;
}

if (!$job) {
    header('Location: ' . SITE_URL . '/careers.php');
    exit;
}

$siteName      = getSetting('site_name', 'LuxeEstate Realty');
$pageMetaTitle = htmlspecialchars($job['title']) . ' | Careers | ' . $siteName;
$pageMetaDesc  = mb_substr(strip_tags($job['description'] ?? ''), 0, 160);
$currentPage   = 'careers';
$csrf          = generateCSRF();

$success = '';
$errors  = [];

// ── Handle application form ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if (empty($name))  $errors[] = 'Full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';

        // Resume upload — mandatory
        $resumePath = '';
        if (empty($_FILES['resume']['name']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Resume is required. Please upload your CV (PDF, DOC, or DOCX).';
        } elseif ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Resume upload failed. Please try again.';
        } else {
            $allowedMimes = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($_FILES['resume']['tmp_name']);
            $ext   = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['pdf','doc','docx'];

            if (!in_array($mime, $allowedMimes) && !in_array($ext, $allowedExts)) {
                $errors[] = 'Only PDF, DOC, or DOCX files are accepted for resume.';
            } elseif ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Resume file must be under 5MB.';
            } else {
                $resumeDir = UPLOAD_DIR . 'resumes/';
                if (!is_dir($resumeDir)) mkdir($resumeDir, 0755, true);
                $filename   = 'resume_' . uniqid() . '_' . preg_replace('/[^a-z0-9._-]/i', '', basename($_FILES['resume']['name']));
                $destPath   = $resumeDir . $filename;
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $destPath)) {
                    $resumePath = 'uploads/resumes/' . $filename;
                } else {
                    $errors[] = 'Could not save resume. Please try again.';
                }
            }
        }

        if (empty($errors)) {
            $notifyEmail = getSetting('contact_email', '');
            if ($notifyEmail) {
                $subject = "Job Application: {$job['title']} — $name";
                $body  = "New job application received.\n\n";
                $body .= "Position: {$job['title']}\n";
                $body .= "Applicant: $name\n";
                $body .= "Email: $email\n";
                $body .= "Phone: $phone\n";
                if ($message) $body .= "\nMessage:\n$message\n";
                $body .= "\nResume saved at: " . SITE_ROOT . '/' . $resumePath;
                @mail($notifyEmail, $subject, $body, "From: $email\r\nReply-To: $email");
            }
            $success = 'Your application has been submitted! We will contact you within 3 business days.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section style="background:linear-gradient(135deg,var(--maroon-dark) 0%,var(--maroon) 60%,#9B1C1C 100%);padding:110px 0 50px">
    <div class="container">
        <nav style="font-size:13px;color:rgba(255,255,255,.65);margin-bottom:18px">
            <a href="<?= SITE_URL ?>" style="color:rgba(255,255,255,.65)">Home</a>
            <span style="margin:0 8px">›</span>
            <a href="<?= SITE_URL ?>/careers.php" style="color:rgba(255,255,255,.65)">Careers</a>
            <span style="margin:0 8px">›</span>
            <span style="color:#fff"><?= htmlspecialchars($job['title']) ?></span>
        </nav>
        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;margin-bottom:16px">
            <?php if ($job['department']): ?>
            <span style="background:rgba(255,255,255,.15);color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.3)"><?= htmlspecialchars($job['department']) ?></span>
            <?php endif; ?>
            <?php if ($job['featured']): ?>
            <span style="background:rgba(220,38,38,.8);color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:20px"><i class="fas fa-fire"></i> Urgent Hiring</span>
            <?php endif; ?>
        </div>
        <h1 style="font-size:2.4rem;font-weight:800;color:#fff;margin-bottom:16px;line-height:1.2"><?= htmlspecialchars($job['title']) ?></h1>
        <div style="display:flex;gap:24px;flex-wrap:wrap;color:rgba(255,255,255,.8);font-size:14px">
            <?php if ($job['location']): ?>
            <span><i class="fas fa-map-marker-alt" style="color:var(--gold);margin-right:6px"></i><?= htmlspecialchars($job['location']) ?></span>
            <?php endif; ?>
            <?php if ($job['experience']): ?>
            <span><i class="fas fa-briefcase" style="color:var(--gold);margin-right:6px"></i><?= htmlspecialchars($job['experience']) ?></span>
            <?php endif; ?>
            <span><i class="fas fa-clock" style="color:var(--gold);margin-right:6px"></i>Full Time</span>
            <span><i class="fas fa-calendar" style="color:var(--gold);margin-right:6px"></i>Posted <?= date('d M Y', strtotime($job['created_at'])) ?></span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 380px;gap:36px;align-items:start" class="job-detail-grid">

            <!-- Left: Job Details -->
            <div>
                <?php if ($job['description']): ?>
                <div style="background:#fff;border-radius:14px;padding:32px;box-shadow:var(--shadow-sm);margin-bottom:24px">
                    <h2 style="font-size:1.2rem;font-weight:700;color:var(--maroon);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--beige-dark)">
                        <i class="fas fa-file-alt" style="margin-right:8px"></i>Job Description
                    </h2>
                    <div class="job-rich-content" style="color:var(--text-muted);line-height:1.8;font-size:14.5px">
                        <?= $job['description'] ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($job['requirements']): ?>
                <div style="background:#fff;border-radius:14px;padding:32px;box-shadow:var(--shadow-sm);margin-bottom:24px">
                    <h2 style="font-size:1.2rem;font-weight:700;color:var(--maroon);margin-bottom:18px;padding-bottom:12px;border-bottom:2px solid var(--beige-dark)">
                        <i class="fas fa-list-check" style="margin-right:8px"></i>Requirements & Skills
                    </h2>
                    <div class="job-rich-content" style="color:var(--text-muted);line-height:1.8;font-size:14.5px">
                        <?= $job['requirements'] ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Back link -->
                <a href="<?= SITE_URL ?>/careers.php" style="display:inline-flex;align-items:center;gap:8px;color:var(--maroon);font-weight:600;font-size:14px">
                    <i class="fas fa-arrow-left"></i> View All Openings
                </a>
            </div>

            <!-- Right: Apply Form -->
            <div style="position:sticky;top:100px" id="applyForm">
                <div style="background:#fff;border-radius:16px;padding:30px;box-shadow:var(--shadow-md);border-top:4px solid var(--maroon)">
                    <h3 style="font-size:1.1rem;font-weight:700;color:var(--maroon);margin-bottom:6px">Apply for this Position</h3>
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">Our team will review your application and reach out within 3 business days.</p>

                    <?php if ($success): ?>
                    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:18px;text-align:center;color:#16a34a">
                        <i class="fas fa-check-circle" style="font-size:28px;margin-bottom:8px;display:block"></i>
                        <strong>Application Submitted!</strong>
                        <p style="font-size:13px;margin:6px 0 0"><?= htmlspecialchars($success) ?></p>
                    </div>
                    <?php else: ?>

                    <?php if (!empty($errors)): ?>
                    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 16px;margin-bottom:16px;color:#dc2626;font-size:13px">
                        <ul style="margin:0;padding-left:16px">
                            <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <div style="margin-bottom:14px">
                            <label style="font-size:12px;font-weight:700;color:var(--text);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px">Full Name *</label>
                            <input type="text" name="name" required placeholder="Rahul Sharma"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   class="form-control">
                        </div>
                        <div style="margin-bottom:14px">
                            <label style="font-size:12px;font-weight:700;color:var(--text);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px">Email Address *</label>
                            <input type="email" name="email" required placeholder="rahul@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="form-control">
                        </div>
                        <div style="margin-bottom:14px">
                            <label style="font-size:12px;font-weight:700;color:var(--text);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px">Phone Number *</label>
                            <input type="tel" name="phone" required placeholder="+91 98765 43210"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   class="form-control">
                        </div>
                        <div style="margin-bottom:14px">
                            <label style="font-size:12px;font-weight:700;color:var(--text);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px">Cover Message</label>
                            <textarea name="message" rows="3" placeholder="Tell us briefly why you're a great fit..."
                                      class="form-control"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <!-- Resume upload — mandatory -->
                        <div style="margin-bottom:20px">
                            <label style="font-size:12px;font-weight:700;color:var(--text);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px">
                                Resume / CV * <span style="color:var(--danger)">(Required)</span>
                            </label>
                            <div id="resumeDropzone" style="border:2px dashed var(--maroon);border-radius:10px;padding:20px;text-align:center;cursor:pointer;background:rgba(128,0,0,.03);transition:all .2s"
                                 onclick="document.getElementById('resumeInput').click()">
                                <i class="fas fa-file-upload" style="font-size:22px;color:var(--maroon);display:block;margin-bottom:6px"></i>
                                <p style="font-size:13px;color:var(--text-muted);margin:0" id="resumeLabel">
                                    Click to upload your Resume<br>
                                    <span style="font-size:11px">PDF, DOC, DOCX — Max 5MB</span>
                                </p>
                            </div>
                            <input type="file" name="resume" id="resumeInput" required
                                   accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                   style="display:none">
                        </div>

                        <button type="submit" style="width:100%;background:var(--maroon);color:#fff;padding:13px;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;letter-spacing:.02em"
                                onmouseover="this.style.background='var(--maroon-dark)'" onmouseout="this.style.background='var(--maroon)'">
                            <i class="fas fa-paper-plane" style="margin-right:8px"></i> Submit Application
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
// Resume dropzone feedback
const resumeInput = document.getElementById('resumeInput');
const resumeLabel = document.getElementById('resumeLabel');
const resumeDz    = document.getElementById('resumeDropzone');
if (resumeInput) {
    resumeInput.addEventListener('change', function() {
        if (this.files[0]) {
            resumeLabel.innerHTML = '<i class="fas fa-check-circle" style="color:var(--success)"></i> ' + this.files[0].name;
            resumeDz.style.borderColor = 'var(--success)';
            resumeDz.style.background  = 'rgba(22,163,74,.04)';
        }
    });
    resumeDz.addEventListener('dragover', e => { e.preventDefault(); resumeDz.style.background = 'rgba(128,0,0,.07)'; });
    resumeDz.addEventListener('dragleave', () => { resumeDz.style.background = 'rgba(128,0,0,.03)'; });
    resumeDz.addEventListener('drop', e => {
        e.preventDefault();
        resumeInput.files = e.dataTransfer.files;
        resumeInput.dispatchEvent(new Event('change'));
    });
}
</script>

<style>
.job-rich-content p  { margin-bottom: 10px; }
.job-rich-content ul, .job-rich-content ol { padding-left: 20px; margin-bottom: 10px; }
.job-rich-content li { margin-bottom: 4px; }
.job-rich-content h2, .job-rich-content h3 { color: var(--maroon); margin: 16px 0 8px; font-weight: 700; }
.job-rich-content strong { color: var(--text); }
@media (max-width: 860px) { .job-detail-grid { grid-template-columns: 1fr !important; } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
