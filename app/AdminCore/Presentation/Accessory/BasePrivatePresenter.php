<?php declare(strict_types=1);

namespace App\AdminCore\Presentation\Accessory;

use App\AdminCore\Security\AdminActivityLogger;
use App\AdminCore\Security\AdminSessionSecurityService;
use Nette;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\DI\Attributes\Inject;

abstract class BasePrivatePresenter extends Nette\Application\UI\Presenter
{
	/**
	 * @var list<array{title: string, link: string|null}>
	 */
	protected array $breadcrumbs = [];

	#[Inject]
	public AdminMenuProvider $adminMenuProvider;

	#[Inject]
	public AdminSessionSecurityService $adminSessionSecurityService;

	#[Inject]
	public AdminActivityLogger $adminActivityLogger;

	protected function startup(): void
	{
		parent::startup();

		$storage = $this->getUser()->getStorage();
		if ($storage instanceof SessionStorage) {
			$storage->setNamespace('admin');
		}
		$this->getUser()->setExpiration('3 hours', true);

		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('Přihlaste se, prosím.', 'info');
			$this->redirect(':AdminCore:Public:Sign:in', ['backlink' => $this->storeRequest()]);
		}

		if (!$this->adminSessionSecurityService->validateAndRefresh($this->getUser())) {
			$this->flashMessage(AdminSessionSecurityService::FORCED_LOGOUT_MESSAGE, 'warning');
			$this->redirect(':AdminCore:Public:Sign:in', ['backlink' => $this->storeRequest()]);
		}

		$permission = $this->resolveCurrentPermission();
		if (!$this->getUser()->isAllowed($permission['resource'], $permission['privilege'])) {
			$this->logAdminActivity('auth.authorization.denied', [
				'resource' => $permission['resource'],
				'privilege' => $permission['privilege'],
				'presenter' => $this->getName(),
				'action' => $this->getAction(),
				'signal' => $this->getSignal(),
			]);

			$this->redirect(':AdminCore:Error4xx:default', [
				'code' => 403,
				'message' => 'Nemáte oprávnění pro zobrazení této stránky nebo provedení této akce.',
			]);
		}

		$this->breadcrumbs = [];
		$this->addBreadcrumb('Administrace', ':Admin:Home:default');
	}

	protected function beforeRender(): void
	{
		parent::beforeRender();
		$this->template->currentUser = $this->getUser()->getIdentity();
		$this->template->adminMenuItems = $this->adminMenuProvider->getItems();
		$this->template->appName = $this->adminMenuProvider->getAppName();
		$this->template->breadcrumbs = $this->breadcrumbs;
	}

	protected function addBreadcrumb(string $title, ?string $link = null): void
	{
		$this->breadcrumbs[] = [
			'title' => $title,
			'link' => $link,
		];

		$this->template->breadcrumbs = $this->breadcrumbs;
		$this->template->pageTitle = $title;
	}

	/**
	 * @param array<string, scalar|array<array-key, scalar|null>|null> $data
	 */
	protected function logAdminActivity(string $action, array $data = []): void
	{
		$userId = $this->getUser()->getId();
		$this->adminActivityLogger->log(is_int($userId) ? $userId : null, $action, $data);
	}

	/**
	 * @return non-empty-list<string>
	 */
	public function formatLayoutTemplateFiles(): array
	{
		return [__DIR__ . '/../@layout.private.latte'];
	}

	/**
	 * @return array{resource: string, privilege: string}
	 */
	private function resolveCurrentPermission(): array
	{
		$signal = $this->getSignal();
		if ($signal !== null) {
			$presenterName = $this->getName() ?? '';
			if ($presenterName === '') {
				throw new Nette\InvalidStateException('Presenter name is not available.');
			}

			[, $signalName] = $signal;

			return [
				'resource' => $presenterName,
				'privilege' => $signalName,
			];
		}

		$presenterName = $this->getName() ?? '';
		if ($presenterName === '') {
			throw new Nette\InvalidStateException('Presenter name is not available.');
		}

		$action = $this->getAction();
		$mappedPermission = $this->adminMenuProvider->resolvePermissionForPresenterAction($presenterName, $action);
		if ($mappedPermission !== null) {
			return $mappedPermission;
		}

		return [
			'resource' => $presenterName,
			'privilege' => $action,
		];
	}
}
