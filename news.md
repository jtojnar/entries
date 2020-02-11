# entries news

Each minor release (or even commit in the repository) is generally considered a separate product with different database schema and upgrading is not supported. This is usually not a problem since most deployments are limited time only but we can create a backwards-compatible patch release if you are affected by a security issue.

## 0.5.0 – 2020-02-11
### New features
* User can display invoice-like itemized list of fees.
* Administrator will see *Clear cache* button on the homepage, making updating the mail templates and config easier.
* When an SI card number for a card with capacity lower than `parameters.entries.recommendedCardCapacity`, user will be warned.
* Newly added [`CustomInputModifier`](https://github.com/jtojnar/entries/commit/77cfe2b488cf96b95954ec143d09d6cea41cf4f0) class allows arbitrary tweaking of input fields.
* [`allowLateRegistrationsByEmail`](https://github.com/jtojnar/entries/commit/6651583943ba9989e82ef7feac10033f037d4632) field was added to configuration.
* [Aggregation functions](https://github.com/jtojnar/entries/commit/263266b7b7be22bdfc3c4673402b53676c3cd24e) can now be used in age constraints to .
*  Add support for [per-category member count limits](https://github.com/jtojnar/entries/commit/e9d743c727c17f2f773335efab5ba3dced468721).

### Other changes
* PHP 7.1 is now the minimum required version.
* Setting database name is now done using `dbal.database` key instead of `dbal.dbname` key in the `app/config/config.local.neon`. Keep this in mind if you see “No database selected” error.
* Upgraded to Bootstrap 4, much more modern style.
* Displayed currency of fees can be configured in `parameters.entries.fees.currency`
* Upgraded to Nette 3.
* Started using [Money for PHP](https://moneyphp.org/) for precision in handling money.
* And lot of internal clean-ups.
