import Enum from 'es6-enum';
import { distance } from 'fastest-levenshtein';
import { faSpinner as fasSpinner } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { prop, sortBy, toLower } from 'ramda';
import { useCallback, useEffect, useRef, useState, Fragment } from 'react';
import { render } from 'react-dom';

const LoadingState = Enum(
	'INITIAL',
	'LOADING',
	'SUCCESS',
	'ERROR',
);

const WidgetState = Enum(
	'INITIAL',
	'MATCHES',
	'ALL',
	'SELECTED',
	'CUSTOM',
	'NOT_QUALIFIED',
);

const genderclass = {
	'men': 'M',
	'women': 'W',
	'mixed': 'X'
};

const ageclass = {
	'under18': '18',
	'under20': '20',
	'under23': '23',
	'junior': 'J',
	'youth': 'Y',
	'open': 'O',
	'veteran': 'V',
	'superveteran': 'SV',
	'ultraveteran': 'UV'
};

const csLang = {
	noMatches: 'Jméno nenalezeno. Prosím zkontrolujte případné překlepy nebo manuálně prohledejte databázi předkvalifikovaných závodníků.',
	searchCriteria: 'Prohledat databázi předkvalifikovaných závodníků',
	searchCriteriaAgain: 'Znovu prohledat databázi předkvalifikovaných závodníků',
	browseDatabase: 'Přehled po zemích',
	selectNotQualified: 'Nekvalifikoval jsem se',
	errorLoadingData: 'Chyba při načítání dat, opakujte akci.',
	selectCriterion: 'To jsem já',
	notQualified: 'Nekvalifikoval jsem se podle žádného z kritérií.',
	placeholder: (
		<Fragment>
			<p>Mezinárodní Rogainingová Federace vytvořila <a href="https://wrc2022.rogaining.cz/files/Entry_Criteria_WRC_2022.pdf">výběrová kritéria</a> pro MS 2022. Pokud tento člen týmu splňuje tato kritéria pro prioritní přijetí přihlášky, může tuto skutečnost deklarovat zde.</p>
			<p>Vítězové minulých světových šampionátů (předkvalifikace podle kritéria 1.2), prosím uveďte do pole „zpráva pro pořadatele“ min. jeden 24-hodinový rogaining, kterého jste se zúčastnili během minulých dvou let.</p>
		</Fragment>
	),
	customHint: 'Prosím specifikujte závod, kategorii a umístění.',
	selectCriterionStarred: 'To jsem já, absolvoval jsem min. jeden 24h rogaining během minulých 2 let',
};

const enLang = {
	noMatches: 'No matches found. Please double check your name or select your qualification manually.',
	searchCriteria: 'Search database of pre-qualified rogainers',
	searchCriteriaAgain: 'Search database of pre-qualified rogainers again',
	browseDatabase: 'Look up by country',
	selectNotQualified: 'I haven’t  qualified',
	errorLoadingData: 'Error loading qualification data, please try again.',
	selectCriterion: 'This is me',
	notQualified: 'I do not qualify under any of the criteria.',
	placeholder: (
		<Fragment>
			<p>The International Rogaining Federation has estabilished <a href="https://wrc2022.rogaining.cz/files/Entry_Criteria_WRC_2022.pdf">selection criteria</a> for the WRC 2022. If this member meets a criterion for priority entry, please declare the eligibility here.</p>
			<p>Past WRC champions pre-qualified via criteria 1.2, please specify at least one 24-hour rogaine you participated in the past two years into the “message for organizer” field.</p>
		</Fragment>
	),
	customHint: 'Please specify the event, category and placing.',
	selectCriterionStarred: 'This is me and I have competed in at least one 24-hour rogaine in the last two years',
};

const lang = document.documentElement.lang === 'cs' ? csLang : enLang;

// Do not fetch it multiple times.
let criteriaData = null;

function fetchCriteria() {
	if (criteriaData !== null) {
		return Promise.resolve(criteriaData);
	} else {
		return (
			fetch('../../pqe.json')
			.then((data) => data.json())
			.then((data) => {
				for (let [country, persons] of Object.entries(data.qualified.auto)) {
					// Turn list of persons into object so that we can merge it with persons from the preferred list efficiently.
					data.qualified.auto[country] = Object.fromEntries(persons.map((person) => [person.personId, person]));
				}

				// Merge each person list per country based on personId.
				for (let [country, persons] of Object.entries(data.qualified.preferred)) {
					if (!data.qualified.auto[country]) {
						data.qualified.auto[country] = {};
					}
					persons.forEach((person) => {
						if (!data.qualified.auto[country][person.personId]) {
							data.qualified.auto[country][person.personId] = person;
						} else {
							// Append criteria.
							data.qualified.auto[country][person.personId].reasons.push(...person.reasons);
						}
					});
				}

				for (let [country, persons] of Object.entries(data.qualified.auto)) {
					// Turn map of persons back into a list and add country for convenience.
					data.qualified.auto[country] = sortBy(({ firstname, lastname}) => [lastname, firstname], Object.values(persons).map((person) => ({ ...person, country })));
				}

				criteriaData = sortBy(prop(0), Object.entries(data.qualified.auto));

				return criteriaData;
			})
		);
	}
}

/**
 * The reason can be either a JSON value, or a freeform string.
 */
function parseSelectedReason(value) {
	try {
		return JSON.parse(value);
	} catch {
		return value.trim();
	}
}

/**
 * Renders a row for a single matching person.
 */
function Match({
	personId,
	firstname,
	lastname,
	country,
	reasons,
	onSelected = null,
}) {
	const criterionClicked = useCallback(
		() => {
			onSelected({
				personId,
				firstname,
				lastname,
				country,
				reasons,
			});
		},
		[
			personId,
			firstname,
			lastname,
			country,
			reasons,
		]
	);

	return (
		<li className="list-group-item d-flex justify-content-between align-items-start">
			<div className="ms-2 me-auto">
				<div className="fw-bold">{lastname} {firstname} ({country})</div>
				{reasons.map(({ criterion, gender, age, event, position}, idx) => {
					return (
						<Fragment key={idx}>
							{idx !== 0 && ', '}
							{criterion} – {criterion === '1.4' ? 'IRF councillor' : 
								<a href={`https://pqe.rogaining.org/events/${event}/results`} target="_blank">
									{genderclass[gender]}{ageclass[age]}{position}@{event}
								</a>
							}
						</Fragment>
					);
				})}
			</div>
			{onSelected !== null &&
				<button
					type="button"
					className="btn btn-secondary"
					onClick={criterionClicked}
				>
					{reasons.every(({ starWarning }) => starWarning) ? lang.selectCriterionStarred : lang.selectCriterion}
				</button>
			}
		</li>
	);
}

/**
 * Renders a list of rows, one per each matching person.
 */
function Matches({
	matches,
	onSelected,
}) {

	return (
		<ul className="list-group">
			{matches.map(
				({ personId, firstname, lastname, country, reasons}) => (
					<Match key={personId} {...{ personId, firstname, lastname, country, reasons, onSelected }} />
				)
			)}
		</ul>
	);
}

/**
 * Renders the custom selected reason for pre-qualification.
 */
function CustomSelectedReason({
	selectedReason,
	setSelectedReason,
}) {
	const customCriteriaRef = useRef();
	const customCriteriaBlur = useCallback(
		() => {
			if (customCriteriaRef.current) {
				setSelectedReason(customCriteriaRef.current.value);
			}
		},
		[]
	);

	return (
		<input type="text" defaultValue={selectedReason} ref={customCriteriaRef} onBlur={customCriteriaBlur} />
	);
}

function getInitialState(reason) {
	if (reason === '') {
		return WidgetState.INITIAL;
	}
	if (reason === null) {
		return WidgetState.NOT_QUALIFIED;
	}
	if (typeof reason === 'string') {
		return WidgetState.CUSTOM;
	}
	if (typeof reason === 'object') {
		return WidgetState.SELECTED;
	}
	// Should be unreachable.
	console.assert(false);
}

/**
 * A widget that replaces the “Qualified via criteria” field by an interface
 * that allows selecting participation from the PQE database.
 */
function QualificationChooser({
	firstNameField,
	lastNameField,
	onChange,
	value,
}) {
	/**
	 * @var {String|Object|null} selectedReason
	 *
	 * The types are used as follows:
	 *  - `null` when one is not eligible
	 *  - `object` when prequalification reason has been chosen from the database
	 *  - `string` when a free-form explanation is used
	 **/
	const [selectedReason, setSelectedReason] = useState(parseSelectedReason(value));
	const [widgetState, setWidgetState] = useState(getInitialState(selectedReason));
	const [personMatches, setPersonMatches] = useState(null);
	const [pqeDataState, setPqeDataState] = useState(LoadingState.INITIAL);
	const [pqeData, setPqeData] = useState(null);

	useEffect(
		() => {
			// Update the hidden field on change.
			onChange(typeof selectedReason === 'string' ? selectedReason : JSON.stringify(selectedReason));
		},
		[selectedReason]
	);

	const searchCriteriaClicked = useCallback(
		async () => {
			setPqeDataState(LoadingState.LOADING);
			// Clear the so that the form cannot be submitted.
			onChange('');
			setWidgetState(WidgetState.MATCHES);

			try {
				const data = await fetchCriteria();

				const lastname = lastNameField.value.trim();
				const firstname = firstNameField.value.trim();

				let matches = [];

				for (let [country, persons] of data) {
					for (let person of persons) {
						if (distance(toLower(`${person.lastname} ${person.firstname}`), toLower(`${lastname} ${firstname}`)) <= 5 || distance(toLower(`${person.firstname} ${person.lastname}`), toLower(`${lastname} ${firstname}`)) <= 5) {
							matches.push(person);
						}
					}
				}

				setPersonMatches(matches);

				setPqeDataState(LoadingState.SUCCESS);
			} catch (error) {
				console.error(error);
				setPqeDataState(LoadingState.ERROR);
			}
		},
		[lastNameField, firstNameField]
	);
	const selectNotQualifiedClicked = useCallback(
		() => {
			setSelectedReason(null);
			setWidgetState(WidgetState.NOT_QUALIFIED);
		},
		[]
	);
	const selectPersonClicked = useCallback(
		(selectedReason) => {
			setSelectedReason(selectedReason);
			setWidgetState(WidgetState.SELECTED);
		},
		[]
	);
	const browseDatabaseClicked = useCallback(
		async () => {
			setPqeDataState(LoadingState.LOADING);
			setWidgetState(WidgetState.ALL);

			try {
				const data = await fetchCriteria();

				setPqeData(data);

				setPqeDataState(LoadingState.SUCCESS);
			} catch (error) {
				console.error(error);
				setPqeDataState(LoadingState.ERROR);
			}
		},
		[]
	);

	const boxRef = useRef();

	useEffect(
		() => {
			if (boxRef.current) {
				requestAnimationFrame(() => {
					boxRef.current.scrollIntoView();
				});
			}
		},
		[
			// Scroll the box into view when results change.
			pqeDataState,
		]
	);

	let body = null;
	let buttons = [];

	if (widgetState === WidgetState.ALL) {
		if (pqeDataState === LoadingState.SUCCESS) {
			console.assert(pqeData !== null);
			body = (
				<div ref={boxRef}>
					{pqeData.map(
						([ country, persons ]) => {
							return (
								<div key={country}>
									<h2>{country}</h2>
									<Matches
										matches={persons}
										onSelected={selectPersonClicked}
									/>
								</div>
							);
						}
					)}
				</div>
			);
		} else if (pqeDataState === LoadingState.ERROR) {
			body = (
				<div className="alert alert-error" role="alert">{lang.errorLoadingData}</div>
			);
		} else {
			body = (
				<FontAwesomeIcon icon={fasSpinner} spin />
			);
		}
	} else if (widgetState === WidgetState.MATCHES) {
		if (pqeDataState === LoadingState.SUCCESS) {
			console.assert(personMatches !== null);
			buttons.push(
				<button
					key="browseDatabase"
					type="button"
					className="btn btn-secondary m-1"
					disabled={pqeDataState === LoadingState.LOADING}
					onClick={browseDatabaseClicked}
				>
					{lang.browseDatabase}
				</button>
			);
			if (personMatches.length === 0) {
				body = (
					<p>{lang.noMatches}</p>
				);
			} else {
				body = (
					<Matches
						matches={personMatches}
						onSelected={selectPersonClicked}
					/>
				);
			}
		} else if (pqeDataState === LoadingState.ERROR) {
			body = (
				<div className="alert alert-error" role="alert">{lang.errorLoadingData}</div>
			);
		} else {
			body = (
				<FontAwesomeIcon icon={fasSpinner} spin />
			);
		}
	} else if (widgetState === WidgetState.INITIAL) {
		console.assert(selectedReason === '');
		body = lang.placeholder;
	} else if (widgetState === WidgetState.NOT_QUALIFIED) {
		console.assert(selectedReason === null);
		body = lang.notQualified;
	} else if (widgetState === WidgetState.CUSTOM) {
		console.assert(typeof selectedReason === 'string');
		body = (
			<Fragment>
				<CustomSelectedReason
					selectedReason={selectedReason}
					setSelectedReason={setSelectedReason}
				/>
				<p className="form-text text-muted">{lang.customHint}</p>
			</Fragment>
		);
	} else if (widgetState === WidgetState.SELECTED) {
		console.assert(typeof selectedReason === 'object');
		body = (
			<Match {...selectedReason} />
		);
	}

	return (
		<div className="card" style={{ maxHeight: '100vh' }}>
			<div className="card-body overflow-auto">
				{body}
			</div>
			<div className="card-footer">
				{buttons}
				<button
					key="searchCriteria"
					type="button"
					className="btn btn-info m-1"
					disabled={pqeDataState === LoadingState.LOADING}
					onClick={searchCriteriaClicked}
				>
					{widgetState === WidgetState.MATCHES ? lang.searchCriteriaAgain : lang.searchCriteria}
				</button>
				<button
					key="selectNotQualified"
					type="button"
					className="btn btn-secondary m-1"
					disabled={pqeDataState === LoadingState.LOADING}
					onClick={selectNotQualifiedClicked}
				>
					{lang.selectNotQualified}
				</button>
			</div>
		</div>
	);
}

export function register() {
	document.addEventListener('DOMContentLoaded', (event) => {
		const pqeFields = document.querySelectorAll('.pqe-entry-field');

		pqeFields.forEach((field) => {
			field.classList.add('d-none');
			// Remove client-side validation since it will be unable to focus.
			field.removeAttribute('data-nette-rules');

			const container = document.createElement('div');
			field.parentNode.appendChild(container);

			const form = field.form.elements;
			const firstNameField = form[field.name.replace('pqe', 'firstname')];
			const lastNameField = form[field.name.replace('pqe', 'lastname')];

			render(
				<QualificationChooser
					firstNameField={firstNameField}
					lastNameField={lastNameField}
					onChange={(value) => {
						field.value = value;
					}}
					value={field.value}
				/>,
				container,
			);
		});
	});
}
