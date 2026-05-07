<?php declare(strict_types=1);

namespace App\Presentation\Admin\Accessory;

use Contributte\Datagrid\Datagrid;
use Contributte\Datagrid\Localization\SimpleTranslator;

final class DatagridFactory
{
	private readonly SimpleTranslator $translator;

	public function __construct()
	{
		$this->translator = new SimpleTranslator([
			'contributte_datagrid.no_item_found_reset' => 'Položky nenalezeny. Můžete resetovat filtr',
			'contributte_datagrid.no_item_found' => 'Položky nenalezeny.',
			'contributte_datagrid.here' => 'zde',
			'contributte_datagrid.items' => 'Položky',
			'contributte_datagrid.all' => 'vše',
			'contributte_datagrid.from' => 'z',
			'contributte_datagrid.reset_filter' => 'Resetovat filtr',
			'contributte_datagrid.group_actions' => 'Hromadné akce',
			'contributte_datagrid.show' => 'Zobrazit',
			'contributte_datagrid.add' => 'Přidat',
			'contributte_datagrid.edit' => 'Upravit',
			'contributte_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
			'contributte_datagrid.show_default_columns' => 'Zobrazit výchozí sloupce',
			'contributte_datagrid.hide_column' => 'Skrýt sloupec',
			'contributte_datagrid.action' => 'Akce',
			'contributte_datagrid.previous' => 'Předchozí',
			'contributte_datagrid.next' => 'Další',
			'contributte_datagrid.choose' => 'Vyberte',
			'contributte_datagrid.choose_input_required' => 'Text hromadné akce nesmí být prázdný',
			'contributte_datagrid.execute' => 'Provést',
			'contributte_datagrid.save' => 'Uložit',
			'contributte_datagrid.cancel' => 'Zrušit',
			'contributte_datagrid.multiselect_choose' => 'Vyberte',
			'contributte_datagrid.multiselect_selected' => '{0} vybráno',
			'contributte_datagrid.filter_submit_button' => 'Filtrovat',
			'contributte_datagrid.show_filter' => 'Zobrazit filtr',
			'contributte_datagrid.per_page_submit' => 'Změnit',
		]);
	}

	public function create(): Datagrid
	{
		$grid = new Datagrid;
		$grid->setTranslator($this->translator);

		return $grid;
	}
}