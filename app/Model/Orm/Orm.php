<?php

declare(strict_types=1);

namespace App\Model\Orm;

use Nextras\Orm\Model\Model;

/**
 * Model.
 *
 * @property Person\PersonRepository $persons
 * @property Team\TeamRepository $teams
 * @property Country\CountryRepository $countries
 * @property Invoice\InvoiceRepository $invoices
 * @property ItemReservation\ItemReservationRepository $itemReservations
 * @property Message\MessageRepository $messages
 * @property Token\TokenRepository $tokens
 */
final class Orm extends Model {
}
