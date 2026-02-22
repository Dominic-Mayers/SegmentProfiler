<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.23',
    ],
    'react' => [
        'version' => '19.2.4',
    ],
    'mobile-detect' => [
        'version' => '1.4.5',
    ],
    'object-assign' => [
        'version' => '4.1.1',
    ],
    'fbjs/lib/emptyObject' => [
        'version' => '3.0.5',
    ],
    'fbjs/lib/emptyFunction' => [
        'version' => '3.0.5',
    ],
    'svg-pan-zoom' => [
        'version' => '3.6.2',
    ],
    'tiny-emitter' => [
        'version' => '2.1.0',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'tiny-popup-menu' => [
        'version' => '1.0.15',
    ],
    'ctxmenu' => [
        'version' => '2.1.0',
    ],
    'graph-app-test' => [
        'path' => './assets/graph/graph-app-test.js',
        'entrypoint' => true,
    ],
    'graph-app' => [
        'path' => './assets/graph/graph-app.js',
        'entrypoint' => true,
    ],
    '@hpcc-js/wasm' => [
        'path' => './node_modules/@hpcc-js/wasm/dist/index.js',
    ],
    'test/main.js' => [
        'path' => './assets/test/main.js',
        'entrypoint' => true,
    ],
];
