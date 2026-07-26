<?php declare(strict_types=1);

namespace App\AdminCore\Presentation\Error4xx;

use App\AdminCore\Presentation\Accessory\AdminMenuProvider;
use Nette;
use Nette\Bridges\SecurityHttp\SessionStorage;

final class Error4xxPresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private readonly AdminMenuProvider $adminMenuProvider,
	)
	{
	}

	public function renderDefault(?Nette\Application\BadRequestException $exception = null, ?int $code = null, ?string $message = null): void
	{
		$storage = $this->getUser()->getStorage();
		if ($storage instanceof SessionStorage) {
			$storage->setNamespace('admin');
		}

		$httpCode = $code ?? $exception?->getHttpCode() ?? ($exception?->getCode() ?: 404);
		$errorMessage = $message ?? $exception?->getMessage() ?? '';

		$this->template->httpCode = $httpCode;
		$this->template->errorMessage = $errorMessage;
		$this->template->appName = $this->adminMenuProvider->getAppName();
		$this->template->adminMenuItems = $this->adminMenuProvider->getItems();
		$this->template->breadcrumbs = [
			['title' => 'Administrace', 'link' => ':Admin:Home:default'],
			['title' => 'Chyba', 'link' => null],
		];
		$this->template->pageTitle = 'Přístup není dostupný (chyba ' . $httpCode . ')';

		$identity = $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity() : null;
		$this->template->currentUser = $identity ?? (object) ['name' => 'Správce', 'username' => 'spravce'];
	}

	/**
	 * @return non-empty-list<string>
	 */
	public function formatLayoutTemplateFiles(): array
	{
		return [__DIR__ . '/../@layout.private.latte'];
	}
}
