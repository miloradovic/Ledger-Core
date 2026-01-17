<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        // Modern PHP and Laravel essentials
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'trailing_comma_in_multiline' => true,
        'string_line_ending' => true,
        // PHPDoc essentials
        'phpdoc_to_return_type' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_trim' => true,
        'phpdoc_line_span' => true,
        // Type safety
        'strict_param' => true,
        'strict_comparison' => true,
        'is_null' => true,
        'type_declaration_spaces' => true,
        // Code modernization
        'ternary_to_null_coalescing' => true,
        'ternary_to_elvis_operator' => true,
        'modernize_types_casting' => true,
        'no_alias_functions' => true,
        'native_function_casing' => true,
        // Laravel-specific
        'no_php4_constructor' => true,
        'no_unneeded_curly_braces' => true,
        'no_useless_else' => true,
        'simplified_null_return' => true,
        'simplified_if_return' => true,
        'return_assignment' => true,
        'blank_line_before_statement' => true,
        'visibility_required' => true,
    ])
    ->setFinder($finder);
