# DuckPatrol: CSRF Schutzbibliothek

## Überblick
DuckPatrol ist eine PHP-Bibliothek, die entwickelt wurde, um deine plain PHP Webanwendungen vor
Cross-Site Request Forgery (CSRF) Angriffen zu schützen. Sie bietet eine einfache Möglichkeit,
CSRF-Schutz in deine Projekte zu integrieren, indem sie Composer verwendet und ein Skript zur 
Automatisierung bereitstellt. 


## Voraussetzungen 
 - PHP 7.4 oder höher
 - Composer
 - Zu schützende Formulare müssen mit der POST Methode versendet werden 
 - Um das Skript ausführen zu können müssen am Zielsystem Schreibberechtigungen für den
php Prozess erlaubt sein
 - output_buffering muss in deiner `php.ini` aktiviert sein


## Installation
Installiere DuckPatrol mit Composer:

```sh
composer require jennchen/duckpatrol
```

## Nutzung
Um die DuckPatrol-Bibliothek zu initialisieren, gibt es zwei Möglichkeiten.


### Manuelle Initialisierung
Zum einen kannst du DuckPatrol manuell in alle Seiten einbauen, die du vor CSRF-Angriffen 
schützen willst. Dies erreichst du, indem du dir `bootstrap.php`-Datei einbindest: 

```php
require_once 'path/to/vendor/jennchen/duckpatrol/bootstrap.php';
```

### Automatische Initialisierung
Die andere Möglichkeit ist es, das `init_script.php` zu verwenden. Dieses durchsucht dein 
gesamtes Projekt nach PHP-Files, die ein HTML-Formular mit der Methode POST beinhalten ab. In all 
diesen wird nun voll automatisch das `bootstrap.php`-File eingebunden.

Um das Skript auszuführen, musst du in den duckpatrol Ordner navigieren, dieser befindet sich im
vendor-Verzeichnis unter 'jennchen'. Bist du im Verzeichnis der Library kannst du das Skript wie 
folgt ausführen: 

```sh
php init_script.php
```

### Konfiguration des Skriptes
Willst du ein Custom-Root Verzeichnis festlegen, oder gewisse Dateien aus dem Ablauf des Skriptes 
entfernen, kannst du das in der `config.php` tun. Alle Infos dazu findest du in der 
Konfigurationsdatei selbst 

## Danksagung
Dank an alle, die mich bei der Entstehung dieser Library so großartig unterstützt haben. Ein 
besonderer Dank geht an: 
 - Michael Kraftl
 - Michael Wagner
 - und alle, die bei der Namenssuche geholfen haben <3


## Autor
**Jennifer Kraftl** - *Developerin* - [GitHub](https://github.com/jennicorn)


## Lizenz
Diese Bibliothek ist Open-Source-Software, die unter der MIT-Lizenz lizenziert ist.

