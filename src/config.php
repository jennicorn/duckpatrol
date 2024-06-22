<?php
//INITIALIZER SCRIP CONFIG
// define custom root folder
// if no folder is specified the folder with the composer.json will be
// chosen as root
$_CONF['root'] = ''; //absPath e.g. 'C:\xampp\htdocs\secureProject\testRoot';

// files explicitly not to protect against csrf
$_CONF['ignoreList'] = [
    'folder' => [''], //FROM root e.g. 'folder' => ['testFolder']
    'files' => [''] //FROM rot e.g. 'files' => ['testFolder2/testFile.php', 'testFile2.php']
];

// page to point at for unauthorized requests
$_CONF['landingPage'] = 'invalidForm.php'; //FROM ROOT  e.g. 'invalidForm.php'