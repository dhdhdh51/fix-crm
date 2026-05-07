<?php
/**
 * Admin — Blog Posts
 * POST processing runs before ANY output so header('Location:') works.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/functions.php';
requireAdmin();

$db = db();

// ── Handle POST before any output ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type'=>'danger','msg'=>'Security token expired. Please try again.'];
        header('Location: ' . ADMIN_URL . '/blogs.php'); exit;
    }

    $action = trim($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id  = (int)($_POST['id'] ?? 0);
        $q   = $db->prepare("SELECT featured_image FROM blogs WHERE id=?");
        $q->execute([$id]);
        $path = $q->fetchColumn();
        if ($path) deleteImage($path);
        $db->prepare("DELETE FROM blogs WHERE id=?")->execute([$id]);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Post deleted.'];
        header('Location: ' . ADMIN_URL . '/blogs.php'); exit;
    }

    if (in_array($action, ['add_blog','update_blog'])) {
        $title     = trim($_POST['title'] ?? '');
        $slug      = trim($_POST['slug'] ?? '');
        $category  = trim($_POST['category'] ?? '');
        $excerpt   = trim($_POST['excerpt'] ?? '');
        $content   = $_POST['content'] ?? '';
        $tags      = trim($_POST['tags'] ?? '');
        $status    = trim($_POST['status'] ?? 'draft');
        $featured  = isset($_POST['featured']) ? 1 : 0;
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_desc  = trim($_POST['meta_desc'] ?? '');
        $editId    = (int)($_POST['edit_id'] ?? 0);

        if (empty($slug)) $slug = generateSlug($title);
        $slug = uniqueSlug($slug, 'blogs', $editId ?: 0);

        // Image upload
        $imgPath = trim($_POST['existing_image'] ?? '');
        if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $res = uploadImage($_FILES['image'], 'blogs');
            if ($res['success']) {
                if ($imgPath) deleteImage($imgPath);
                $imgPath = $res['path'];
            }
        }

        try {
            if ($editId) {
                $stmt = $db->prepare("
                    UPDATE blogs SET title=?,slug=?,category=?,excerpt=?,content=?,tags=?,
                        status=?,featured=?,featured_image=?,meta_title=?,meta_desc=?,updated_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([$title,$slug,$category,$excerpt,$content,$tags,
                                $status,$featured,$imgPath,$meta_title,$meta_desc,$editId]);
            } else {
                $authorId = $_SESSION['admin_id'] ?? 1;
                $stmt = $db->prepare("
                    INSERT INTO blogs
                        (title,slug,category,excerpt,content,tags,status,featured,featured_image,meta_title,meta_desc,author_id,created_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())
                ");
                $stmt->execute([$title,$slug,$category,$excerpt,$content,$tags,
                                $status,$featured,$imgPath,$meta_title,$meta_desc,$authorId]);
            }
            $_SESSION['flash'] = ['type'=>'success','msg'=>$editId ? 'Post updated.' : 'Post published.'];
        } catch (PDOException $e) {
            error_log('Blog save error: ' . $e->getMessage());
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'Database error: ' . $e->getMessage()];
        }
        header('Location: ' . ADMIN_URL . '/blogs.php'); exit;
    }
}

// ── Edit mode ──
$editBlog = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM blogs WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editBlog = $s->fetch() ?: null;
}

$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = ADMIN_PER_PAGE;
$offset  = ($page - 1) * $perPage;
$total   = (int)$db->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$pages   = (int)ceil($total / $perPage);
$bStmt   = $db->prepare("SELECT b.*,u.name AS author FROM blogs b LEFT JOIN users u ON b.author_id=u.id ORDER BY b.created_at DESC LIMIT ? OFFSET ?");
$bStmt->bindValue(1, (int)$perPage, PDO::PARAM_INT);
$bStmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
$bStmt->execute();
$blogs   = $bStmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$csrf       = generateCSRF();
$pageTitle  = 'Blog Posts';
$activePage = 'blogs';
require_once __DIR__ . '/layout-header.php';
?>

<div class="page-header">
    <div><h1><i class="fas fa-newspaper" style="color:var(--maroon)"></i> Blog Posts</h1></div>
    <button onclick="toggleForm()" class="btn btn-gold" id="addBlogBtn">
        <i class="fas fa-plus"></i> <?= $editBlog ? 'Edit Post' : 'New Post' ?>
    </button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?>">
    <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-triangle' ?>"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Add/Edit Form -->
<div id="blogFormWrap" style="display:<?= $editBlog ? 'block' : 'none' ?>;margin-bottom:20px">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><?= $editBlog ? 'Edit: '.htmlspecialchars($editBlog['title']) : 'New Blog Post' ?></span>
            <button type="button" onclick="toggleForm(false)" class="btn btn-sm btn-gray"><i class="fas fa-times"></i></button>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="<?= $editBlog ? 'update_blog' : 'add_blog' ?>">
                <?php if ($editBlog): ?>
                <input type="hidden" name="edit_id" value="<?= $editBlog['id'] ?>">
                <?php if ($editBlog['featured_image'] ?? null): ?>
                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editBlog['featured_image']) ?>">
                <?php endif; ?>
                <?php endif; ?>

                <div class="form-grid" style="margin-bottom:14px">
                    <div class="form-group form-full">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="blogTitle" value="<?= htmlspecialchars($editBlog['title']??'') ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" id="blogSlug" value="<?= htmlspecialchars($editBlog['slug']??'') ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" value="<?= htmlspecialchars($editBlog['category']??'') ?>"
                               list="blogCats" class="form-control" placeholder="e.g. Buying Guide">
                        <datalist id="blogCats">
                            <?php $cats=$db->query("SELECT DISTINCT category FROM blogs WHERE category!='' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($cats as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Featured Image</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control">
                        <?php if ($editBlog && ($editBlog['featured_image'] ?? null)): ?>
                        <div style="margin-top:8px">
                            <img src="<?= SITE_URL . '/' . htmlspecialchars($editBlog['featured_image']) ?>" style="height:60px;border-radius:6px;object-fit:cover">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Excerpt</label>
                        <input type="text" name="excerpt" value="<?= htmlspecialchars($editBlog['excerpt']??'') ?>" class="form-control" placeholder="Short summary (used in blog cards)">
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Content</label>
                        <textarea name="content" id="blogContent" rows="12" class="form-control"><?= htmlspecialchars($editBlog['content']??'') ?></textarea>
                        <span class="form-hint">Basic HTML is supported (p, h2, h3, strong, em, ul, ol, li, a)</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tags (comma separated)</label>
                        <input type="text" name="tags" value="<?= htmlspecialchars($editBlog['tags']??'') ?>" class="form-control" placeholder="buying, investment, tips">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="published" <?= ($editBlog['status']??'')==='published'?'selected':'' ?>>Published</option>
                            <option value="draft"     <?= ($editBlog['status']??'draft')==='draft'?'selected':'' ?>>Draft</option>
                        </select>
                    </div>
                    <div class="form-group" style="justify-content:flex-end;padding-top:24px">
                        <label class="form-check">
                            <input type="checkbox" name="featured" <?= !empty($editBlog['featured'])?'checked':'' ?>>
                            <span class="form-check-label"><i class="fas fa-star" style="color:var(--gold)"></i> Feature on Homepage</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= $editBlog ? 'Update Post' : 'Publish Post' ?></button>
                <button type="button" onclick="toggleForm(false)" class="btn btn-gray" style="margin-left:8px">Cancel</button>
            </form>
        </div>
    </div>
</div>

<!-- Blog List -->
<div class="card">
    <div class="table-wrap">
        <?php if (empty($blogs)): ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted)">
            <i class="fas fa-newspaper" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px"></i>
            No blog posts yet. <button onclick="toggleForm()" class="btn btn-sm btn-gold" style="margin-left:8px">Write First Post</button>
        </div>
        <?php else: ?>
        <table>
            <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Views</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($blogs as $b): ?>
            <tr>
                <td><?php if ($b['featured_image'] ?? null): ?>
                    <img src="<?= SITE_URL . '/' . htmlspecialchars($b['featured_image']) ?>" class="table-img">
                <?php else: ?>
                    <div class="table-no-img"><i class="fas fa-image"></i></div>
                <?php endif; ?></td>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($b['title']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($b['slug']) ?></div>
                    <?php if ($b['featured']): ?><span class="badge badge-gold" style="margin-top:3px"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
                </td>
                <td><?= $b['category'] ? '<span class="badge badge-maroon">'.htmlspecialchars($b['category']).'</span>' : '—' ?></td>
                <td style="font-size:12px"><?= htmlspecialchars($b['author'] ?? 'Admin') ?></td>
                <td><span class="badge <?= $b['status']==='published'?'badge-green':'badge-gray' ?>"><?= ucfirst($b['status']) ?></span></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= number_format($b['views']??0) ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:4px">
                        <a href="<?= SITE_URL ?>/blog/<?= htmlspecialchars($b['slug']) ?>" target="_blank" class="btn btn-sm btn-icon btn-gray"><i class="fas fa-eye"></i></a>
                        <a href="?edit=<?= $b['id'] ?>#blogFormWrap" class="btn btn-sm btn-icon btn-outline" onclick="toggleForm(true)"><i class="fas fa-edit"></i></a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this post?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-sm btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pages > 1): ?>
        <div class="pagination-bar">
            <?php for ($i=1;$i<=$pages;$i++): ?>
            <a href="?page=<?= $i ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleForm(show) {
    const wrap = document.getElementById('blogFormWrap');
    if (show === undefined) wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
    else wrap.style.display = show ? 'block' : 'none';
    if (wrap.style.display === 'block') wrap.scrollIntoView({behavior:'smooth'});
}
document.getElementById('blogTitle')?.addEventListener('input', function() {
    const s = document.getElementById('blogSlug');
    if (!s.dataset.m) s.value = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-');
});
document.getElementById('blogSlug')?.addEventListener('input', function() { this.dataset.m = this.value ? '1' : ''; });
</script>

<?php require_once __DIR__ . '/layout-footer.php'; ?>
