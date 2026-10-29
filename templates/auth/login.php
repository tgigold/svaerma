<?php
/** @var string $csrf */
/** @var string|null $error */
$title = 'Anmelden';
?>
<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/login">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="mb-3">
                <label class="form-label" for="password">Passwort</label>
                <input class="form-control" type="password" id="password" name="password" required autofocus autocomplete="current-password">
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right"></i> Anmelden</button>
        </form>
    </div>
</div>
