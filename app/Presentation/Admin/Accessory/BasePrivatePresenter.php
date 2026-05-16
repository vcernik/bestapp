<?php declare(strict_types=1);

namespace App\Presentation\Admin\Accessory;

use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette;

abstract class BasePrivatePresenter extends Nette\Application\UI\Presenter
{
	/**
	 * @var list<array{title: string, link: string|null}>
	 */
	protected array $breadcrumbs = [];

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
		$this->getUser()->setExpiration('3 hours', true);

		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('Relace vypršela nebo nejste přihlášen. Přihlaste se prosím znovu.', 'info');
			$this->redirect(':Admin:Public:Sign:in');
		}

		$this->breadcrumbs = [];
		$this->addBreadcrumb('Administrace', 'Home:default');
	}

	protected function beforeRender(): void
	{
		parent::beforeRender();
		$this->template->currentUser = $this->getUser()->getIdentity();
		$this->template->adminMenuItems = $this->adminMenuProvider->getItems();
		$this->template->appName = $this->adminMenuProvider->getAppName();
		$this->template->breadcrumbs = $this->breadcrumbs;
	}

	/**
	 * Přidá breadcrumb položku do seznamu.
	 */
	protected function addBreadcrumb(string $title, ?string $link = null): void
	{
		$this->breadcrumbs[] = [
			'title' => $title,
			'link' => $link,
		];
	}

	/**
	 * @return list<string>
	 */
	public function formatLayoutTemplateFiles(): array
	{
		return [__DIR__ . '/../@layout.private.latte'];
	}
}
