<?php
$pageTitle = 'Respondents';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/db.php';

$respondents = $conn->query(
    "SELECT rp.respondentId, rp.sessionId, COUNT(r.responseId) AS responseCount, MAX(r.submittedAt) AS lastSubmitted
     FROM respondents rp
     LEFT JOIN responses r ON rp.respondentId = r.respondentId
     GROUP BY rp.respondentId, rp.sessionId
     ORDER BY rp.respondentId DESC"
);
?>

<div class="card">
    <div class="card-header">
        <h2>Respondents</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Session</th>
                    <th>Responses</th>
                    <th>Last Submitted</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($respondents && $respondents->num_rows): ?>
                <?php while ($row = $respondents->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['respondentId'] ?></td>
                        <td><?= htmlspecialchars($row['sessionId']) ?></td>
                        <td><?= (int)$row['responseCount'] ?></td>
                        <td><?= $row['lastSubmitted'] ? date('M j, Y g:i a', strtotime($row['lastSubmitted'])) : '-' ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><h3>No respondents yet</h3><p>Respondents are created when public surveys are submitted.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
