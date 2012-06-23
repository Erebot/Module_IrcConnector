<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

if (version_compare(phpversion(), '5.3.1', '<')) {
    if (substr(phpversion(), 0, 5) != '5.3.1') {
        // this small hack is because of running RCs of 5.3.1
        echo "@PACKAGE_NAME@ requires PHP 5.3.1 or newer." . PHP_EOL;
        exit(1);
    }
}
foreach (array('phar', 'spl', 'pcre', 'simplexml') as $ext) {
    if (!extension_loaded($ext)) {
        echo "Extension $ext is required." . PHP_EOL;
        exit(1);
    }
}
try {
    Phar::mapPhar();
} catch (Exception $e) {
    echo "Cannot process @PACKAGE_NAME@ phar:" . PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

// Return metadata about this package.
return array(
    'pear.erebot.net/@PACKAGE_NAME@' => array(
        'version' => '@PACKAGE_VERSION@',
        'path' =>
            "phar://" . __FILE__ .
            DIRECTORY_SEPARATOR . "@PACKAGE_NAME@-@PACKAGE_VERSION@" .
            DIRECTORY_SEPARATOR . "php",
        'requires' => array(
            'php >= 5.2.2',
            'virt-Erebot_API = 0.2.*',
            'pear.erebot.net/Erebot',
        ),
    ),
);

__HALT_COMPILER();
