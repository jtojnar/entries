import { EditorView, basicSetup } from 'codemirror';
import { html, htmlLanguage } from '@codemirror/lang-html';
import { PRESUBMIT_EVENT } from './form';
import { CompletionContext } from '@codemirror/autocomplete';

function editorFromTextArea(textarea) {
	const variables = getVariableSuggestions(textarea);
	const extensions = makeEditorExtensions(variables);

	let view = new EditorView({
		doc: textarea.value,
		extensions,
	});

	textarea.parentNode.insertBefore(view.dom, textarea);
	textarea.style.display = 'none';

	if (textarea.form) {
		textarea.form.addEventListener(PRESUBMIT_EVENT, (event) => {
			textarea.value = view.state.doc.toString();
		});
	}

	return view;
}

export function register() {
	document.addEventListener('DOMContentLoaded', (event) => {
		const textareas = document.querySelectorAll('textarea.codemirror');

		textareas.forEach((textarea) => {
			editorFromTextArea(textarea);
		});
	});
}

/**
 * Extracts completions from `data-variable-suggestions` attribute on the `textarea`.
 */
function getVariableSuggestions(/** @type {HTMLTextAreaElement} */ textarea) {
	if (textarea.dataset.variableSuggestions === undefined) {
		return [];
	}

	return JSON.parse(textarea.dataset.variableSuggestions);
}

/**
 * Creates auto-completion that suggests known PHP variables after typing `$`.
 */
function makeVariableCompletion(variables) {
	const options = variables.map((label) => ({ label, type: 'variable' }));
	return function (/** @type {CompletionContext} */ context) {
		let word = context.matchBefore(/\$/);
		if (word === null) {
			return null;
		}

		return {
			from: word.to,
			options,
		};
	};
}

/**
 * Sets up extensions for HTML editor, potentially autocompleting PHP variables.
 */
function makeEditorExtensions(variables) {
	const variableCompletion =
		variables.length > 0
			? htmlLanguage.data.of({
					autocomplete: makeVariableCompletion(variables),
				})
			: [];

	return [
		basicSetup,
		EditorView.lineWrapping,
		EditorView.theme({
			'.cm-content, .cm-gutter': {
				minHeight: '18em',
			},
		}),
		variableCompletion,
		html(),
	];
}
