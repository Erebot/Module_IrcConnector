<?php

/* Return metadata about what this package:
 * - "requires"
 * - "provides"
 * - "suggests"
 * - "conflicts" with
 * - "replaces"
 * You may optionally provide version constraints.
 * See http://getcomposer.org/doc/01-basic-usage.md#package-versions
 * for examples of valid version constraints.
 *
 * "php" refers to PHP itself while "pear2.php.net/pyrus"
 * refers to Pyrus.
 *
 * You may also pass a string with the name of the license
 * used by this module or an array with a single key/value,
 * the name of the license and an URI for that license.
 */
$metadata['pear.erebot.net/' . $packageName] += array(
    'requires' => array(
        'pear.erebot.net/Erebot',
        'pear2.php.net/pyrus' => '> 2.0.0alpha3',
        'pecl.php.net/sockets',
        'php' => '>= 5.2.0',
        'virt-Erebot_API',
    ),
    'license' => array(
        'GPL' => 'http://www.gnu.org/licenses/gpl-3.0.txt',
    ),
);

