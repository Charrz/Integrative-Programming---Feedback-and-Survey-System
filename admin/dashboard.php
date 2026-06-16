<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/db.php';

// Gather summary counts
$counts = [];
foreach (['surveys','questions','respondents','responses'] as $tbl) {
    $r = $conn->query("SELECT COUNT(*) AS c FROM `$tbl`");
    $counts[$tbl] = (int)$r->fetch_assoc()['c'];
}

// Recent surveys
$recentSurveys = $conn->query(
    "SELECT s.surveyId, s.title, s.createdAt, u.userName,
            (SELECT COUNT(*) FROM responses r WHERE r.surveyId = s.surveyId) AS responseCount
     FROM surveys s
     JOIN user u ON s.userId = u.userId
     ORDER BY s.createdAt DESC LIMIT 5"
);

// Recent audit trail
$recentAudit = $conn->query(
    "SELECT a.action, a.timestamp, u.userName
     FROM audit_trail a
     JOIN user u ON a.userId = u.userId
     ORDER BY a.timestamp DESC LIMIT 8"
);
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Surveys</div>
        <div class="stat-value"><?= $counts['surveys'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Questions</div>
        <div class="stat-value"><?= $counts['questions'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Respondents</div>
        <div class="stat-value"><?= $counts['respondents'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Responses</div>
        <div class="stat-value"><?= $counts['responses'] ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Recent Surveys -->
    <div class="card">
        <div class="card-header">
            <h2>Recent Surveys</h2>
            <a href="<?= appUrl('/admin/surveys.php') ?>" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Created By</th>
                        <th>Responses</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $recentSurveys->fetch_assoc()): ?>
                    <tr>
                        <td><a href="<?= appUrl('/admin/surveys.php?edit=' . $row['surveyId']) ?>"><?= htmlspecialchars($row['title']) ?></a></td>
                        <td><?= htmlspecialchars($row['userName']) ?></td>
                        <td><?= $row['responseCount'] ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($counts['surveys'] === 0): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--neutral-500);padding:24px;">No surveys yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Audit -->
    <div class="card">
        <div class="card-header">
            <h2>Recent Activity</h2>
            <a href="<?= appUrl('/admin/audit.php') ?>" class="btn btn-outline btn-sm">Full Log</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $recentAudit->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['userName']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['action']) ?></td>
                        <td style="white-space:nowrap;color:var(--neutral-500);font-size:12px;"><?= date('M j, g:i a', strtotime($row['timestamp'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
