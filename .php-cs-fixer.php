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
        
        // Array & List syntax
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'normalize_index_brace' => true,
        'whitespace_after_comma_in_array' => true,
        
        // Import statements
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'no_leading_import_slash' => true,
        
        // Strings & Quotes
        'single_quote' => true,
        'string_line_ending' => true,
        'concat_space' => ['spacing' => 'one'],
        'explicit_string_variable' => true,
        
        // PHPDoc
        'phpdoc_to_return_type' => true,
        'phpdoc_to_param_type' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'phpdoc_trim' => true,
        'phpdoc_line_span' => ['const' => 'single', 'method' => 'multi', 'property' => 'single'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        
        // Type safety & declarations
        'strict_param' => true,
        'strict_comparison' => true,
        'declare_strict_types' => true,
        'type_declaration_spaces' => true,
        'types_spaces' => ['space' => 'none'],
        'nullable_type_declaration_for_default_null_value' => true,
        
        // Code modernization (PHP 8.2+)
        'ternary_to_null_coalescing' => true,
        'ternary_to_elvis_operator' => true,
        'modernize_types_casting' => true,
        'no_alias_functions' => true,
        'native_function_casing' => true,
        'use_arrow_functions' => true,
        'no_unneeded_control_parentheses' => true,
        
        // Laravel & Code structure
        'no_php4_constructor' => true,
        'no_unneeded_braces' => ['namespaces' => true],
        'no_useless_else' => true,
        'simplified_null_return' => true,
        'simplified_if_return' => true,
        'return_assignment' => true,
        'not_operator_with_successor_space' => true,
        'modifier_keywords' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
        
        // Blank lines & spacing
        'blank_line_before_statement' => ['statements' => ['return', 'try', 'throw', 'if', 'switch', 'for', 'foreach', 'while', 'do']],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra', 'throw', 'use']],
        'blank_lines_before_namespace' => true,
        
        // PHPUnit & Testing - camelCase for test methods
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => false,
        'php_unit_strict' => false,
        
        // Cleanup
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_blank_line_at_eof' => true,
        'no_whitespace_in_blank_line' => true,
        'method_chaining_indentation' => true,
        
        // Casts & operators
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,
        'short_scalar_cast' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'unary_operator_spaces' => true,
        'standardize_not_equals' => true,
        'object_operator_without_whitespace' => true,
    ])
    ->setFinder($finder);
