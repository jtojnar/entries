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
Run following commands

	git clone https://github.com/jtojnar/entries.git
	cd entries
	composer install

or download package from [BinTray](https://bintray.com/jtojnar/entries/entries) ([latest](https://bintray.com/jtojnar/entries/entries/_latestVersion#files)).

Run SQL from *install.sql*.

Configure app in *app/config/config.local.neon.default* and rename it to *config.local.neon*.

Make *temp* and *log* directories writeable.

Change e-mail templates in *app/templates/Mail*.
