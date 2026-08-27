<?php

declare(strict_types=1);

require __DIR__ . '/_json.php';

try {
    $feedbackId = save_feedback(
        current_user_id(),
        (int) ($_POST['registration_id'] ?? 0),
        (int) ($_POST['rating'] ?? 0),
        $_POST['review_text'] ?? ''
    );

    json_response(true, 'Your event feedback was saved.', ['feedback_id' => $feedbackId]);
} catch (InvalidArgumentException $exception) {
    json_response(false, $exception->getMessage(), [], 422);
} catch (DomainException $exception) {
    json_response(false, $exception->getMessage(), [], 403);
} catch (Throwable $exception) {
    json_response(false, $exception->getMessage(), [], 409);
}
