<?php /** @var list<array{type: string, message: string}> $flash */ ?>
<?php foreach ($flash ?? [] as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endforeach; ?>
