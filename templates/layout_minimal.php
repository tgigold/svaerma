<?php /** @var string $content */ ?>
<!doctype html>
<html lang="de">
<head>
<?= $view->partial('partials/head', get_defined_vars()) ?>
</head>
<body class="bg-body-tertiary d-flex flex-column min-vh-100">
<main class="container container-narrow py-5 flex-grow-1">
    <div class="text-center mb-4">
        <i class="bi bi-pin-map-fill text-primary display-5"></i>
        <h1 class="h4 mt-2"><?= e(App\App::NAME) ?></h1>
    </div>
    <?= $view->partial('partials/flash', get_defined_vars()) ?>
    <?= $content ?>
</main>
<?= $view->partial('partials/footer', get_defined_vars()) ?>
<?= $view->partial('partials/scripts', get_defined_vars()) ?>
</body>
</html>
