<?php declare(strict_types=1);

namespace App\AdminCore\Presentation\Public\Sign;

use App\AdminCore\Presentation\Accessory\BootstrapFormFactory;
use App\AdminCore\Presentation\Public\Accessory\BasePublicPresenter;
use App\AdminCore\Security\AdminActivityLogger;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;

final class SignPresenter extends BasePublicPresenter
{
	#[Persistent]
	public string $backlink = '';

	public function __construct(
		private readonly BootstrapFormFactory $bootstrapFormFactory,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
	}

	protected function createComponentSignInForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addText('username', 'Uživatelské jméno')
			->setRequired('Zadejte uživatelské jméno.');

		$form->addPassword('password', 'Heslo')
			->setRequired('Zadejte heslo.');

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

	/**
	 * @return non-empty-list<string>
	 */
	public function formatTemplateFiles(): array
	{
		return [__DIR__ . '/in.latte'];
	}

	private function signIn(\stdClass $values): void
	{
		try {
			$this->getUser()->login($values->username, $values->password);
			$this->flashMessage('Přihlášení proběhlo úspěšně.', 'success');
			$this->restoreRequest($this->backlink);
			$this->redirect(':Admin:Home:default');
		} catch (Nette\Security\AuthenticationException $exception) {
			$this['signInForm']->addError('Zkontrolujte své uživatelské jméno nebo heslo.');
		}
	}
}
