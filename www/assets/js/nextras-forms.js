/**
 * Converts a JSON object produced by calling json_encode
 * on PHP’s DateTime object into a JavaScript’s Date object.
 * For simplicity, it is assumed the DateTime’s timezone
 * matches the local timezone of the user agent.
 */
function fromPhpDateTime(datetime, isDate = false) {
	const parsed = datetime.date.match(/^(?<year>-?[0-9]+)-(?<month>[0-9]{2})-(?<day>[0-9]{2}) (?<hour>[0-9]{2}):(?<min>[0-9]{2}):(?<sec>[0-9]{2})\.(?<msec>[0-9]+)$/);

	let yearString = parsed.groups.year;
	const year = parseInt(yearString, 10);
	if (!(0 <= year && year <= 9999)) {
		// PHP uses four digits for negative years, whereas JavaScript
		// requires six digits and explicit sign:
		// https://tc39.es/ecma262/#sec-expanded-years
		const sign = year < 0 ? '-' : '+';
		yearString = sign + String(Math.abs(year)).padStart(6, '0');
	}

	if (isDate) {
		// When PHP’s DateTime object is converted to JSON, it will contain
		// a date field formatted like 2012-06-23 00:00:00.000000.
		// That will be interpreted as a time in local timezone by JavaScript’s Date.
		// But the value of the input[type="date"] will be something like 2012-06-23,
		// which Date will interpret as UTC time.
		// To make them comparable without having to handle timezones,
		// let’s use just the date part of the datetime string.
		return new Date(`${yearString}-${parsed.groups.month}-${parsed.groups.day}`);
	} else {
		return new Date(`${yearString}-${parsed.groups.month}-${parsed.groups.day} ${parsed.groups.hour}:${parsed.groups.min}:${parsed.groups.sec}.${parsed.groups.msec}`);
	}
}

export function register(Nette) {
	const originalMinValidator = Nette.validators.min;
	Nette.validators.min = function(elem, arg, val) {
		if (elem.type === 'date' || elem.type === 'datetime-local') {
			if (elem.validity.rangeUnderflow) {
				return false;
			} else if (elem.validity.badInput) {
				return null;
			}
			return arg === null || new Date(val) >= fromPhpDateTime(arg, elem.type === 'date');
		}
		return originalMinValidator(elem, arg, val);
	};

	const originalMaxValidator = Nette.validators.max;
	Nette.validators.max = function(elem, arg, val) {
		if (elem.type === 'date' || elem.type === 'datetime-local') {
			if (elem.validity.rangeOverflow) {
				return false;
			} else if (elem.validity.badInput) {
				return null;
			}
			return arg === null || new Date(val) <= fromPhpDateTime(arg, elem.type === 'date');
		}
		return originalMaxValidator(elem, arg, val);
	};

	const originalRangeValidator = Nette.validators.range;
	Nette.validators.range = function(elem, arg, val) {
		if (elem.type === 'date' || elem.type === 'datetime-local') {
			if (elem.validity.rangeUnderflow || elem.validity.rangeOverflow) {
				return false;
			} else if (elem.validity.badInput) {
				return null;
			}
			return Array.isArray(arg) ?
				((arg[0] === null || new Date(val) >= fromPhpDateTime(arg[0], elem.type === 'date')) && (arg[1] === null || new Date(val) <= fromPhpDateTime(arg[1], elem.type === 'date'))) : null;
		}
		return originalRangeValidator(elem, arg, val);
	};
}
