entries
=======

[![Build Status](https://travis-ci.org/jtojnar/entries.svg?branch=master)](https://travis-ci.org/jtojnar/entries)

Entry registration system for [Rogaining](http://en.wikipedia.org/wiki/Rogaining).

Requirements
------------

* PHP 5.4
* MySQL or other similar database
* composer dependencies

Installation
------------
Run following commands

	git clone https://github.com/jtojnar/entries.git
	composer install

or download package.

Run SQL from *install.sql*. You can change db table prefixes later in your *config.local.neon* (`parameters.database.prefix`).

Configure app in *app/config/config.local.neon.default* and rename it to *config.local.neon*.

Change e-mail templates in *app/templates/Mail*.
