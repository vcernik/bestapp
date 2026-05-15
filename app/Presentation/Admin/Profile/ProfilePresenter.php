<?php declare(strict_types=1);

namespace App\Presentation\Admin\Profile;

use App\Core\Security\AdminPasswordChangeFacade;
use App\Presentation\Admin\Accessory\AdminMenuProvider;
use App\Presentation\Admin\Accessory\BasePrivatePresenter;
use App\Presentation\Admin\Accessory\BootstrapFormFactory;
use Nette\Application\UI\Form;

final class ProfilePresenter extends BasePrivatePresenter
{
	public function __construct(
		AdminMenuProvider $adminMenuProvider,
		private readonly BootstrapFormFactory $bootstrapFormFactory,
		private readonly AdminPasswordChangeFacade $adminPasswordChangeFacade,
	)
	{
		parent::__construct($adminMenuProvider);
	}

	protected function createComponentChangePasswordForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addPassword('currentPassword', 'Aktuální heslo')
			->setRequired('Zadejte aktuální heslo.');

		$form->addPassword('newPassword', 'Nové heslo')
			->setRequired('Zadejte nové heslo.')
			->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků.', 12);

		$form->addPassword('newPasswordVerify', 'Potvrzení nového hesla')
			->setRequired('Potvrďte nové heslo.')
			->addRule($form::Equal, 'Hesla se neshodují.', $form['newPassword'])
			->setOmitted();

		$form->addSubmit('send', 'Změnit heslo');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$userId = $this->getUser()->getId();
			if (!is_int($userId)) {
				$form->addError('Neplatná přihlášená identita.');
				return;
			}

			try {
				$this->adminPasswordChangeFacade->changePassword($userId, $values->currentPassword, $values->newPassword);
				$this->flashMessage('Heslo bylo úspěšně změněno.', 'success');
				$this->redirect('this');
			} catch (\RuntimeException $exception) {
				$form->addError($exception->getMessage());
			}
		};

		return $form;
	}
}
