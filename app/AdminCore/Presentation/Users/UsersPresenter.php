<?php declare(strict_types=1);

namespace App\AdminCore\Presentation\Users;

use App\AdminCore\Model\Orm\AdminUser\AdminUser;
use App\AdminCore\Presentation\Accessory\BasePrivatePresenter;
use App\AdminCore\Presentation\Accessory\BootstrapFormFactory;
use App\AdminCore\Presentation\Accessory\DatagridFactory;
use App\AdminCore\Security\AdminUserManager;
use App\AdminCore\Security\AdminUsersAdministrationFacade;
use Contributte\Datagrid\Datagrid;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

final class UsersPresenter extends BasePrivatePresenter
{
	private ?AdminUser $editedUser = null;

	public function __construct(
		private readonly BootstrapFormFactory $bootstrapFormFactory,
		private readonly DatagridFactory $datagridFactory,
		private readonly AdminUsersAdministrationFacade $adminUsersAdministrationFacade,
		private readonly AdminUserManager $adminUserManager,
	)
	{
	}

	protected function startup(): void
	{
		parent::startup();
		$this->addBreadcrumb('AdminCore');
		$this->addBreadcrumb('Uživatelé', ':AdminCore:Users:default');
	}

	public function actionCreate(): void
	{
		$this->editedUser = null;
	}

	public function actionEdit(int $id): void
	{
		try {
			$this->editedUser = $this->adminUsersAdministrationFacade->getUserOrFail($id);
		} catch (\RuntimeException $exception) {
			throw new BadRequestException($exception->getMessage(), 404, $exception);
		}
	}

	public function renderDefault(): void
	{
	}

	public function renderCreate(): void
	{
		$this->addBreadcrumb('Nový uživatel');
	}

	protected function createComponentUsersGrid(): Datagrid
	{
		$grid = $this->datagridFactory->create();
		$grid->setDataSource($this->adminUsersAdministrationFacade->getUsers());

		$grid->addColumnNumber('id', 'ID')
			->setSortable();

		$grid->addColumnText('username', 'Uživatelské jméno')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('name', 'Jméno')
			->setSortable()
			->setFilterText();

		$grid->addColumnText('role', 'Role')
			->setSortable();

		$grid->addColumnText('enabled', 'Stav')
			->setRenderer(static fn (AdminUser $user): Html => Html::el('span')
				->addClass('badge ' . ($user->enabled ? 'text-bg-success' : 'text-bg-danger'))
				->setText($user->enabled ? 'Aktivní' : 'Zakázaný'))
			->setSortable();

		$grid->addAction('edit', 'Upravit', 'edit')
			->setClass('btn btn-sm btn-outline-primary');

		return $grid;
	}

	public function renderEdit(): void
	{
		if ($this->editedUser === null) {
			throw new BadRequestException('Uživatel nebyl načten.', 404);
		}

		$this->addBreadcrumb('Editace uživatele ' . $this->editedUser->username);
	}

	protected function createComponentCreateUserForm(): Form
	{
		$form = $this->bootstrapFormFactory->create();
		$this->prepareUserForm($form, null, 'Nové heslo', 'Vytvořit uživatele');

		/** @var TextInput $newPassword */
		$newPassword = $form['newPassword'];
		$newPassword
			->setRequired('Zadejte nové heslo.')
			->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků.', 10);

		/** @var TextInput $newPasswordConfirm */
		$newPasswordConfirm = $form['newPasswordConfirm'];
		$newPasswordConfirm
			->setRequired('Pro kontrolu zadejte nové heslo ještě jednou.')
			->addRule($form::Equal, 'Zadaná hesla se neshodují.', $newPassword);

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$actorUserId = $this->resolveActorUserId($form);
			if ($actorUserId === null) {
				return;
			}

			try {
				$createdUser = $this->adminUsersAdministrationFacade->createUser(
					$actorUserId,
					(string) $values->username,
					(string) $values->name,
					(string) $values->role,
					(bool) $values->enabled,
					(string) $values->newPassword,
				);

				$this->flashMessage('Uživatel byl vytvořen.', 'success');
				$this->redirect('edit', $createdUser->id);
			} catch (\RuntimeException $exception) {
				$form->addError($exception->getMessage());
			}
		};

		return $form;
	}

	protected function createComponentEditUserForm(): Form
	{
		if ($this->editedUser === null) {
			throw new BadRequestException('Uživatel nebyl načten.', 404);
		}

		$form = $this->bootstrapFormFactory->create();
		$this->prepareUserForm($form, $this->editedUser, 'Nové heslo (volitelné)', 'Uložit změny');

		/** @var TextInput $newPassword */
		$newPassword = $form['newPassword'];
		$newPassword
			->addCondition($form::Filled)
				->addRule($form::MinLength, 'Nové heslo musí mít alespoň %d znaků.', 10);

		/** @var TextInput $newPasswordConfirm */
		$newPasswordConfirm = $form['newPasswordConfirm'];
		$newPasswordConfirm
			->addConditionOn($newPassword, $form::Filled)
				->setRequired('Pro kontrolu zadejte nové heslo ještě jednou.')
				->addRule($form::Equal, 'Zadaná hesla se neshodují.', $newPassword);

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$actorUserId = $this->resolveActorUserId($form);
			if ($actorUserId === null) {
				return;
			}

			if ($this->editedUser === null) {
				throw new BadRequestException('Uživatel nebyl načten.', 404);
			}

			try {
				$this->adminUsersAdministrationFacade->updateUser(
					$actorUserId,
					$this->editedUser->id,
					(string) $values->username,
					(string) $values->name,
					(string) $values->role,
					(bool) $values->enabled,
					$values->newPassword !== '' ? (string) $values->newPassword : null,
				);

				$this->flashMessage('Uživatel byl upraven.', 'success');
				$this->redirect('default');
			} catch (\RuntimeException $exception) {
				$form->addError($exception->getMessage());
			}
		};

		return $form;
	}

	private function prepareUserForm(Form $form, ?AdminUser $editedUser, string $passwordLabel, string $submitLabel): void
	{
		$roles = [];
		foreach ($this->adminUserManager->getAvailableRoles() as $role) {
			$roles[$role] = $role;
		}

		$form->addSelect('role', 'Role', $roles)
			->setRequired('Vyberte roli.')
			->setDefaultValue($editedUser?->role ?? $this->adminUserManager->getDefaultRole());

		$form->addText('username', 'Uživatelské jméno')
			->setRequired('Vyplňte uživatelské jméno.')
			->setDefaultValue($editedUser?->username ?? '');

		$form->addText('name', 'Jméno')
			->setRequired('Vyplňte jméno.')
			->setDefaultValue($editedUser?->name ?? '');

		$form->addCheckbox('enabled', 'Uživatel je aktivní')
			->setDefaultValue($editedUser?->enabled ?? true);

		$form->addPassword('newPassword', $passwordLabel);
		$form->addPassword('newPasswordConfirm', 'Nové heslo znovu');

		$form->addSubmit('save', $submitLabel);
	}

	private function resolveActorUserId(Form $form): ?int
	{
		$actorUserId = $this->getUser()->getId();
		if (!is_int($actorUserId)) {
			$form->addError('Neplatná přihlášená identita.');
			return null;
		}

		return $actorUserId;
	}
}
