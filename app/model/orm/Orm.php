<?php

declare(strict_types=1);

namespace App\Model;

use Nextras\Orm\Model\Model;

/**
 * Model.
 *
 * @property PersonRepository $persons
 * @property TeamRepository $teams
 * @property CountryRepository $countries
 * @property InvoiceRepository $invoices
 * @property MessageRepository $messages
 * @property TokenRepository $tokens
 */
class Orm extends Model {
}
