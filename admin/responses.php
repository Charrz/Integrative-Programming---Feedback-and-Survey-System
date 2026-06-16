<?php
$pageTitle = 'Responses';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/db.php';

$responses = $conn->query(
    "SELECT r.responseId, r.submittedAt, s.title, rp.sessionId
     FROM responses r
     JOIN surveys s ON r.surveyId = s.surveyId
     JOIN respondents rp ON r.respondentId = rp.respondentId
     ORDER BY r.submittedAt DESC"
);
?>

<div class="card">
    <div class="card-header">
        <h2>Responses</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Survey</th>
                    <th>Respondent Session</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($responses && $responses->num_rows): ?>
                <?php while ($row = $responses->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row['responseId'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['sessionId']) ?></td>
                        <td><?= date('M j, Y g:i a', strtotime($row['submittedAt'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><h3>No responses yet</h3><p>Submitted survey responses will appear here.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
