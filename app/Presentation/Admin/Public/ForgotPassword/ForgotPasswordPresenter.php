<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\ForgotPassword;

use App\Core\Security\AdminPasswordResetFacade;
use App\Presentation\Admin\Accessory\AdminMenuProvider;
use App\Presentation\Admin\Accessory\BootstrapFormFactory;
use App\Presentation\Admin\Public\Accessory\BasePublicPresenter;
use Nette\Application\UI\Form;

final class ForgotPasswordPresenter extends BasePublicPresenter
{
	private ?string $token = null;

	public function __construct(
		AdminMenuProvider $adminMenuProvider,
		private readonly BootstrapFormFactory $bootstrapFormFactory,
		private readonly AdminPasswordResetFacade $adminPasswordResetFacade,
	)
	{
		parent::__construct($adminMenuProvider);
	}

	public function actionReset(?string $token = null): void
	{
		if ($token === null || !$this->adminPasswordResetFacade->isResetTokenValid($token)) {
			$this->flashMessage('Odkaz pro reset hesla je neplatný nebo expiroval.', 'danger');
			$this->redirect('request');
		}

		$this->token = $token;
	}

	protected function createComponentForgotPasswordRequestForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addText('username', 'Uživatelské jméno')
			->setRequired('Zadejte uživatelské jméno.');
		$form->addSubmit('send', 'Odeslat instrukce');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$token = $this->adminPasswordResetFacade->createResetTokenForUsername($values->username);
			$this->flashMessage('Pokud účet existuje, instrukce pro reset hesla byly odeslány.', 'info');

			if ($token !== null && $this->isAjax()) {
				$this->payload->debugResetToken = $token;
			}
			$this->redirect('request');
		};

		return $form;
	}

	protected function createComponentForgotPasswordResetForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addPassword('password', 'Nové heslo')
			->setRequired('Zadejte nové heslo.')
			->addRule($form::MinLength, 'Heslo musí mít alespoň %d znaků.', 12);

		$form->addPassword('passwordVerify', 'Potvrzení hesla')
			->setRequired('Potvrďte nové heslo.')
			->addRule($form::Equal, 'Hesla se neshodují.', $form['password'])
			->setOmitted();

		$form->addSubmit('send', 'Uložit nové heslo');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			if ($this->token === null || !$this->adminPasswordResetFacade->resetPassword($this->token, $values->password)) {
				$this['forgotPasswordResetForm']->addError('Odkaz pro reset hesla je neplatný nebo expiroval.');
				return;
			}

			$this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
			$this->redirect(':Admin:Public:Sign:in');
		};

		return $form;
	}
}
