<?php

use duckpatrol\src\Initializer\Initializer;

require_once 'src/config.php';
require_once 'src/Initializer/Initializer.php';

global $_CONF;
$ignoreList = $_CONF['ignoreList'] ?? [
    'folder' => [],
    'files' => []
];
$root = $_CONF['root'] ?? ''; //root of application, if not specified folder with composer.json will be used

new Initializer($ignoreList, $root);