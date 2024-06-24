<?php

// Define the custom root folder. If no folder is specified, the outermost folder with the composer.json will be chosen as root.
$_CONF['root'] = ''; // Absolute path, e.g., 'C:\xampp\htdocs\secureProject\testRoot';

// Files and folders explicitly not to protect against CSRF
$_CONF['ignoreList'] = [
    'folder' => [], // From root, e.g., 'folder' => ['testFolder']
    'files' => [] // From root, e.g., 'files' => ['testFolder2/testFile.php', 'testFile2.php']
];

// Page to redirect to for unauthorized requests
$_CONF['landingPage'] = ''; // From root, e.g., 'invalidForm.php'
