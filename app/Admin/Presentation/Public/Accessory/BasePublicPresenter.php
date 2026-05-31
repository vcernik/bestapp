<?php declare(strict_types=1);

namespace App\Admin\Presentation\Public\Accessory;

use App\Admin\Presentation\Accessory\AdminMenuProvider;
use Nette;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\DI\Attributes\Inject;

abstract class BasePublicPresenter extends Nette\Application\UI\Presenter
{
	#[Inject]
	public AdminMenuProvider $adminMenuProvider;

	protected function startup(): void
	{
		parent::startup();

		$storage = $this->getUser()->getStorage();
		if ($storage instanceof SessionStorage) {
			$storage->setNamespace('admin');
		}
	}

	protected function beforeRender(): void
	{
		parent::beforeRender();
		$this->template->appName = $this->adminMenuProvider->getAppName();
	}

	/**
	 * @return list<string>
	 */
	public function formatLayoutTemplateFiles(): array
	{
		return [__DIR__ . '/../../@layout.public.latte'];
	}
}
