# entries

Entry registration system for [Rogaining](http://en.wikipedia.org/wiki/Rogaining).

## Requirements

- PHP 8.0 or newer
- MySQL or other similar database
- composer dependencies (included in the bundle)
- npm dependencies (included in the bundle)

## Development

We do not include dependencies in the git repository, therefore you will need to install the dependencies by running `composer install` and `yarn install` to be able to run the application.

Then you will need to build the assets with `yarn run build` or `yarn run dev`. The latter is especially useful when modifying CSS or JavaScript files, as it will monitor them and rebuild them when changed.

## Installation

1. Clone this repository and follow the steps in [Development section](#development), or download package from [Cloudsmith](https://cloudsmith.io/~entries-for-rogaining/repos/entries/packages/) ([latest](https://cloudsmith.io/~entries-for-rogaining/repos/entries/packages/?q=version%3Alatest)).
2. Run SQL from `install.sql`.
3. Configure the event information in `app/config/config.local.neon` as described in the [configuration documentation](docs/configuration.md). Do not forget to set up admin password and database credentials in either `app/config/config.local.neon` or `app/config/private.neon`.
4. Make `temp` and `log` directories writeable.
5. Change e-mail templates in `app/templates/Mail`.
6. The entry point of the application is in the `www` directory, configure your web server accordingly.

## Thanks

Package repository hosting is graciously provided by [Cloudsmith](https://cloudsmith.com). Cloudsmith is the only fully hosted, cloud-native, universal package management solution, that enables your organization to create, store and share packages in any format, to any place, with total confidence.
