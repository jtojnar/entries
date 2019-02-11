# entries news

Each minor release (or even commit in the repository) is generally considered a separate product with different database schema and upgrading is not supported. This is usually not a problem since most deployments are limited time only but we can create a backwards-compatible patch release if you are affected by a security issue.

## 0.5.0 – unreleased
### New features
* User can display invoice-like itemized list of fees.
* Administrator will see *Clear cache* button on the homepage, making updating the mail templates and config easier.
* When an SI card number for a card with capacity lower than `parameters.entries.recommendedCardCapacity`, user will be warned.

### Other changes
* PHP 7.1 is now the minimum required version.
* Setting database name is now done using `dbal.database` key instead of `dbal.dbname` key in the `app/config/config.local.neon`. Keep this in mind if you see “No database selected” error.
* Upgraded to Bootstrap 4, much more modern style.
* Displayed currency of fees can be configured in `parameters.entries.fees.currency`
