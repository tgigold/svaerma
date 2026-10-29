<?php
/** @var int $radius */
/** @var string $csrf */
$title = 'Einchecken';
?>
<div id="checkin" data-radius="<?= e($radius) ?>" data-max-radius="<?= e(App\Service\Osm\Overpass::MAX_RADIUS) ?>">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="flex-grow-1 small text-body-secondary" id="geo-status" role="status">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Standort wird ermittelt …
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="geo-refresh" aria-label="Standort neu ermitteln">
            <i class="bi bi-crosshair"></i>
        </button>
    </div>

    <form class="mb-3" id="search-form" role="search">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" type="search" id="search-q" placeholder="Ort suchen …" aria-label="Ort suchen" autocomplete="off" enterkeyhint="search">
            <button class="btn btn-outline-primary" type="submit">Suchen</button>
        </div>
    </form>

    <div id="places-message" class="alert d-none" role="alert"></div>
    <div class="list-group mb-3" id="places" aria-live="polite"></div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="widen">
            <i class="bi bi-arrows-angle-expand"></i> Umkreis erweitern
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#manual"
                aria-expanded="false" aria-controls="manual">
            <i class="bi bi-pencil"></i> Eigener Ort
        </button>
    </div>

    <div class="collapse mb-4" id="manual">
        <div class="card card-body">
            <label class="form-label" for="manual-name">Name des Orts</label>
            <div class="input-group">
                <input class="form-control" id="manual-name" maxlength="200" placeholder="z. B. Bei Oma im Garten">
                <button class="btn btn-primary" type="button" id="manual-use">Weiter</button>
            </div>
            <div class="form-text">Gespeichert wird dein aktueller Standort.</div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-bottom h-auto" tabindex="-1" id="confirm" aria-labelledby="confirm-title">
    <div class="offcanvas-header pb-0">
        <div>
            <h2 class="offcanvas-title h5 mb-0" id="confirm-title"></h2>
            <div class="small text-body-secondary" id="confirm-meta"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Schließen"></button>
    </div>
    <div class="offcanvas-body">
        <form method="post" action="/checkin" id="checkin-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="lat">
            <input type="hidden" name="lon">
            <input type="hidden" name="place_name">
            <input type="hidden" name="osm_type">
            <input type="hidden" name="osm_id">
            <input type="hidden" name="timezone">
            <label class="form-label visually-hidden" for="note">Notiz</label>
            <textarea class="form-control mb-3" id="note" name="note" rows="2" maxlength="5000" placeholder="Notiz (optional)"></textarea>
            <button class="btn btn-primary w-100 btn-lg" type="submit"><i class="bi bi-check2-circle"></i> Einchecken</button>
        </form>
    </div>
</div>

