<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        'no_unused_imports' => true,
        'no_whitespace_in_blank_line' => true,
        'single_quote' => true
    ])
    ->setFinder($finder)
    ->setIndent("    ")
    ->setLineEnding("\n")
    ->setUsingCache(false)
;
