<?php
/**
 * LuxeEstate Realty - Careers Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/functions.php';

$currentPage   = 'careers';
$siteName      = getSetting('site_name', 'LuxeEstate Realty');
$pageMetaTitle = "Careers | $siteName";
$pageMetaDesc  = "Join the $siteName team. Explore exciting career opportunities in real estate.";

// Load active jobs from DB
$jobs = [];
try {
    $jobs = db()->query("SELECT * FROM job_postings WHERE status='active' ORDER BY sort_order ASC, featured DESC, created_at DESC")->fetchAll();
} catch (PDOException $e) { /* table not yet created */ }

include __DIR__ . '/includes/header.php';
?>

<section style="background:linear-gradient(135deg,var(--maroon-dark) 0%,var(--maroon) 60%,var(--gold) 100%);padding:120px 0 60px;text-align:center;color:#fff">
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
            <p style="color:var(--text-muted);max-width:560px;margin:0 auto">We invest in our people just as we invest in properties — with care, vision, and commitment.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px">
            <?php foreach ([
                ['fas fa-rupee-sign',    'Competitive Pay',       'Industry-leading compensation with performance bonuses.'],
                ['fas fa-chart-line',    'Growth Opportunities',  'Clear career progression with senior mentorship.'],
                ['fas fa-users',         'Team Culture',          'A supportive, diverse team that celebrates wins together.'],
                ['fas fa-graduation-cap','Continuous Learning',   'Regular training, workshops, and industry resources.'],
                ['fas fa-star',          'Premium Brand',         'Represent one of the most respected real estate names.'],
                ['fas fa-heart',         'Work-Life Balance',     'Flexible schedules and a healthy work environment.'],
            ] as [$icon, $title, $desc]): ?>
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
            <p style="color:var(--text-muted)">We're hiring across multiple roles. Click on any role to view details and apply.</p>
        </div>

        <?php if (empty($jobs)): ?>
        <div style="text-align:center;padding:60px 20px;color:var(--text-muted);max-width:500px;margin:0 auto">
            <div style="width:72px;height:72px;background:var(--beige-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                <i class="fas fa-briefcase" style="font-size:28px;opacity:.4"></i>
            </div>
            <h3 style="font-size:1.2rem;font-weight:700;color:var(--maroon);margin-bottom:10px">No Open Positions Right Now</h3>
            <p style="font-size:14px;line-height:1.7">We don't have any openings at the moment, but we're always looking for great talent. Send your resume to
                <a href="mailto:<?= getSetting('contact_email','careers@luxeestate.com') ?>" style="color:var(--maroon);font-weight:600"><?= getSetting('contact_email','careers@luxeestate.com') ?></a>
                and we'll reach out when something comes up.
            </p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:16px;max-width:860px;margin:0 auto">
            <?php foreach ($jobs as $j): ?>
            <a href="<?= SITE_URL ?>/job/<?= $j['id'] ?>" style="text-decoration:none;display:flex;gap:20px;align-items:flex-start;background:#fff;border:1px solid var(--border-light);border-radius:12px;padding:24px 28px;transition:box-shadow .2s,transform .15s<?= $j['featured'] ? ';border-left:4px solid var(--gold)' : '' ?>" onmouseover="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none';this.style.transform='none'">
                <div style="flex:1">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px">
                        <h3 style="font-size:1.05rem;font-weight:700;color:var(--maroon);margin:0"><?= htmlspecialchars($j['title']) ?></h3>
                        <?php if ($j['department']): ?>
                        <span style="background:#fff8e1;color:#7A5C1A;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid var(--gold)"><?= htmlspecialchars($j['department']) ?></span>
                        <?php endif; ?>
                        <?php if ($j['featured']): ?>
                        <span style="background:#fef2f2;color:var(--danger);font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;border:1px solid var(--danger)"><i class="fas fa-fire"></i> Urgent Hiring</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($j['description']): ?>
                    <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;line-height:1.6"><?= mb_substr(strip_tags($j['description']), 0, 150) ?>…</p>
                    <?php endif; ?>
                    <div style="display:flex;gap:20px;font-size:12px;color:var(--text-muted);flex-wrap:wrap">
                        <?php if ($j['location']): ?><span><i class="fas fa-map-marker-alt" style="color:var(--maroon);margin-right:4px"></i><?= htmlspecialchars($j['location']) ?></span><?php endif; ?>
                        <?php if ($j['experience']): ?><span><i class="fas fa-briefcase" style="color:var(--gold);margin-right:4px"></i><?= htmlspecialchars($j['experience']) ?></span><?php endif; ?>
                    </div>
                </div>
                <span style="white-space:nowrap;background:var(--maroon);color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;align-self:center">View &amp; Apply →</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
