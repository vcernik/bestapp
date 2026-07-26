<?php declare(strict_types=1);

namespace App\AdminCore\Security;

use App\AdminCore\Model\Orm\AdminOrm;
use App\AdminCore\Model\Orm\AdminUser\AdminUser;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

final class AdminSessionSecurityService
{
	public const string FORCED_LOGOUT_MESSAGE = 'Váš účet už není platný. Přihlaste se prosím znovu.';

	public function __construct(
		private readonly AdminOrm $orm,
	)
	{
	}

	public function validateAndRefresh(User $user): bool
	{
		$userId = $user->getId();
		if (!is_int($userId)) {
			$user->logout(true);
			return false;
		}

		$adminUser = $this->orm->adminUsers->getById($userId);
		if ($adminUser === null || !$adminUser->enabled) {
			$user->logout(true);
			return false;
		}

		$identityTimestamp = $this->extractUpdatedAtTimestamp($user->getIdentity());
		$databaseTimestamp = $adminUser->updatedAt->getTimestamp();
		if ($identityTimestamp !== $databaseTimestamp) {
			$user->login($this->createIdentity($adminUser));
		}

		return true;
	}

	private function createIdentity(AdminUser $adminUser): SimpleIdentity
	{
		return new SimpleIdentity($adminUser->id, [$adminUser->role], [
			'username' => $adminUser->username,
			'name' => $adminUser->name,
			'role' => $adminUser->role,
			'updatedAt' => $adminUser->updatedAt->getTimestamp(),
		]);
	}

	private function extractUpdatedAtTimestamp(?IIdentity $identity): ?int
	{
		if ($identity === null || !method_exists($identity, 'getData')) {
			return null;
		}

		/** @var mixed $value */
		$value = $identity->getData()['updatedAt'] ?? null;
		if (is_int($value)) {
			return $value;
		}

		if (is_string($value) && ctype_digit($value)) {
			return (int) $value;
		}

		return null;
	}
}
