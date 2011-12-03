#!/usr/bin/env php
<?php

if (version_compare(phpversion(), '5.3.1', '<')) {
    if (substr(phpversion(), 0, 5) != '5.3.1') {
        // this small hack is because of running RCs of 5.3.1
        echo "Erebot_Module_IrcConnector requires PHP 5.3.1 or newer.
";
        exit(1);
    }
}
foreach (array('phar', 'spl', 'pcre', 'simplexml') as $ext) {
    if (!extension_loaded($ext)) {
        echo 'Extension ', $ext, " is required
";
        exit(1);
    }
}
try {
    Phar::mapPhar();
} catch (Exception $e) {
    echo "Cannot process Erebot_Module_IrcConnector phar:
";
    echo $e->getMessage(), "
";
    exit(1);
}

$phar = new Phar(__FILE__);
$sig = $phar->getSignature();
define('Erebot_Module_IrcConnector_SIG', $sig['hash']);
define('Erebot_Module_IrcConnector_SIGTYPE', $sig['hash_type']);

__HALT_COMPILER();
