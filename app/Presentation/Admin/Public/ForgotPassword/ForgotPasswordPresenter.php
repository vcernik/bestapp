<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\ForgotPassword;

use App\Core\Security\AdminPasswordResetFacade;
use App\Presentation\Admin\Accessory\AdminMenuProvider;
use App\Presentation\Admin\Public\Accessory\BasePublicPresenter;
use App\Presentation\Admin\Public\Accessory\ForgotPasswordFormFactory;
use Nette\Application\UI\Form;

final class ForgotPasswordPresenter extends BasePublicPresenter
{
	private ?string $token = null;

	public function __construct(
		AdminMenuProvider $adminMenuProvider,
		private readonly ForgotPasswordFormFactory $forgotPasswordFormFactory,
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
		return $this->forgotPasswordFormFactory->createRequestForm(function (\stdClass $values): void {
			$token = $this->adminPasswordResetFacade->createResetTokenForUsername($values->username);
			$this->flashMessage('Pokud účet existuje, instrukce pro reset hesla byly odeslány.', 'info');

			if ($token !== null && $this->isAjax()) {
				$this->payload->debugResetToken = $token;
			}
			$this->redirect('request');
		});
	}

	protected function createComponentForgotPasswordResetForm(): Form
	{
		return $this->forgotPasswordFormFactory->createResetForm(function (\stdClass $values): void {
			if ($this->token === null || !$this->adminPasswordResetFacade->resetPassword($this->token, $values->password)) {
				$this['forgotPasswordResetForm']->addError('Odkaz pro reset hesla je neplatný nebo expiroval.');
				return;
			}

			$this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
			$this->redirect(':Admin:Public:Sign:in');
		});
	}
}
