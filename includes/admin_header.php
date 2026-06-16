<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);

function navLink(string $href, string $icon, string $label, string $current): string {
    $active = (basename($href) === $current) ? ' active' : '';
    $url = appUrl($href);
    return "<a href=\"$url\" class=\"$active\"><span class=\"nav-icon\">$icon</span>$label</a>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Survey Admin</title>
    <link rel="stylesheet" href="<?= appUrl('/assets/style.css') ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h1>Feedback &amp; Survey</h1>
            <span>Admin Panel</span>
        </div>
        <nav class="sidebar-nav">
            <?= navLink('/admin/dashboard.php', '#', 'Dashboard', $currentPage) ?>
            <div class="nav-section">Surveys</div>
            <?= navLink('/admin/surveys.php', 'S', 'All Surveys', $currentPage) ?>
            <?= navLink('/admin/questions.php', 'Q', 'Questions', $currentPage) ?>
            <?= navLink('/admin/responses.php', 'R', 'Responses', $currentPage) ?>
            <div class="nav-section">Users</div>
            <?= navLink('/admin/users.php', 'U', 'User Accounts', $currentPage) ?>
            <?= navLink('/admin/respondents.php', 'P', 'Respondents', $currentPage) ?>
            <div class="nav-section">System</div>
            <?= navLink('/admin/audit.php', 'A', 'Audit Trail', $currentPage) ?>
            <?= navLink('/admin/reports.php', 'T', 'Reports', $currentPage) ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?= appUrl('/logout.php') ?>">Sign out</a>
        </div>
    </aside>

    <div class="main-wrap">
        <div class="topbar">
            <span class="topbar-title"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></span>
            <div class="topbar-user">
                <div class="topbar-avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
                <?= currentUserName() ?>
            </div>
        </div>
        <div class="page-content">
            <?php if ($flash): ?>
                <div class="flash flash-<?= $flash['type'] ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>
