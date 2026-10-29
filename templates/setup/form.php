<?php
/** @var list<string> $problems */
/** @var list<string> $errors */
/** @var array<string, string> $input */
/** @var int $minLength */
/** @var string $csrf */
$title = 'Einrichtung';
?>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5 card-title">Einrichtung</h2>

        <?php if ($problems !== []): ?>
            <div class="alert alert-danger">
                <p class="mb-2">Bevor es losgeht, muss Folgendes behoben werden:</p>
                <ul class="mb-0">
                    <?php foreach ($problems as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p class="text-body-secondary small">
                Zum Schutz vor Fremden brauchst du einen Einmal-Code. Er liegt auf dem Server in
                <code>data/setup-token.txt</code> – lade die Datei per FTP herunter oder öffne sie im FTP-Programm.
            </p>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="/setup" novalidate>
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <div class="mb-3">
                    <label class="form-label" for="token">Setup-Code</label>
                    <input class="form-control token-code" id="token" name="token" required autocomplete="off" autocapitalize="off">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Passwort</label>
                    <input class="form-control" type="password" id="password" name="password" required
                           minlength="<?= e($minLength) ?>" autocomplete="new-password">
                    <div class="form-text">Mindestens <?= e($minLength) ?> Zeichen.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Passwort wiederholen</label>
                    <input class="form-control" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="contact_email">Kontakt-Mail</label>
                    <input class="form-control" type="email" id="contact_email" name="contact_email" required
                           value="<?= e($input['contact_email']) ?>">
                    <div class="form-text">Wird im User-Agent an Overpass, Photon und Nominatim mitgeschickt (Fair-Use-Regeln).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="base_url">Adresse der App</label>
                    <input class="form-control" type="url" id="base_url" name="base_url" required value="<?= e($input['base_url']) ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="timezone">Zeitzone</label>
                    <input class="form-control" id="timezone" name="timezone" required value="<?= e($input['timezone']) ?>">
                </div>
                <button class="btn btn-primary w-100" type="submit">Einrichten</button>
            </form>
        <?php endif; ?>
    </div>
</div>
