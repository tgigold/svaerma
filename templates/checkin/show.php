<?php
/** @var App\Model\Checkin $checkin */
/** @var list<App\Model\Checkin> $visits */
/** @var string $tileUrl */
/** @var string $csrf */
use App\Format;

$title = $checkin->placeName;
$local = $checkin->localTime();
$others = array_values(array_filter($visits, static fn ($v) => $v->id !== $checkin->id));
?>
<nav aria-label="breadcrumb" class="small mb-2"><a href="/checkins"><i class="bi bi-chevron-left"></i> Verlauf</a></nav>

<h1 class="h3 mb-1"><?= e($checkin->placeName) ?></h1>
<p class="text-body-secondary mb-3">
    <?= e(Format::day($local)) ?>, <?= e($local->format('H:i')) ?> Uhr
    <?php if ($checkin->timezone !== ($app->config->get('timezone'))): ?>
        <span class="small">(<?= e($checkin->timezone) ?>)</span>
    <?php endif; ?>
</p>

<?php if ($checkin->note !== null): ?>
    <div class="card mb-3"><div class="card-body note"><?= nl2br(e($checkin->note)) ?></div></div>
<?php endif; ?>

<div class="map-small rounded border mb-3" data-map-point
     data-lat="<?= e($checkin->lat) ?>" data-lon="<?= e($checkin->lon) ?>"
     data-name="<?= e($checkin->placeName) ?>" data-tiles="<?= e($tileUrl) ?>"></div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-outline-primary btn-sm" href="/checkin/<?= e($checkin->id) ?>/edit"><i class="bi bi-pencil"></i> Bearbeiten</a>
    <?php if ($checkin->osmUrl() !== null): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e($checkin->osmUrl()) ?>" rel="noopener" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> OpenStreetMap
        </a>
    <?php endif; ?>
    <form method="post" action="/checkin/<?= e($checkin->id) ?>/delete" class="ms-auto"
          data-confirm="Check-in „<?= e($checkin->placeName) ?>“ wirklich löschen?">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Löschen</button>
    </form>
</div>

<?php if ($checkin->osmType !== null): ?>
    <h2 class="h6">Deine Besuche hier</h2>
    <?php if ($others === []): ?>
        <p class="small text-body-secondary">Dein erster Check-in an diesem Ort.</p>
    <?php else: ?>
        <p class="small text-body-secondary"><?= e(count($visits)) ?>× hier eingecheckt.</p>
        <ul class="list-unstyled small">
            <?php foreach (array_slice($others, 0, 20) as $v): ?>
                <li><a href="<?= e($v->path()) ?>"><?= e(Format::dateTime($v->localTime())) ?></a><?= $v->note !== null ? ' – ' . e(mb_strimwidth($v->note, 0, 80, '…')) : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<p class="small text-body-tertiary mt-4 mb-0">
    <?= e(number_format($checkin->lat, 5, ',', '')) ?>, <?= e(number_format($checkin->lon, 5, ',', '')) ?>
    · Quelle: <?= e(['web' => 'Web', 'api' => 'API', 'swarm' => 'Swarm-Import'][$checkin->source] ?? $checkin->source) ?>
</p>
