<?php
/**
 * Core Configurations.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.1.11
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
$versionFile = file(dirname(__DIR__) . '/VERSION.txt');

// Handle case where VERSION.txt doesn't exist (shouldn't happen, but prevent fatal errors)
if ($versionFile === false || !is_array($versionFile) || empty($versionFile)) {
    // Fallback: try to get version from composer.json
    $composerPath = dirname(__DIR__) . '/composer.json';
    $version = '5.2.0'; // Default fallback
    if (file_exists($composerPath)) {
        $composer = json_decode(file_get_contents($composerPath), true);
        if (isset($composer['version'])) {
            $version = $composer['version'];
        }
    }
    return [
        'Cake.version' => $version,
    ];
}

return [
    'Cake.version' => trim(array_pop($versionFile)),
];
