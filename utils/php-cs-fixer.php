<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
	->exclude('log')
	->exclude('temp')
	->in(__DIR__ . '/..')
	->files()
	->name('*.phpt');

$rules = [
	'@Symfony' => true,
	'@Symfony:risky' => true,
	'@PHP81Migration' => true,
	'@PHP80Migration:risky' => true,

	// overwrite some Symfony rules
	'braces_position' => [
		'functions_opening_brace' => 'same_line',
		'classes_opening_brace' => 'same_line',
	],
	'function_declaration' => [
		'closure_function_spacing' => 'none',
		'closure_fn_spacing' => 'none',
	],
	'concat_space' => ['spacing' => 'one'],
	// Inconsistent with arguments without default value.
	'nullable_type_declaration_for_default_null_value' => [
		'use_nullable_type_declaration' => true,
	],
	'phpdoc_align' => false,
	'yoda_style' => false,
	'global_namespace_import' => false,

	// additional rules
	'array_syntax' => ['syntax' => 'short'],
	'modernize_types_casting' => true,
	'ordered_imports' => true,
	'phpdoc_add_missing_param_annotation' => true,
	'phpdoc_order' => true,
	'strict_param' => true,
];

$config = new PhpCsFixer\Config();

return $config
	->setRules($rules)
	->setIndent("\t")
	->setRiskyAllowed(true)
	->setFinder($finder);
