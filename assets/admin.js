import '../app/AdminCore/assets/adminCore.js';

import suneditor from 'suneditor';
import { align, backgroundColor, blockStyle, fontColor, fontSize, link, list } from 'suneditor/plugins';
import cs from 'suneditor/langs/cs';
import 'suneditor/css/editor';
import 'suneditor/css/contents';

import './admin.css';



const editorInstances = new WeakMap();

const editorOptions = {
	plugins: {
		align,
		backgroundColor,
		blockStyle,
		fontColor,
		fontSize,
		link,
		list,
	},
	lang: cs,
	height: '240px',
	buttonList: [
		['undo', 'redo'],
		['bold', 'italic', 'removeFormat'],
		['list'],
		['link'],
		['codeView'],
	],
	attributeBlacklist: {
		'*': 'style',
	},
};

const syncTextarea = (textarea, content) => {
	textarea.value = content;
	textarea.dispatchEvent(new Event('input', { bubbles: true }));
};

const syncEditorToTextarea = (editor, textarea) => {
	syncTextarea(textarea, editor.$.html.get());
};

const enableEditor = (textarea) => {
	if (editorInstances.has(textarea)) {
		return;
	}

	const editor = suneditor.create(textarea, {
		...editorOptions,
		events: {
			onChange: ({ data }) => {
				syncTextarea(textarea, data);
			},
		},
	});

	editorInstances.set(textarea, editor);
};

const disableEditor = (textarea) => {
	const editor = editorInstances.get(textarea);
	if (!editor) {
		return;
	}

	editor.destroy();
	editorInstances.delete(textarea);
	textarea.style.display = '';
};

const toggleModuleEditor = (typeSelect, contentTextarea) => {
	if (typeSelect.value === typeSelect.dataset.wysiwygType) {
		enableEditor(contentTextarea);
		return;
	}

	disableEditor(contentTextarea);
};

const initArticleModuleEditors = () => {
	document.querySelectorAll('[data-article-module-type]').forEach((typeSelect) => {
		const form = typeSelect.closest('form');
		const contentTextarea = form?.querySelector('[data-article-module-content]');

		if (!(typeSelect instanceof HTMLSelectElement) || !(contentTextarea instanceof HTMLTextAreaElement)) {
			return;
		}

		toggleModuleEditor(typeSelect, contentTextarea);
		typeSelect.addEventListener('change', () => toggleModuleEditor(typeSelect, contentTextarea));
		form.addEventListener('submit', () => {
			const editor = editorInstances.get(contentTextarea);
			if (editor) {
				syncEditorToTextarea(editor, contentTextarea);
			}
		}, true);
	});
};

const initStandaloneEditors = () => {
	document.querySelectorAll('textarea[data-wysiwyg-editor]:not([data-article-module-content])').forEach((textarea) => {
		if (!(textarea instanceof HTMLTextAreaElement)) {
			return;
		}

		enableEditor(textarea);

		const form = textarea.closest('form');
		if (!(form instanceof HTMLFormElement)) {
			return;
		}

		form.addEventListener('submit', () => {
			const editor = editorInstances.get(textarea);
			if (editor) {
				syncEditorToTextarea(editor, textarea);
			}
		}, true);
	});
};

document.addEventListener('DOMContentLoaded', () => {
	initArticleModuleEditors();
	initStandaloneEditors();
});
