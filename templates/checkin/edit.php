<?php
/** @var App\Model\Checkin $checkin */
/** @var array<string, string> $input */
/** @var list<string> $errors */
/** @var list<string> $timezones */
/** @var string $csrf */
$title = 'Bearbeiten';
?>
<nav aria-label="breadcrumb" class="small mb-2"><a href="<?= e($checkin->path()) ?>"><i class="bi bi-chevron-left"></i> Zurück</a></nav>
<h1 class="h3 mb-3">Check-in bearbeiten</h1>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" action="/checkin/<?= e($checkin->id) ?>/edit" class="card card-body">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
    <div class="mb-3">
        <label class="form-label" for="place_name">Ort</label>
        <input class="form-control" id="place_name" name="place_name" required maxlength="200" value="<?= e($input['place_name']) ?>">
        <?php if ($checkin->osmType !== null): ?>
            <div class="form-text">Die Verknüpfung mit OpenStreetMap bleibt erhalten.</div>
        <?php endif; ?>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label class="form-label" for="checked_in_at">Zeitpunkt (Ortszeit)</label>
            <input class="form-control" type="datetime-local" id="checked_in_at" name="checked_in_at" required value="<?= e($input['checked_in_at']) ?>">
        </div>
        <div class="col-sm-6">
            <label class="form-label" for="timezone">Zeitzone</label>
            <input class="form-control" id="timezone" name="timezone" list="timezones" required value="<?= e($input['timezone']) ?>">
            <datalist id="timezones">
                <?php foreach ($timezones as $tz): ?><option value="<?= e($tz) ?>"></option><?php endforeach; ?>
            </datalist>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="note">Notiz</label>
        <textarea class="form-control" id="note" name="note" rows="4" maxlength="5000"><?= e($input['note']) ?></textarea>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Speichern</button>
        <a class="btn btn-outline-secondary" href="<?= e($checkin->path()) ?>">Abbrechen</a>
    </div>
</form>
