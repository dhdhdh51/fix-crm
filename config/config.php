<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// =====================================================
// Database Configuration
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'hoxgmwvgwv_crm');
define('DB_USER', 'hoxgmwvgwv_crm');
define('DB_PASS', 'hoxgmwvgwv_crm');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
if (!defined('SITE_URL'))    define('SITE_URL',    'https://bharatseo.site');
if (!defined('SITE_ROOT'))   define('SITE_ROOT',   dirname(__DIR__));
if (!defined('UPLOAD_DIR'))  define('UPLOAD_DIR',  SITE_ROOT . '/uploads/');
if (!defined('UPLOAD_URL'))  define('UPLOAD_URL',  SITE_URL . '/uploads/');
if (!defined('ADMIN_URL'))   define('ADMIN_URL',   SITE_URL . '/admin');

// Security
if (!defined('CSRF_TOKEN_EXPIRE')) define('CSRF_TOKEN_EXPIRE', 3600);
if (!defined('SESSION_EXPIRE'))    define('SESSION_EXPIRE',    7200);
if (!defined('MAX_LOGIN_ATTEMPTS'))define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('LOGIN_LOCKOUT'))     define('LOGIN_LOCKOUT',      900);

// Image Settings
if (!defined('MAX_FILE_SIZE'))        define('MAX_FILE_SIZE',        5242880);
if (!defined('ALLOWED_IMAGE_TYPES'))  define('ALLOWED_IMAGE_TYPES',  ['image/jpeg', 'image/png', 'image/webp']);
if (!defined('THUMB_WIDTH'))          define('THUMB_WIDTH',           800);
if (!defined('THUMB_HEIGHT'))         define('THUMB_HEIGHT',          600);

// Pagination
if (!defined('PROPERTIES_PER_PAGE')) define('PROPERTIES_PER_PAGE', 9);
if (!defined('BLOGS_PER_PAGE'))      define('BLOGS_PER_PAGE',       6);
if (!defined('ADMIN_PER_PAGE'))      define('ADMIN_PER_PAGE',       15);
