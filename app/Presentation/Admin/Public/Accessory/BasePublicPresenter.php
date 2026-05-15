<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\Accessory;

use App\Presentation\Admin\Accessory\AdminMenuProvider;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette;

abstract class BasePublicPresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private readonly AdminMenuProvider $adminMenuProvider,
	)
	{
		parent::__construct();
	}

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
