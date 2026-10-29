<?php /** @var string $content */ ?>
<!doctype html>
<html lang="de">
<head>
<?= $view->partial('partials/head', get_defined_vars()) ?>
</head>
<body class="bg-body-tertiary">
<main class="container container-narrow py-5">
    <div class="text-center mb-4">
        <i class="bi bi-pin-map-fill text-primary display-5"></i>
        <h1 class="h4 mt-2">Check-in</h1>
    </div>
    <?= $view->partial('partials/flash', get_defined_vars()) ?>
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
