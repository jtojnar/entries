# Configuring Entries

The system currently uses [NEON language](https://ne-on.org/) for creating the entry form as well as setting other parameters. The configuration is loaded from `config.local.neon` and `private.neon` files in the `app/config` directory. It is also possible to [customize e-mail messages](customizing-emails.md), hook into input fields and modify invoices.

> If you put database credentials (`dbal` section) and `parameters.adminPassword` into `private.neon` file, it will be safe to commit `config.local.neon` to version control and it will also make it easy to share the local config accross multiple servers (e.g. computer and developer’s production).

## Structure of the config

The configuration file provides considerable customization possibilities – not only it enables changing the application, it also allows overriding the behaviour of the underlying libraries. For example, it is possible to [use SMTP](https://doc.nette.org/en/3.1/configuring#toc-mail) for sending e-mails.

The configuration file is divided into sections; on top of the optional Nette specific ones, there are the following sections:

### `dbal` section

This section contains credentials for connecting to a database server. It is required.

```neon
dbal:
	driver: mysqli
	host: localhost
	database: entries
	username: u_entries
	password: p4ssw0rd
	connectionTz: +01:00
	options:
		charset: utf8mb4
```

For details about the supported keys see [Nextras\Dbal docs](https://nextras.org/dbal/docs/4.0/#toc-connection).

### `parameters` section

This section affects behaviour of the app itself.

#### `adminPassword`

```neon
# used for editing entries, currently only plain-text passwords are supported
adminPassword: p4ssw0rd
```

#### `siteTitle`

This is a dictionary of names of the event in the supported languages. It will be displayed in the page title and headings.

At least an item for the default language needs to be provided, it will be used as a fallback.

```neon
siteTitle:
	cs: HROB 2017
	en: Mountain Orienteering Championships 2017
```

#### `siteTitleShort`

This is a dictionary of short names of the event in the supported languages. It may be used in the e-mail messages.

When missing, the full `siteTitle` will be used instead.

```neon
siteTitle:
	cs: MČR 2021
	en: CRC 2021
```

#### `accountNumber`

This is the bank account number to be listed in payment instructions in the Czech e-mail the team receives after registration. (Unless the message text is [overridden](customizing-emails.md).)

```neon
accountNumber: '000000-0000000000/0000'
```

#### `webmasterEmail`

A string containing the e-mail address of the person responsible for handling entries. It will be shown on the front page and will be used as a sender of the confirmation e-mails.

If you use a better e-mail provider like GMail, you should also configure the SMTP sender in order to have the messages correctly signed. This will reduce the likelihood of the messages ending up in the entrants’ spam directory. See [Structure of the config](#structure-of-the-config) section.

#### `entries`

The most important subsection, it describes the information about the event (starting date), categories, fees, constraints on the registration process, as well as the fields of the entry form. It also contains the administrator’s password.

```neon
# used for age calculations
eventDate: 2017-11-03

# the registration will be closed outside of this interval
opening: 2017-06-01T01:00:00
closing: 2017-11-01T01:00:00

# whether to urge people to use e-mail after registration period (defaults to false)
allowLateRegistrationsByEmail: true

# constraints on number of team members (min & max can be overridden in each category individually)
minMembers: 1
initialMembers: 2 # number of members when form is loaded, optional (defaults to the value of minMembers)
maxMembers: 3

# this will warn users when they try to enter with an SI card
# with lower capacity
recommendedCardCapacity: 50
```

##### `fees`

This section defines the fees and other properties related to prices.

###### `person`

This key is used as a fallback when a category does not define fees locally. It is especially useful if most of the categories share the same price, prices for the rest can be overridden individually.

###### `currency`

Prices will be displayed in choosen currency on the invoice and in the administration. Currently only the following are supported:

- CZK – Czech koruna
- PLN – Polish złoty
- HUF – Hungarian forint
- EUR – Euro
- USD – United States dollar
- GBP – Pound sterling
- JPY – Japanese yen

You can also set [currency conversion rates](#services-section).

##### `categories`

This section defines the categories entrants can choose from. The `categories` section can be either nested or flat. Each category defines a set of constraints that need to be satisfied in order for the team to be able to register in the category.

Categories can also narrow down the minimum and maximum number of team members:

```neon
parameters:
	entries:
		minMembers: 1
		maxMembers: 5
		categories:
			'MM':
				minMembers: 2
				maxMembers: 2
```

###### Category constraints

The constrains form a list representing a conjunction of individual constraint expressions. The syntax of a constraint expression is described by the following [EBNF grammar](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form):

```ebnf
expression ::= quantified_expression | aggregate_expression
quantified_expression ::= "some(" predicate ")" | "all(" predicate ")"
predicate ::= numeric_variable comparison_operator number | "gender" "=" sex
sex ::= "male" | "female"
numeric_variable ::= "age"
comparison_operator ::= "<" | "<=" | "=" | ">=" | ">"
aggregate_expression ::= aggregate_function "(" numeric_variable ")" comparison_operator number
aggregate_function ::= "sum" | "min" | "max"
```

Or, to put it simply, each constraint expression can be:

- one of the following predicates quantified by either `some` or `all`:
	- `age`, followed by one of the comparison operators `<`, `<=`, `=`, `>=`, `>` and then a number
	- `gender` followed by `=` and then either `male` or `female`
- one of the aggregate functions `sum`, `min`, `max`, applied to `age` and followed by a comparison operator and then a number

`some` quantifier requires at least one member of the team satisfies given predicate, `all` requires that all members of the team do. `age` refers to the number of years since a team member’s birth at the first day of the event **rounded down**. Notably, that means that `age>65` will only match people who are at least 66.

```neon
constraints:
	- 'some(gender=male)'
	- 'some(gender=female)'
	- 'all(age<20)'
	- 'sum(age)>=40'
```

If the constraints are an empty set, the `constraints` key can be omitted.

###### Flat categories

The categories are all equal, the simplest organization.

```neon
HH20:
	constraints:
		- 'all(gender=male)'
		- 'all(age<20)'
DD20:
	constraints:
		- 'all(gender=female)'
		- 'all(age<20)'
P: # an unconstrained category
```

###### Nested categories

Larger events might organize multiple races of different durations. Nested categories add a level thus making the category selection cleaner. Since [option group](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/optgroup) from HTML is used, the groups cannot be nested.

Each category group requires a label for each supported locale. Alternately, you can specify a label string shared by all locales.

You can also override the [fees](#fees), either on a category level or a group level.

```neon
categories:
	24:
		label:
			cs: 24 hodin
			en: 24 hours
		categories:
			MO:
				constraints:
					- 'all(gender=male)'
			WO:
				fees:
					person: 200
			XO:
	12:
		label: '12'
		fees:
			person: 200
		categories:
			MO12:
			WO12:
			XO12:
```

Note that the category keys should be unique even across the groups.

##### `fields`

This section describes the data asked in the entry form. The team fields are declared separately from the person fields but the syntax is the same.

By default, the form only asks the team for a name, category and optionally a message for the organizer. A person is asked for a name, sex, e-mail address, and a birth date. More fields of various types can be added as necessary.

```neon
fields:
	team:
		phone:
			type: phone
			label:
				cs: 'Telefon:'
				en: 'Phone:'
	person:
		country:
			type: country
			default: 46
			public: true
		sportident:
			type: sportident
			fee: 50.0
			public: true
```

###### Common properties

- `type` – Each field will need to declare its type, see [field types](#field-types).
- `label` – Some fields may define a default label (`sportident`, `country`) but otherwise you should define one for each language.
- `public` – By default, no custom field will be shown in the team list publicly to prevent leaking personal information and as not to clutter the list with items only relevant to organizers. You can set this option to `true` to make it visible to everyone.
- `applicableCategories` – A list of categories to show this field in. If not present, every category is implied.

###### Field types

- `country` – Select box listing the countries of the world. You can specify a `default` value – 46 stands for Czechia, see [install.sql](../install.sql) for a complete list. You might want to make the value public to promote nationalism among the participants.
- `phone` – A telephone number.
- `sportident` – A field allowing to enter a SI card number or request one for rent. The price is set using the `fee` key. You might want to make the value public to allow people to check if they registered the correct SI card.
- `enum` – Allows selecting a single value from a list of values. Each option can have a `fee` set.

```neon
accommodation:
	type: enum
	label:
		en: 'Accommodation:'
		cs: 'Ubytování:'
	options:
		sunday:
			label:
				en: 'Friday – Sunday'
				cs: 'Pátek – Neděle'
			fee: 500
		saturday:
			label:
				en: 'Saturday – Sunday'
				cs: 'Sobota – Neděle'
			fee: 250
		none:
			default: true
			label:
				en: 'None'
				cs: 'Žádné'
```

- `checkbox` – A simple boolean switch, can have a `fee` or a `default` state associated.
- `checkboxlist` – Allows selecting multiple values from a list of values. Each option can have a `fee` or a `default` state set.

```neon
boarding:
	label:
		en: 'Boarding:'
		cs: 'Stravování:'
	type: checkboxlist
	items:
		sat_breakfast:
			label:
				en: 'Saturday Breakfast (120 CZK)'
				cs: 'Sobotní snídaně (120 Kč)'
			fee: 120
		sat_supper:
			label:
				en: 'Saturday Supper (100 CZK)'
				cs: 'Sobotní večeře (100 Kč)'
			fee: 100
		sun_breakfast:
			label:
				en: 'Sunday Breakfast (120 CZK)'
				cs: 'Nedělní snídaně (120 Kč)'
			fee: 120
```

### `services` section

This section [configures services](https://doc.nette.org/en/dependency-injection/configuration#toc-services) for Nette’s dependency injection and it is extremely powerful. You can use it to change many internal aspects of the application. Here we are listing just the few interesting ones.

#### Setting currency exchange rates

The app currently only supports defining prices in a single currency but you can set exchange rates by overriding the `exchange` service:

```neon
exchange: Money\Exchange\ReversedCurrenciesExchange(
	Money\Exchange\FixedExchange([
		'EUR': [
			'CZK': 25
		]
	])
)
```

Then you will be able to use `exchangeCurrency:'CZK'` [Latte filter](https://latte.nette.org/en/filters) in templates such as e-mails.

See [moneyphp documentation](https://www.moneyphp.org/en/stable/features/currency-conversion.html) for more information.
