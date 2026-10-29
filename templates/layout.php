<?php
/** @var string $content */
/** @var string $csrf */
/** @var bool $loggedIn */
$nav = [
    '/' => ['bi-geo-alt', 'Einchecken'],
    '/settings' => ['bi-sliders', 'Einstellungen'],
    '/admin' => ['bi-gear', 'Admin'],
];
$current = $currentPath ?? '/';
?>
<!doctype html>
<html lang="de">
<head>
<?= $view->partial('partials/head', get_defined_vars()) ?>
</head>
<body>
<nav class="navbar navbar-expand-md bg-body-tertiary border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/"><i class="bi bi-pin-map-fill text-primary"></i> Check-in</a>
        <?php if ($loggedIn): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Navigation umschalten">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($nav as $href => [$icon, $label]): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $current === $href ? ' active' : '' ?>" href="<?= e($href) ?>"
                               <?= $current === $href ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" action="/logout" class="d-flex">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-box-arrow-right"></i> Abmelden</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</nav>
<main class="container py-3">
    <?= $view->partial('partials/flash', get_defined_vars()) ?>
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
