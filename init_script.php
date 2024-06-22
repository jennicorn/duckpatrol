<?php

use Jennchen\Duckpatrol\Initializer\Initializer;

require_once 'src/config.php';
require_once 'src/Initializer/Initializer.php';

global $_CONF;
$ignoreList = $_CONF['ignoreList'];
$root = $_CONF['root'];

new Initializer($ignoreList, $root);