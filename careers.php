<?php
/**
 * LuxeEstate Realty - Careers Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/functions.php';

$csrf          = generateCSRF();
$currentPage   = 'careers';
$siteName      = getSetting('site_name', 'LuxeEstate Realty');
$pageMetaTitle = "Careers | $siteName";
$pageMetaDesc  = "Join the $siteName team. Explore exciting career opportunities in real estate sales, marketing, and operations.";

// Load jobs from DB (table may not exist on first visit before admin creates it)
$dbJobs = [];
try {
    $dbJobs = db()->query("SELECT * FROM job_postings WHERE status='active' ORDER BY sort_order ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) { /* table doesn't exist yet — fall back to defaults */ }

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if (empty($name))     $errors[] = 'Name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (empty($phone))    $errors[] = 'Phone number is required.';
        if (empty($position)) $errors[] = 'Please select a position.';

        if (empty($errors)) {
            $notifyEmail = getSetting('contact_email', '');
            if ($notifyEmail) {
                $subject = "Career Application: $position - $name";
                $body    = "New career application received.\n\nName: $name\nEmail: $email\nPhone: $phone\nPosition: $position\n\nMessage:\n$message";
                @mail($notifyEmail, $subject, $body, "From: $email\r\nReply-To: $email");
            }
            $success = 'Thank you for your application! We will get back to you within 3 business days.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="page-hero" style="background:linear-gradient(135deg,var(--maroon-dark) 0%,var(--maroon) 60%,var(--gold) 100%);padding:120px 0 60px;text-align:center;color:#fff">
    <div class="container">
        <h1 style="font-size:2.8rem;font-weight:800;margin-bottom:12px">Join Our Team</h1>
        <p style="font-size:1.1rem;opacity:.85;max-width:560px;margin:0 auto">Be part of a passionate team redefining luxury real estate across India.</p>
        <nav style="margin-top:20px;font-size:13px;opacity:.7">
            <a href="<?= SITE_URL ?>" style="color:#fff">Home</a>
            <span style="margin:0 8px">›</span>
            <span>Careers</span>
        </nav>
    </div>
</section>

<!-- Why Join Us -->
<section class="section" style="background:var(--beige-light)">
    <div class="container">
        <div style="text-align:center;margin-bottom:48px">
            <h2 class="section-title">Why Work With <span>Us</span></h2>
            <p style="color:var(--text-muted);max-width:560px;margin:0 auto">We invest in our people just as we invest in properties — with care, vision, and a long-term commitment.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px">
            <?php
            $perks = [
                ['fas fa-rupee-sign',    'Competitive Pay',       'Industry-leading compensation with performance bonuses and incentives.'],
                ['fas fa-chart-line',    'Growth Opportunities',  'Clear career progression with mentorship from senior professionals.'],
                ['fas fa-users',         'Collaborative Culture', 'A supportive, diverse team that celebrates wins together.'],
                ['fas fa-graduation-cap','Continuous Learning',   'Regular training, workshops, and access to industry resources.'],
                ['fas fa-star',          'Premium Brand',         'Represent one of the most respected names in luxury real estate.'],
                ['fas fa-heart',         'Work-Life Balance',     'Flexible schedules and a healthy work environment.'],
            ];
            foreach ($perks as [$icon, $title, $desc]): ?>
            <div style="background:#fff;border-radius:12px;padding:28px 24px;box-shadow:var(--shadow-sm);text-align:center">
                <div style="width:52px;height:52px;background:var(--gold-gradient);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <i class="<?= $icon ?>" style="color:var(--maroon-dark);font-size:20px"></i>
                </div>
                <h3 style="font-size:1rem;font-weight:700;color:var(--maroon);margin-bottom:8px"><?= $title ?></h3>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.6;margin:0"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Open Positions -->
<section class="section">
    <div class="container">
        <div style="text-align:center;margin-bottom:48px">
            <h2 class="section-title">Open <span>Positions</span></h2>
            <p style="color:var(--text-muted)">We're hiring across multiple roles. Find the one that fits you.</p>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px;max-width:860px;margin:0 auto">
            <?php
            // Use DB jobs if available, otherwise show defaults
            $displayJobs = !empty($dbJobs) ? $dbJobs : [
                ['title'=>'Senior Property Consultant','department'=>'Sales','location'=>'Bangalore','experience'=>'3+ years','description'=>'Drive premium property sales, build client relationships, and hit ambitious targets in a high-energy environment.','featured'=>0],
                ['title'=>'Real Estate Agent','department'=>'Sales','location'=>'Bangalore / Hyderabad','experience'=>'1+ years','description'=>'Help clients buy, sell, and invest in residential and commercial properties across the city.','featured'=>0],
                ['title'=>'Digital Marketing Executive','department'=>'Marketing','location'=>'Bangalore (Hybrid)','experience'=>'2+ years','description'=>'Manage paid campaigns, social media, SEO, and content strategy to generate quality leads.','featured'=>0],
                ['title'=>'Customer Relations Manager','department'=>'Operations','location'=>'Bangalore','experience'=>'3+ years','description'=>'Ensure an exceptional end-to-end experience for every client from first enquiry to possession.','featured'=>0],
                ['title'=>'Property Research Analyst','department'=>'Research','location'=>'Bangalore','experience'=>'2+ years','description'=>'Analyse market trends, pricing, and investment opportunities to guide clients and internal teams.','featured'=>0],
                ['title'=>'Admin & Back-Office Executive','department'=>'Administration','location'=>'Bangalore','experience'=>'Fresher / 1+ year','description'=>'Support day-to-day operations, documentation, coordination, and CRM management.','featured'=>0],
            ];
            foreach ($displayJobs as $job): ?>
            <div style="background:#fff;border:1px solid var(--border-light);border-radius:12px;padding:24px 28px;display:flex;gap:20px;align-items:flex-start;transition:box-shadow .2s<?= !empty($job['featured']) ? ';border-left:4px solid var(--gold)' : '' ?>" onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='none'">
                <div style="flex:1">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
                        <h3 style="font-size:1.05rem;font-weight:700;color:var(--maroon);margin:0"><?= htmlspecialchars($job['title']) ?></h3>
                        <?php if ($job['department']): ?>
                        <span style="background:#fff8e1;color:var(--maroon);font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid var(--gold)"><?= htmlspecialchars($job['department']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($job['featured'])): ?>
                        <span style="background:#fef2f2;color:var(--maroon);font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid var(--maroon)"><i class="fas fa-fire"></i> Urgent Hiring</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($job['description']): ?>
                    <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;line-height:1.6"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($job['requirements'])): ?>
                    <p style="font-size:12px;color:var(--text-muted);margin:0 0 12px;line-height:1.6"><strong>Requirements:</strong> <?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;gap:16px;font-size:12px;color:var(--text-muted);flex-wrap:wrap">
                        <?php if ($job['location']): ?>
                        <span><i class="fas fa-map-marker-alt" style="color:var(--maroon);margin-right:5px"></i><?= htmlspecialchars($job['location']) ?></span>
                        <?php endif; ?>
                        <?php if ($job['experience']): ?>
                        <span><i class="fas fa-briefcase" style="color:var(--gold);margin-right:5px"></i><?= htmlspecialchars($job['experience']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="#apply" onclick="document.querySelector('[name=position]').value=<?= json_encode($job['title']) ?>" style="white-space:nowrap;background:var(--maroon);color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;transition:background .2s" onmouseover="this.style.background='var(--maroon-dark)'" onmouseout="this.style.background='var(--maroon)'">Apply Now</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Apply Form -->
<section class="section" style="background:var(--beige-light)" id="apply">
    <div class="container">
        <div style="max-width:660px;margin:0 auto">
            <div style="text-align:center;margin-bottom:36px">
                <h2 class="section-title">Apply <span>Now</span></h2>
                <p style="color:var(--text-muted)">Fill in your details and we'll reach out to you shortly.</p>
            </div>

            <?php if ($success): ?>
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:20px 24px;text-align:center;color:#16a34a;margin-bottom:24px">
                <i class="fas fa-check-circle" style="font-size:24px;margin-bottom:8px;display:block"></i>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;padding:16px 20px;margin-bottom:20px;color:#dc2626">
                <ul style="margin:0;padding-left:18px">
                    <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div style="background:#fff;border-radius:16px;padding:36px;box-shadow:var(--shadow-md)">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px" class="careers-form-row">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--text-dark);display:block;margin-bottom:6px">Full Name *</label>
                            <input type="text" name="name" required placeholder="Rahul Sharma"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                   style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;transition:border .2s" onfocus="this.style.borderColor='var(--maroon)'" onblur="this.style.borderColor='var(--border-light)'">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--text-dark);display:block;margin-bottom:6px">Phone *</label>
                            <input type="tel" name="phone" required placeholder="+91 98765 43210"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;transition:border .2s" onfocus="this.style.borderColor='var(--maroon)'" onblur="this.style.borderColor='var(--border-light)'">
                        </div>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="font-size:13px;font-weight:600;color:var(--text-dark);display:block;margin-bottom:6px">Email Address *</label>
                        <input type="email" name="email" required placeholder="rahul@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;transition:border .2s" onfocus="this.style.borderColor='var(--maroon)'" onblur="this.style.borderColor='var(--border-light)'">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="font-size:13px;font-weight:600;color:var(--text-dark);display:block;margin-bottom:6px">Position Applying For *</label>
                        <select name="position" required style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;background:#fff;transition:border .2s" onfocus="this.style.borderColor='var(--maroon)'" onblur="this.style.borderColor='var(--border-light)'">
                            <option value="">Select a position...</option>
                            <?php foreach ($displayJobs as $job):
                                $t = $job['title'];
                                $sel = ($_POST['position'] ?? '') === $t ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $sel ?>><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                            <option value="Other" <?= ($_POST['position'] ?? '') === 'Other' ? 'selected' : '' ?>>Other / General Application</option>
                        </select>
                    </div>
                    <div style="margin-bottom:24px">
                        <label style="font-size:13px;font-weight:600;color:var(--text-dark);display:block;margin-bottom:6px">Cover Letter / Message</label>
                        <textarea name="message" rows="5" placeholder="Tell us about yourself, your experience, and why you want to join us..."
                                  style="width:100%;padding:10px 14px;border:1.5px solid var(--border-light);border-radius:8px;font-size:14px;box-sizing:border-box;resize:vertical;outline:none;transition:border .2s;font-family:inherit" onfocus="this.style.borderColor='var(--maroon)'" onblur="this.style.borderColor='var(--border-light)'"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" style="width:100%;background:var(--maroon);color:#fff;padding:14px;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:background .2s;letter-spacing:.02em" onmouseover="this.style.background='var(--maroon-dark)'" onmouseout="this.style.background='var(--maroon)'">
                        <i class="fas fa-paper-plane" style="margin-right:8px"></i> Submit Application
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
@media (max-width: 600px) {
    .careers-form-row { grid-template-columns: 1fr !important; }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
