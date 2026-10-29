<?php

declare(strict_types=1);

namespace App\Service\Osm;

/** An OSM service could not be reached or returned garbage. Message is user-facing (German). */
final class OsmException extends \RuntimeException
{
}
