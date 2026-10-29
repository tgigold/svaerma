<?php
/** @var int $radius */
/** @var int $minRadius */
/** @var int $maxRadius */
/** @var string $csrf */
$title = 'Einstellungen';
?>
<h1 class="h3 mb-3">Einstellungen</h1>
<div class="card">
    <div class="card-body">
        <form method="post" action="/settings">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label class="form-label" for="nearby_radius">Suchradius für Orte in der Nähe</label>
            <div class="input-group mb-1 input-narrow">
                <input class="form-control" type="number" id="nearby_radius" name="nearby_radius" value="<?= e($radius) ?>"
                       min="<?= e($minRadius) ?>" max="<?= e($maxRadius) ?>" step="10" required>
                <span class="input-group-text">m</span>
            </div>
            <div class="form-text mb-3">Standard: 150 m. Bei ungenauem GPS wird der Radius automatisch bis zur Messgenauigkeit vergrößert.</div>
            <button class="btn btn-primary" type="submit">Speichern</button>
        </form>
    </div>
</div>
