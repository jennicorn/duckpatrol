# DuckPatrol: CSRF Protection Library

## Overview
DuckPatrol is a PHP library developed to protect your plain PHP web applications from Cross-Site Request Forgery (CSRF) attacks. It offers a simple way to integrate CSRF protection into your projects by using Composer and providing a script for automation.

## Requirements
- PHP 7.4 or higher
- Composer installation
- Forms to be protected must be sent using the POST method
- To run the script, write permissions must be allowed for the PHP process on the target system

## Installation
Install DuckPatrol with Composer:

```sh
composer require jennchen/duckpatrol
```

## Usage
There are two ways to initialize the DuckPatrol library.

### Manual Initialization
One way is to manually integrate DuckPatrol into all pages you want to protect from CSRF attacks. You can do this by including the `bootstrap.php` file:

```php
require_once 'path/to/vendor/jennchen/duckpatrol/bootstrap.php';
```

### Automatic Initialization
The other way is to use the `init_script.php`. This script searches your entire project for PHP files that contain an HTML form with the POST method. In all these files, the `bootstrap.php` file will be automatically included.

To run the script, you need to navigate to the DuckPatrol folder, which is located in the vendor directory under 'jennchen'. Once you are in the library directory, you can run the script as follows:

```sh
php init_script.php
```

### Script Configuration
If you want to set a custom root directory or exclude certain files from the script's process, you can do this in the `config.php`. All information on this can be found in the configuration file itself.

## Acknowledgements
Thanks to everyone who supported me greatly during the creation of this library. A special thanks goes to:
- Michael Kraftl
- Michael Wagner
- and everyone who helped with the name search <3

## Author
**Jennifer Kraftl** - *Developer* - [GitHub](https://github.com/jennicorn)

## License
This library is open-source software licensed under the MIT License.
