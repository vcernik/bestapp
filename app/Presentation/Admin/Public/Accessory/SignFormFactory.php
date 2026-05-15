<?php declare(strict_types=1);

namespace App\Presentation\Admin\Public\Accessory;

use App\Presentation\Admin\Accessory\BootstrapFormFactory;
use Nette\Application\UI\Form;

final class SignFormFactory
{
	public function __construct(
		private readonly BootstrapFormFactory $bootstrapFormFactory,
	)
	{
	}

	/**
	 * @param callable(\stdClass): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$form->addText('username', 'Uživatelské jméno')
			->setRequired('Zadejte uživatelské jméno.');

		$form->addPassword('password', 'Heslo')
			->setRequired('Zadejte heslo.');

		$form->addCheckbox('remember', 'Zapamatovat si mě');
		$form->addSubmit('send', 'Přihlásit se');

		$form->onSuccess[] = static function (Form $form, \stdClass $values) use ($onSuccess): void {
			$onSuccess($values);
		};

		return $form;
	}
}
