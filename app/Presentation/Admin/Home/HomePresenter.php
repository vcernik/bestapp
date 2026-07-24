<?php declare(strict_types=1);

namespace App\Presentation\Admin\Home;

use App\AdminCore\Presentation\Accessory\BasePrivatePresenter;

final class HomePresenter extends BasePrivatePresenter
{
	

	protected function startup(): void
	{
		parent::startup();
		$this->addBreadcrumb('Dashboard');
	}

	public function renderDefault(): void
	{
	}
}
