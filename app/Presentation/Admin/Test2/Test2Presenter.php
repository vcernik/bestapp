<?php declare(strict_types=1);

namespace App\Presentation\Admin\Test2;

use App\AdminCore\Presentation\Accessory\BasePrivatePresenter;

final class Test2Presenter extends BasePrivatePresenter
{	
	protected function startup(): void
	{
		parent::startup();
		$this->addBreadcrumb('Správa obsahu');
		$this->addBreadcrumb('Kategorie');
	}

	public function renderDefault(): void
	{
	}
}
