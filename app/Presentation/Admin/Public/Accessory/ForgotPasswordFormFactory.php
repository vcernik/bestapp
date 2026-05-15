<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\Accessory;

use App\Presentation\Admin\Accessory\BootstrapFormFactory;
use Nette\Application\UI\Form;

final class ForgotPasswordFormFactory
{
	public function __construct(
		private readonly BootstrapFormFactory $bootstrapFormFactory,
	)
	{
	}

	/**
	 * @param callable(\stdClass): void $onRequestSuccess
	 */
	public function createRequestForm(callable $onRequestSuccess): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addText('username', 'Uživatelské jméno')
			->setRequired('Zadejte uživatelské jméno.');
		$form->addSubmit('send', 'Odeslat instrukce');

		$form->onSuccess[] = static function (Form $form, \stdClass $values) use ($onRequestSuccess): void {
			$onRequestSuccess($values);
		};

		return $form;
	}

	/**
	 * @param callable(\stdClass): void $onResetSuccess
	 */
	public function createResetForm(callable $onResetSuccess): Form
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

		$form->onSuccess[] = static function (Form $form, \stdClass $values) use ($onResetSuccess): void {
			$onResetSuccess($values);
		};

		return $form;
	}
}
