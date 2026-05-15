<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\Sign;

use App\Core\Security\AdminActivityLogger;
use App\Presentation\Admin\Accessory\AdminMenuProvider;
use App\Presentation\Admin\Public\Accessory\BasePublicPresenter;
use App\Presentation\Admin\Public\Accessory\SignFormFactory;
use Nette;
use Nette\Application\UI\Form;

final class SignPresenter extends BasePublicPresenter
{
	public function __construct(
		AdminMenuProvider $adminMenuProvider,
		private readonly SignFormFactory $signFormFactory,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
		parent::__construct($adminMenuProvider);
	}

	protected function createComponentSignInForm(): Form
	{
		return $this->signFormFactory->create(function (\stdClass $values): void {
			$this->signIn($values);
		});
	}

	public function actionOut(): void
	{
		$userId = is_int($this->getUser()->getId()) ? $this->getUser()->getId() : null;
		$this->getUser()->logout();
		$this->adminActivityLogger->log($userId, 'auth.logout');
		$this->flashMessage('Byli jste úspěšně odhlášeni.', 'success');
		$this->redirect('in');
	}

	public function renderIn(): void
	{
	}

	private function signIn(\stdClass $values): void
	{
		try {
			$this->getUser()->login($values->username, $values->password);
			$this->getUser()->setExpiration($values->remember ? '14 days' : '3 hours', true);
			$this->flashMessage('Přihlášení proběhlo úspěšně.', 'success');
			$this->redirect(':Admin:Home:default');
		} catch (Nette\Security\AuthenticationException $exception) {
			$this['signInForm']->addError($exception->getMessage());
		}
	}
}
