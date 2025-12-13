<?php

declare(strict_types=1);

// SPDX-License-Identifier: BSD-3-Clause
// Copyright 2004, 2014 David Grudl

namespace App\Components;

use Nette;
use Nette\Utils\Html;
use Stringable;

/**
 * A subset of `Nette\Forms\Helpers` modified to work better with Bootstrap styles.
 */
final class Helpers {
	/**
	 * The difference from `Nette\Forms\Helpers` is that we move `label` tag after the `input` tag, instead of having the former wrap the latter.
	 *
	 * @param array<string, mixed> $inputAttrs
	 * @param array<string, mixed> $labelAttrs
	 * @param Html|Stringable|null $wrapper
	 */
	public static function createInputList(
		array $items,
		?array $inputAttrs = null,
		?array $labelAttrs = null,
		$wrapper = null,
	): string {
		[$inputAttrs, $inputTag] = self::prepareAttrs($inputAttrs, 'input');
		[$labelAttrs, $labelTag] = self::prepareAttrs($labelAttrs, 'label');
		$res = '';
		$input = Html::el();
		$label = Html::el();
		[$wrapper, $wrapperEnd] = $wrapper instanceof Html ? [$wrapper->startTag(), $wrapper->endTag()] : [(string) $wrapper, ''];

		foreach ($items as $value => $caption) {
			foreach ($inputAttrs as $k => $v) {
				$input->attrs[$k] = $v[$value] ?? null;
			}

			foreach ($labelAttrs as $k => $v) {
				$label->attrs[$k] = $v[$value] ?? null;
			}

			$input->value = $value;
			$res .= ($res === '' && $wrapperEnd === '' ? '' : $wrapper)
				. $inputTag . $input->attributes() . '>'
				. $labelTag . $label->attributes() . '>'
				. ($caption instanceof Nette\HtmlStringable ? $caption : htmlspecialchars((string) $caption, \ENT_NOQUOTES, 'UTF-8'))
				. '</label>'
				. $wrapperEnd;
		}

		return $res;
	}

	/**
	 * This is copied as is from `Nette\Forms\Helpers` since we cannot access it from here.
	 *
	 * @param array<string, mixed> $attrs
	 *
	 * @return array{array<string, array<string, mixed>>, string}
	 */
	private static function prepareAttrs(?array $attrs, string $name): array {
		$dynamic = [];
		foreach ((array) $attrs as $k => $v) {
			if ($k[-1] === '?' || $k[-1] === ':') {
				$p = substr($k, 0, -1);
				unset($attrs[$k], $attrs[$p]);
				if ($k[-1] === '?') {
					$dynamic[$p] = array_fill_keys((array) $v, value: true);
				} elseif (\is_array($v) && $v) {
					$dynamic[$p] = $v;
				} else {
					$attrs[$p] = $v;
				}
			}
		}

		return [$dynamic, '<' . $name . Html::el(null, $attrs)->attributes()];
	}
}
