<?php declare(strict_types=1);

namespace App\Admin\Presentation\Profile;

use App\Admin\Presentation\Accessory\BasePrivatePresenter;
use App\Admin\Presentation\Accessory\BootstrapFormFactory;
use App\Admin\Security\AdminPasswordChangeFacade;
use Nette\Application\UI\Form;

final class ProfilePresenter extends BasePrivatePresenter
{
	public function __construct(
		private readonly BootstrapFormFactory $bootstrapFormFactory,
		private readonly AdminPasswordChangeFacade $adminPasswordChangeFacade,
	)
	{
	}

	protected function startup(): void
	{
		parent::startup();
		$this->addBreadcrumb('Změna hesla');
	}

	protected function createComponentChangePasswordForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addPassword('currentPassword', 'Aktuální heslo')
			->setRequired('Zadejte aktuální heslo.');

		$form->addPassword('newPassword', 'Nové heslo')
			->setRequired('Zadejte nové heslo.')
			->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků.', 10);

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

	/**
	 * @return list<string>
	 */
	public function formatTemplateFiles(): array
	{
		return [__DIR__ . '/default.latte'];
	}
}
