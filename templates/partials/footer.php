<?php /** @var bool|null $geoapifyActive */ ?>
<footer class="border-top py-3 small text-body-secondary site-footer">
    <div class="container d-flex flex-wrap justify-content-between">
        <span>
            Kartendaten © <a class="link-secondary" href="https://www.openstreetmap.org/copyright" rel="noopener" target="_blank">OpenStreetMap-Mitwirkende</a>,
            verfügbar unter der <a class="link-secondary" href="https://opendatacommons.org/licenses/odbl/" rel="noopener" target="_blank">ODbL</a>
        </span>
        <span>
            Suche: <a class="link-secondary" href="https://photon.komoot.io" rel="noopener" target="_blank">Photon</a> von komoot
            <?php if (!empty($geoapifyActive)): ?>
                · <a class="link-secondary" href="https://www.geoapify.com/" rel="noopener" target="_blank">Powered by Geoapify</a>
            <?php endif; ?>
        </span>
    </div>
</footer>
