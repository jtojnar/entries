<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->exclude('log')
	->exclude('temp')
	->in(__DIR__ . '/..');

$rules = [
	'@Symfony' => true,
	'@Symfony:risky' => true,
	'@PHP71Migration' => true,
	'@PHP71Migration:risky' => true,

	// overwrite some Symfony rules
	'braces' => ['position_after_functions_and_oop_constructs' => 'same'],
	'function_declaration' => ['closure_function_spacing' => 'none'],
	'concat_space' => ['spacing' => 'one'],
	'phpdoc_align' => false,
	'yoda_style' => null,

	// additional rules
	'array_syntax' => ['syntax' => 'short'],
	'is_null' => ['use_yoda_style' => false],
	'modernize_types_casting' => true,
	'ordered_imports' => true,
	'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
	'phpdoc_order' => true,
	'strict_param' => true,
];

return PhpCsFixer\Config::create()
	->setRules($rules)
	->setIndent("\t")
	->setRiskyAllowed(true)
	->setFinder($finder);
