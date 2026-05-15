<?php declare(strict_types=1);

namespace App\Core\Security;

use App\Model\Orm\Orm;

final class AdminPasswordChangeFacade
{
	public function __construct(
		private readonly Orm $orm,
		private readonly AdminUserManager $adminUserManager,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
	}

	public function changePassword(int $userId, string $currentPassword, string $newPassword): void
	{
		$user = $this->orm->adminUsers->getById($userId);
		if ($user === null) {
			throw new \RuntimeException('Přihlášený uživatel neexistuje.');
		}

		if (!$this->adminUserManager->verifyPassword($user, $currentPassword)) {
			$this->adminActivityLogger->log($user->id, 'auth.password_change.failed', []);
			throw new \RuntimeException('Aktuální heslo není správné.');
		}

		$this->adminUserManager->updatePassword($user, $newPassword);
		$this->adminActivityLogger->log($user->id, 'auth.password_change.success', []);
	}
}
