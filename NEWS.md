# entries news

Each minor release (or even commit in the repository) is generally considered a separate product with different database schema and upgrading is not supported. This is usually not a problem since most deployments are limited time only but we can create a backwards-compatible patch release if you are affected by a security issue.

## 0.6.0 – unreleased

### New features

- Secrets can now be loaded from `app/config/private.neon` file, making sharing the non-private parts of the config easier.
- CSV exporter now includes e-mail addresses.
- Bank account number in the e-mails can be customized, reducing the need to muck with the templates. See the [`accountNumber`](docs/configuration.md#accountNumber) option.
- E-Mail messages can now be modified by placing overrides in the config directory. See [documentation](docs/customizing-emails.md).
- Implement cache busting strategy for assets.

### Bugs fixed

- Fixed saving team/person data with no custom fields.
- Fix contact marker in the form.
- Fixed several small bugs.
- Fix hiding filter submit button on list page.
- Show invoice link on homepage even after the team has paid.
- Disallow registering to people born after event takes place.
- Fix identity handling after logout.

### Other changes

- PHP 8.0 is now the minimum required version.
- Built packages are now hosted on [Cloudsmith](https://cloudsmith.io/~entries-for-rogaining/repos/entries/packages/?q=version%3Alatest).
- Custom fields will now be hidden by default. You need to add `public: true` to show them in the popovers on the list of teams.
- Upgraded to Bootstrap 5, for fresh looks.
- [Nix](https://nixos.org) files were added for more convenient development.
- Slightly improved wording in the default e-mail templates and made the use more data from the config.
- URLs with ynknown locales are now redirected to the default one.
- Translated the sign in error messages and made them report incorrect password for admin correctly.
- `admin` field for admin password has been renamed to `adminPassword` and moved directly to `parameters` section.
- `config.local.neon` is now included in the source tree, instead of the examples.
- Reduce discrepancied between Czech and English verification e-mails.
- Improve docs about category constraints.
- Use default session storage path instead of `temp/sessions/`.
- Added Catalonia to the list of countries.

## 0.5.0 – 2020-02-11

### New features

- User can display invoice-like itemized list of fees.
- Administrator will see _Clear cache_ button on the homepage, making updating the mail templates and config easier.
- When an SI card number for a card with capacity lower than `parameters.entries.recommendedCardCapacity`, user will be warned.
- Newly added [`CustomInputModifier`](https://github.com/jtojnar/entries/commit/77cfe2b488cf96b95954ec143d09d6cea41cf4f0) class allows arbitrary tweaking of input fields.
- [`allowLateRegistrationsByEmail`](https://github.com/jtojnar/entries/commit/6651583943ba9989e82ef7feac10033f037d4632) field was added to configuration.
- [Aggregation functions](https://github.com/jtojnar/entries/commit/263266b7b7be22bdfc3c4673402b53676c3cd24e) can now be used in age constraints to .
- Add support for [per-category member count limits](https://github.com/jtojnar/entries/commit/e9d743c727c17f2f773335efab5ba3dced468721).

### Other changes

- PHP 7.1 is now the minimum required version.
- Setting database name is now done using `dbal.database` key instead of `dbal.dbname` key in the `app/config/config.local.neon`. Keep this in mind if you see “No database selected” error.
- Upgraded to Bootstrap 4, much more modern style.
- Displayed currency of fees can be configured in `parameters.entries.fees.currency`
- Upgraded to Nette 3.
- Started using [Money for PHP](https://moneyphp.org/) for precision in handling money.
- And lot of internal clean-ups.
