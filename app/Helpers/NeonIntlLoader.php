<?php

declare(strict_types=1);

namespace App\Helpers;

use Contributte\Translation;
use Override;
use Symfony\Component\Translation\MessageCatalogue;

final class NeonIntlLoader extends Translation\Loaders\Neon {
	#[Override]
	public function load(
		mixed $resource,
		string $locale,
		string $domain = 'messages',
	): MessageCatalogue {
		return parent::load($resource, $locale, $domain . '+intl-icu');
	}
}
