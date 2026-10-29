<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Response;
use App\Model\Checkin;

final class CheckinController extends Controller
{
    /** @param array<string, string> $params */
    public function create(array $params): Response
    {
        return $this->render('checkin/create', [
            'radius' => $this->app->nearbyRadius(),
        ]);
    }

    /** @param array<string, string> $params */
    public function store(array $params): Response
    {
        $result = $this->app->checkinService()->create($this->request->post, 'web');
        if (!$result instanceof Checkin) {
            foreach ($result as $error) {
                $this->flash('danger', $error);
            }
            return $this->redirect('/');
        }
        $this->flash('success', 'Eingecheckt: ' . $result->placeName);
        return $this->redirect('/');
    }
}
