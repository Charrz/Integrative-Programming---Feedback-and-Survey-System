<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$token = trim($_GET['token'] ?? '');
$survey = null;
$questions = [];

if ($token !== '') {
    $stmt = $conn->prepare("SELECT surveyId, title, description FROM surveys WHERE shareToken = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $survey = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($survey) {
    $stmt = $conn->prepare("SELECT questionId, question, questionType FROM questions WHERE surveyId = ? ORDER BY questionId");
    $stmt->bind_param('i', $survey['surveyId']);
    $stmt->execute();
    $questionRows = $stmt->get_result();
    while ($question = $questionRows->fetch_assoc()) {
        $question['options'] = [];
        if ($question['questionType'] === 'mcq') {
            $optStmt = $conn->prepare("SELECT optionsId, optionText FROM question_options WHERE questionId = ? ORDER BY optionsId");
            $optStmt->bind_param('i', $question['questionId']);
            $optStmt->execute();
            $question['options'] = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $optStmt->close();
        }
        $questions[] = $question;
    }
    $stmt->close();
}

$submitted = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $survey) {
    $answers = $_POST['answers'] ?? [];

    if (empty($questions)) {
        $error = 'This survey has no questions yet.';
    } else {
        $conn->begin_transaction();
        try {
            $sessionId = session_id();
            $stmt = $conn->prepare("INSERT IGNORE INTO respondents (sessionId) VALUES (?)");
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("SELECT respondentId FROM respondents WHERE sessionId = ? LIMIT 1");
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();
            $respondent = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO responses (surveyId, respondentId, submittedAt) VALUES (?, ?, NOW())");
            $stmt->bind_param('ii', $survey['surveyId'], $respondent['respondentId']);
            $stmt->execute();
            $responseId = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO answers (responseId, questionId, optionsId) VALUES (?, ?, ?)");
            foreach ($questions as $question) {
                if ($question['questionType'] !== 'mcq') {
                    continue;
                }
                $optionId = (int)($answers[$question['questionId']] ?? 0);
                if ($optionId <= 0) {
                    throw new Exception('Please answer all multiple choice questions.');
                }
                $stmt->bind_param('iii', $responseId, $question['questionId'], $optionId);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
            $submitted = true;
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $survey ? htmlspecialchars($survey['title']) : 'Survey' ?></title>
    <link rel="stylesheet" href="<?= appUrl('/assets/style.css') ?>">
</head>
<body>
<div class="survey-page">
    <div class="survey-header">
        <h1><?= $survey ? htmlspecialchars($survey['title']) : 'Survey not found' ?></h1>
        <?php if ($survey && $survey['description']): ?>
            <p><?= htmlspecialchars($survey['description']) ?></p>
        <?php endif; ?>
    </div>
    <div class="survey-body">
        <?php if (!$survey): ?>
            <div class="card"><div class="card-body">This survey link is invalid or no longer available.</div></div>
        <?php elseif ($submitted): ?>
            <div class="survey-success">
                <div class="check-icon">OK</div>
                <h2>Thank you</h2>
                <p>Your response has been submitted.</p>
            </div>
        <?php else: ?>
            <?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card">
                        <div class="q-num">Question <?= $index + 1 ?></div>
                        <div class="q-text"><?= htmlspecialchars($question['question']) ?></div>
                        <?php if ($question['questionType'] === 'mcq'): ?>
                            <ul class="options-list">
                                <?php foreach ($question['options'] as $option): ?>
                                    <li class="option-item">
                                        <label>
                                            <input type="radio" name="answers[<?= (int)$question['questionId'] ?>]" value="<?= (int)$option['optionsId'] ?>" required>
                                            <span><?= htmlspecialchars($option['optionText']) ?></span>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="form-hint">This question type is displayed, but the current database stores only multiple choice answers.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="survey-submit-bar">
                    <span><?= count($questions) ?> question(s)</span>
                    <button type="submit" class="btn btn-primary">Submit Response</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
