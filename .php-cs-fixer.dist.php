<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src/tranzakt')
    ->exclude('vendor')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    '@Symfony' => true,
        'full_opening_tag' => false,
    ])
    ->setFinder($finder)
;
