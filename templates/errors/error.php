<?php
/** @var int $status */
/** @var string $title */
/** @var string|null $message */
/** @var string|null $debug */
?>
<div class="card shadow-sm">
    <div class="card-body text-center">
        <p class="display-6 mb-1"><?= e($status) ?></p>
        <h2 class="h5"><?= e($title) ?></h2>
        <?php if ($message): ?><p class="text-body-secondary"><?= e($message) ?></p><?php endif; ?>
        <a class="btn btn-outline-primary mt-2" href="/">Zur Startseite</a>
    </div>
</div>
<?php if ($debug): ?>
    <pre class="mt-3 small bg-body p-3 border rounded"><?= e($debug) ?></pre>
<?php endif; ?>
