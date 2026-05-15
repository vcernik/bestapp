<?php declare(strict_types=1);

namespace App\Presentation\Admin\Sign;

use App\Presentation\Admin\Accessory\AdminMenuProvider;
use Nette;


final class SignPresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private readonly AdminMenuProvider $adminMenuProvider,
	)
	{
		parent::__construct();
	}

	public function renderIn(): void
	{
		$this->template->appName = $this->adminMenuProvider->getAppName();
	}
}
