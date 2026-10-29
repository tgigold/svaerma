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
<?= $view->partial('partials/scripts', get_defined_vars()) ?>
</body>
</html>
