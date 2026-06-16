<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/db.php';

$category = $_GET['category'] ?? 'all';
$status   = $_GET['status'] ?? '';
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');

$allowedCategories = ['all', 'surveys', 'responses', 'users', 'questions'];
$allowedStatuses = ['', 'active', 'inactive', 'with_responses', 'without_responses'];

if (!in_array($category, $allowedCategories)) {
    $category = 'all';
}
if (!in_array($status, $allowedStatuses)) {
    $status = '';
}

$runReport = function(string $sql, array $params = [], string $types = '') use ($conn) {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
};

$surveyWhere = ['1=1'];
$surveyParams = [];
$surveyTypes = '';

if ($from !== '') {
    $surveyWhere[] = 'DATE(s.createdAt) >= ?';
    $surveyParams[] = $from;
    $surveyTypes .= 's';
}
if ($to !== '') {
    $surveyWhere[] = 'DATE(s.createdAt) <= ?';
    $surveyParams[] = $to;
    $surveyTypes .= 's';
}
if ($status === 'with_responses') {
    $surveyWhere[] = 'EXISTS (SELECT 1 FROM responses r2 WHERE r2.surveyId = s.surveyId)';
}
if ($status === 'without_responses') {
    $surveyWhere[] = 'NOT EXISTS (SELECT 1 FROM responses r2 WHERE r2.surveyId = s.surveyId)';
}
$surveyWhereSql = implode(' AND ', $surveyWhere);

$responseWhere = ['1=1'];
$responseParams = [];
$responseTypes = '';

if ($from !== '') {
    $responseWhere[] = 'DATE(r.submittedAt) >= ?';
    $responseParams[] = $from;
    $responseTypes .= 's';
}
if ($to !== '') {
    $responseWhere[] = 'DATE(r.submittedAt) <= ?';
    $responseParams[] = $to;
    $responseTypes .= 's';
}
$responseWhereSql = implode(' AND ', $responseWhere);

$userWhere = ['1=1'];
$userParams = [];
$userTypes = '';

if ($status === 'active' || $status === 'inactive') {
    $userWhere[] = 'u.accountStatus = ?';
    $userParams[] = $status;
    $userTypes .= 's';
}
$userWhereSql = implode(' AND ', $userWhere);

$surveyCount = (int)$runReport(
    "SELECT COUNT(*) AS total FROM surveys s WHERE $surveyWhereSql",
    $surveyParams,
    $surveyTypes
)->fetch_assoc()['total'];
$responseCount = (int)$runReport(
    "SELECT COUNT(*) AS total FROM responses r WHERE $responseWhereSql",
    $responseParams,
    $responseTypes
)->fetch_assoc()['total'];
$userCount = (int)$runReport(
    "SELECT COUNT(*) AS total FROM user u WHERE $userWhereSql",
    $userParams,
    $userTypes
)->fetch_assoc()['total'];
$questionCount = (int)$conn->query("SELECT COUNT(*) AS total FROM questions")->fetch_assoc()['total'];
$respondentCount = (int)$conn->query("SELECT COUNT(*) AS total FROM respondents")->fetch_assoc()['total'];

$surveys = $runReport(
    "SELECT s.surveyId, s.title, s.createdAt, u.userName,
            COUNT(r.responseId) AS responseCount
     FROM surveys s
     JOIN user u ON s.userId = u.userId
     LEFT JOIN responses r ON s.surveyId = r.surveyId
     WHERE $surveyWhereSql
     GROUP BY s.surveyId, s.title, s.createdAt, u.userName
     ORDER BY s.createdAt DESC
     LIMIT 50",
    $surveyParams,
    $surveyTypes
);

$responses = $runReport(
    "SELECT r.responseId, r.submittedAt, s.title, rp.sessionId
     FROM responses r
     JOIN surveys s ON r.surveyId = s.surveyId
     JOIN respondents rp ON r.respondentId = rp.respondentId
     WHERE $responseWhereSql
     ORDER BY r.submittedAt DESC
     LIMIT 50",
    $responseParams,
    $responseTypes
);

$users = $runReport(
    "SELECT u.userId, u.userName, u.accountStatus, roles.roleName
     FROM user u
     JOIN roles ON u.roleId = roles.roleId
     WHERE $userWhereSql
     ORDER BY u.userName
     LIMIT 50",
    $userParams,
    $userTypes
);

$questions = $conn->query(
    "SELECT q.questionId, q.question, q.questionType, s.title
     FROM questions q
     JOIN surveys s ON q.surveyId = s.surveyId
     ORDER BY s.title, q.questionId
     LIMIT 50"
);

$qp = [
    'category' => $category,
    'status' => $status,
    'from' => $from,
    'to' => $to,
];

$showSurveys = $category === 'all' || $category === 'surveys';
$showResponses = $category === 'all' || $category === 'responses';
$showUsers = $category === 'all' || $category === 'users';
$showQuestions = $category === 'all' || $category === 'questions';
?>

<div class="card no-print" style="margin-bottom:20px;">
    <div class="card-header">
        <h2>Report Filters</h2>
        <button type="button" class="btn btn-outline btn-sm" onclick="printReport()">Print Report</button>
    </div>
    <div class="card-body" style="padding-bottom:14px;">
        <form method="GET" class="filter-bar">
            <label class="filter-field">
                <span>Category</span>
                <select name="category" class="form-control">
                    <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>All reports</option>
                    <option value="surveys" <?= $category === 'surveys' ? 'selected' : '' ?>>Surveys</option>
                    <option value="responses" <?= $category === 'responses' ? 'selected' : '' ?>>Responses</option>
                    <option value="users" <?= $category === 'users' ? 'selected' : '' ?>>Users</option>
                    <option value="questions" <?= $category === 'questions' ? 'selected' : '' ?>>Questions</option>
                </select>
            </label>
            <label class="filter-field">
                <span>Status</span>
                <select name="status" class="form-control">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>All statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active users</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive users</option>
                    <option value="with_responses" <?= $status === 'with_responses' ? 'selected' : '' ?>>Surveys with responses</option>
                    <option value="without_responses" <?= $status === 'without_responses' ? 'selected' : '' ?>>Surveys without responses</option>
                </select>
            </label>
            <label class="filter-field">
                <span>From date</span>
                <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
            </label>
            <label class="filter-field">
                <span>To date</span>
                <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
            </label>
            <button type="submit" class="btn btn-outline">Filter</button>
            <a href="<?= appUrl('/admin/reports.php') ?>" class="btn btn-outline">Reset</a>
        </form>
    </div>
</div>

<div class="report-heading">
    <h2>Printable System Report</h2>
    <p>
        Generated <?= date('M j, Y g:i a') ?>
        <?php if ($from || $to): ?>
            · Date range: <?= $from ? htmlspecialchars($from) : 'Any start' ?> to <?= $to ? htmlspecialchars($to) : 'Any end' ?>
        <?php endif; ?>
    </p>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Surveys</div><div class="stat-value"><?= $surveyCount ?></div></div>
    <div class="stat-card"><div class="stat-label">Responses</div><div class="stat-value"><?= $responseCount ?></div></div>
    <div class="stat-card"><div class="stat-label">Respondents</div><div class="stat-value"><?= $respondentCount ?></div></div>
    <div class="stat-card"><div class="stat-label">Users</div><div class="stat-value"><?= $userCount ?></div></div>
    <div class="stat-card"><div class="stat-label">Questions</div><div class="stat-value"><?= $questionCount ?></div></div>
</div>

<?php if ($showSurveys): ?>
<div class="card report-section">
    <div class="card-header"><h2>Survey Report</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Survey</th><th>Created By</th><th>Created</th><th>Responses</th></tr></thead>
            <tbody>
            <?php if ($surveys->num_rows): ?>
                <?php while ($survey = $surveys->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$survey['surveyId'] ?></td>
                        <td><?= htmlspecialchars($survey['title']) ?></td>
                        <td><?= htmlspecialchars($survey['userName']) ?></td>
                        <td><?= date('M j, Y', strtotime($survey['createdAt'])) ?></td>
                        <td><?= (int)$survey['responseCount'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5"><div class="empty-state"><h3>No surveys found</h3><p>Try changing the report filters.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($showResponses): ?>
<div class="card report-section">
    <div class="card-header"><h2>Response Report</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Survey</th><th>Respondent Session</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php if ($responses->num_rows): ?>
                <?php while ($response = $responses->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$response['responseId'] ?></td>
                        <td><?= htmlspecialchars($response['title']) ?></td>
                        <td><?= htmlspecialchars($response['sessionId']) ?></td>
                        <td><?= date('M j, Y g:i a', strtotime($response['submittedAt'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><h3>No responses found</h3><p>Try changing the date range.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($showUsers): ?>
<div class="card report-section">
    <div class="card-header"><h2>User Status Report</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Username</th><th>Role</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($users->num_rows): ?>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$user['userId'] ?></td>
                        <td><?= htmlspecialchars($user['userName']) ?></td>
                        <td><?= htmlspecialchars($user['roleName']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($user['accountStatus'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><h3>No users found</h3><p>Try changing the status filter.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($showQuestions): ?>
<div class="card report-section">
    <div class="card-header"><h2>Question Category Report</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Survey</th><th>Question</th><th>Type</th></tr></thead>
            <tbody>
            <?php if ($questions->num_rows): ?>
                <?php while ($question = $questions->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$question['questionId'] ?></td>
                        <td><?= htmlspecialchars($question['title']) ?></td>
                        <td><?= htmlspecialchars($question['question']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($question['questionType'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4"><div class="empty-state"><h3>No questions found</h3><p>Questions added to surveys will appear here.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
