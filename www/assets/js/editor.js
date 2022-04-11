import { EditorState, EditorView, basicSetup } from '@codemirror/basic-setup';
import { html } from '@codemirror/lang-html';

function editorFromTextArea(textarea, extensions) {
	let view = new EditorView({
		state: EditorState.create({
			doc: textarea.value,
			extensions,
		}),
	});

	textarea.parentNode.insertBefore(view.dom, textarea);
	textarea.style.display = 'none';

	if (textarea.form) textarea.form.addEventListener('submit', () => {
		textarea.value = view.state.doc.toString();
	});

	return view;
}

export function register() {
	document.addEventListener('DOMContentLoaded', (event) => {
		const textareas = document.querySelectorAll('textarea.codemirror');

		const extensions = [
			basicSetup,
			EditorView.lineWrapping,
			EditorView.theme({
				'.cm-content, .cm-gutter': {
					minHeight: '18em'
				}
			}),
			html(),
		];

		textareas.forEach((textarea) => {
			editorFromTextArea(textarea, extensions);
		});
	});
}
