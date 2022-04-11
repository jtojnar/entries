<?php

declare(strict_types=1);

namespace App\Helpers;

use Contributte\Translation;

class NeonIntlLoader extends Translation\Loaders\Neon {
	/**
	 * {@inheritdoc}
	 */
	public function load(
		$resource,
		string $locale,
		string $domain = 'messages'
	) {
		return parent::load($resource, $locale, $domain . '+intl-icu');
	}
}
