<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return new PhpCsFixer\Config()
    // Symfony Coding Standards (= PER-CS 3.0 + Symfony rules), PHP 8.4 syntax, member order of the standard.
    // strict_types is mandatory although @Symfony:risky removes it (owner, 2026-10-07; finish-init, step 13).
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP8x4Migration' => true,
        'ordered_class_elements' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
