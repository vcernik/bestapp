import netteForms from 'nette-forms';
import naja from "naja";
import {
	AutosubmitPlugin,
	CheckboxPlugin,
	ConfirmPlugin,
	createDatagrids,
	DatepickerPlugin,
	InlinePlugin,
	EditablePlugin,
	ItemDetailPlugin,
	NetteFormsPlugin,
	SelectpickerPlugin,
	SortableJS,
	SortablePlugin,
	TomSelect,
	TreeViewPlugin,
	VanillaDatepicker
} from "../../../vendor/ublaboo/datagrid/assets"
import { NajaAjax } from "../../../vendor/ublaboo/datagrid/assets/ajax";
import Select from "tom-select";
import { Collapse, Dropdown } from "bootstrap";
import Datepicker from 'vanillajs-datepicker/Datepicker';
import cs from 'vanillajs-datepicker/locales/cs';

import './adminCore.css';

netteForms.initOnLoad();



document.addEventListener('DOMContentLoaded', () => {

	// Initialize dropdowns
	Array.from(document.querySelectorAll('.dropdown'))
		.forEach(el => new Dropdown(el))

	// Initialize Naja (nette ajax)
	naja.formsHandler.netteForms = netteForms;
	// Redirect targets like error pages render full HTML, so enforce browser navigation.
	naja.redirectHandler.addEventListener('redirect', (event) => {
		event.detail.setHardRedirect(true);
	});
	naja.initialize();

	// Initialize datagrids
	createDatagrids(new NajaAjax(naja), {
		datagrid: {
			plugins: [
				new AutosubmitPlugin(),
				new CheckboxPlugin(),
				new ConfirmPlugin(),
				new InlinePlugin(),
				new ItemDetailPlugin(),
				new EditablePlugin(),
				new NetteFormsPlugin(netteForms),
				new SortablePlugin(new SortableJS()),
				new DatepickerPlugin(new VanillaDatepicker({ buttonClass: 'btn', language: 'cs' })),
				new SelectpickerPlugin(new TomSelect(Select)),
				new TreeViewPlugin(),
			],
		},
	});



	const menuToggleButton = document.getElementById('adminSidebarToggle');
	const sidebarMenu = document.getElementById('adminSidebarMenu');
	const largeScreenQuery = window.matchMedia('(min-width: 992px)');

	if (!menuToggleButton || !sidebarMenu) {
		return;
	}

	const collapse = Collapse.getOrCreateInstance(sidebarMenu, { toggle: false });

	const syncSidebarForViewport = () => {
		if (largeScreenQuery.matches) {
			sidebarMenu.classList.add('show');
			menuToggleButton.setAttribute('aria-expanded', 'true');
			return;
		}

		sidebarMenu.classList.remove('show');
		menuToggleButton.setAttribute('aria-expanded', 'false');
	};

	syncSidebarForViewport();
	largeScreenQuery.addEventListener('change', syncSidebarForViewport);

	menuToggleButton.addEventListener('click', () => {
		if (largeScreenQuery.matches) {
			return;
		}

		if (sidebarMenu.classList.contains('show')) {
			collapse.hide();
			return;
		}

		collapse.show();
	});

	sidebarMenu.addEventListener('shown.bs.collapse', () => {
		menuToggleButton.setAttribute('aria-expanded', 'true');
	});

	sidebarMenu.addEventListener('hidden.bs.collapse', () => {
		menuToggleButton.setAttribute('aria-expanded', 'false');
	});
});