<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/mixed/includes/bootstrap.php';
require_login();

$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        save_feedback(
            $userId,
            (int) ($_POST['registration_id'] ?? 0),
            (int) ($_POST['rating'] ?? 0),
            $_POST['review_text'] ?? ''
        );
        flash('success', 'Your event feedback was saved.');
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }

    redirect('faisal/feedback.php');
}

$statement = db()->prepare(
    "SELECT
        er.registration_id,
        e.title,
        e.event_date,
        c.club_name,
        f.feedback_id,
        f.rating,
        f.review_text,
        f.status
     FROM event_registration er
     JOIN events e ON e.event_id = er.event_id
     JOIN clubs c ON c.club_id = e.club_id
     LEFT JOIN feedback f ON f.registration_id = er.registration_id
     WHERE er.student_user_id = ? AND er.registration_status = 'Attended'
     ORDER BY e.event_date DESC"
);
$statement->execute([$userId]);
$items = $statement->fetchAll();

$pageTitle = 'Event feedback';
require dirname(__DIR__) . '/mixed/includes/header.php';
?>
<section class="page-shell">
    <div class="page-head">
        <div>
            <span class="eyebrow">ATTENDED EVENTS</span>
            <h2>Your experience,<br><span class="accent-script">in your words.</span></h2>
            <p>Feedback is available after attendance has been confirmed as Present.</p>
        </div>
    </div>

    <div class="grid">
        <?php foreach ($items as $item): ?>
            <article class="card editor-card">
                <span class="card-tag"><?= e($item['club_name']) ?></span>
                <h3><?= e($item['title']) ?></h3>
                <p class="muted"><?= e(date('F j, Y', strtotime($item['event_date']))) ?></p>

                <?php if ($item['status'] && $item['status'] !== 'Visible'): ?>
                    <div class="alert alert-error">This feedback is <?= e(strtolower($item['status'])) ?> and cannot be edited.</div>
                <?php else: ?>
                    <form method="post" data-ajax="<?= e(app_url('faisal/api/feedback.php')) ?>">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="registration_id" value="<?= (int) $item['registration_id'] ?>">
                        <div class="field">
                            <label>Rating</label>
                            <select name="rating" required>
                                <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                    <option value="<?= $rating ?>" <?= (int) $item['rating'] === $rating ? 'selected' : '' ?>><?= $rating ?> / 5</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Review</label>
                            <textarea name="review_text" maxlength="2000"><?= e($item['review_text']) ?></textarea>
                        </div>
                        <button class="button button-primary"><?= $item['feedback_id'] ? 'Update feedback' : 'Submit feedback' ?></button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!$items): ?>
            <div class="empty">No attended events are ready for feedback yet.</div>
        <?php endif; ?>
    </div>
</section>
<?php require dirname(__DIR__) . '/mixed/includes/footer.php'; ?>
