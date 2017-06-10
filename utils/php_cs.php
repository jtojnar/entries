<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__)
	->in(__DIR__ . '/../app')
	->in(__DIR__ . '/../tests')
	->in(__DIR__ . '/../www');

$rules = [
	'@Symfony' => true,
	// why would anyone put braces on different line
	'braces' => ['position_after_functions_and_oop_constructs' => 'same'],
	'function_declaration' => ['closure_function_spacing' => 'none'],
	// overwrite some Symfony rules
	'concat_space' => ['spacing' => 'one'],
	'phpdoc_align' => false,
	'phpdoc_no_empty_return' => false,
	'phpdoc_summary' => false,
	'trailing_comma_in_multiline_array' => false,
	// additional rules
	'array_syntax' => ['syntax' => 'short'],
	'dir_constant' => true,
	'is_null' => ['use_yoda_style' => false],
	'modernize_types_casting' => true,
	'no_alias_functions' => true,
	'ordered_imports' => true,
	'phpdoc_add_missing_param_annotation' => true,
	'phpdoc_order' => true,
	'phpdoc_no_alias_tag' => false,
	'strict_comparison' => true,
	'strict_param' => true,
];

return PhpCsFixer\Config::create()
	->setIndent("\t")
	->setRules($rules)
	->setRiskyAllowed(true)
	->setFinder($finder);
