entries
=======

[![Build Status](https://travis-ci.org/jtojnar/entries.svg?branch=master)](https://travis-ci.org/jtojnar/entries)

Entry registration system for [Rogaining](http://en.wikipedia.org/wiki/Rogaining).

Requirements
------------

* PHP 7.0 or newer
* MySQL or other similar database
* composer dependencies

Installation
------------
1. Clone this repository and issue `composer install`, or download package from [BinTray](https://bintray.com/jtojnar/entries/entries) ([latest](https://bintray.com/jtojnar/entries/entries/_latestVersion#files)).
2. Run SQL from `install.sql`.
3. Configure app in `app/config/config.local.neon.default` and rename the file to `config.local.neon`.
4. Make `temp` and `log` directories writeable.
5. Change e-mail templates in `app/templates/Mail`.
6. The entry point of the application is in the `www` directory, configure your web server accordingly.
