<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\Sign;

use App\Core\Security\AdminActivityLogger;
use App\Presentation\Admin\Accessory\AdminMenuProvider;
use App\Presentation\Admin\Accessory\BootstrapFormFactory;
use App\Presentation\Admin\Public\Accessory\BasePublicPresenter;
use Nette;
use Nette\Application\UI\Form;

final class SignPresenter extends BasePublicPresenter
{
	public function __construct(
		AdminMenuProvider $adminMenuProvider,
		private readonly BootstrapFormFactory $bootstrapFormFactory,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
		parent::__construct($adminMenuProvider);
	}

	protected function createComponentSignInForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addText('username', 'Uživatelské jméno')
			->setRequired('Zadejte uživatelské jméno.');

		$form->addPassword('password', 'Heslo')
			->setRequired('Zadejte heslo.');

		$form->addCheckbox('remember', 'Zapamatovat si mě');
		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$this->signIn($values);
		};

		return $form;
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
			$expiration = $values->remember ? '3 hours' : '3 hours'; // Nastavíme maximální dobu na 3 hodiny
			$this->getUser()->setExpiration($expiration, true);
			$this->flashMessage('Přihlášení proběhlo úspěšně.', 'success');
			$this->redirect(':Admin:Home:default');
		} catch (Nette\Security\AuthenticationException $exception) {
			$this['signInForm']->addError('Zkontrolujte své uživatelské jméno nebo heslo.');
		}
	}
}
