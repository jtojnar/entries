entries
=======

[![Build Status](https://travis-ci.org/jtojnar/entries.svg?branch=master)](https://travis-ci.org/jtojnar/entries)

Entry registration system for [Rogaining](http://en.wikipedia.org/wiki/Rogaining).

Requirements
------------

* PHP 7.1 or newer
* MySQL or other similar database
* composer dependencies (included in the bundle)
* npm dependencies (included in the bundle)

Development
-----------

We do not include dependencies in the git repository, therefore you will need to install the dependencies by running `composer install` and `yarn install` to be able to run the application.

Then you will need to build the assets with `yarn run build` or `yarn run dev`. The latter is especially useful when modifying CSS or JavaScript files, as it will monitor them and rebuild them when changed.

Installation
------------
1. Clone this repository and follow the steps in [Development section](#development), or download package from [BinTray](https://bintray.com/jtojnar/entries/entries) ([latest](https://bintray.com/jtojnar/entries/entries/_latestVersion#files)).
2. Run SQL from `install.sql`.
3. Configure app in `app/config/config.local.neon.default` and rename the file to `config.local.neon`. See [documentation](docs/configuration.md) for more information.
4. Make `temp` and `log` directories writeable.
5. Change e-mail templates in `app/templates/Mail`.
6. The entry point of the application is in the `www` directory, configure your web server accordingly.
