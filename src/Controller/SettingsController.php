<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;
use App\Service\Osm\Overpass;

final class SettingsController extends Controller
{
    /** @param array<string, string> $params */
    public function index(array $params): Response
    {
        return $this->render('settings/index', [
            'radius' => $this->app->nearbyRadius(),
            'minRadius' => Overpass::MIN_RADIUS,
            'maxRadius' => Overpass::MAX_RADIUS,
        ]);
    }

    /** @param array<string, string> $params */
    public function save(array $params): Response
    {
        $radius = filter_var($this->request->input('nearby_radius'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => Overpass::MIN_RADIUS, 'max_range' => Overpass::MAX_RADIUS],
        ]);
        if ($radius === false) {
            $this->flash('danger', sprintf('Der Umkreis muss zwischen %d und %d m liegen.', Overpass::MIN_RADIUS, Overpass::MAX_RADIUS));
            return $this->redirect('/settings');
        }
        $this->app->settings()->set('nearby_radius', (string) $radius);
        $this->flash('success', 'Einstellungen gespeichert.');
        return $this->redirect('/settings');
    }
}
