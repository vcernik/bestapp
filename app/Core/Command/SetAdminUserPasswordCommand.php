<?php declare(strict_types=1);

namespace App\Core\Command;

use App\Core\Security\AdminUserManager;
use App\Model\Orm\AdminUser\AdminUser;
use App\Model\Orm\Orm;

final class SetAdminUserPasswordCommand
{
	public function __construct(
		private readonly Orm $orm,
		private readonly AdminUserManager $adminUserManager,
	)
	{
	}

	public function execute(string $username, string $password): AdminUser
	{
		$normalizedUsername = AdminUserManager::normalizeUsername($username);
		$user = $this->orm->adminUsers->getByUsername($normalizedUsername);
		if ($user === null) {
			throw new \RuntimeException('Admin user with this username does not exist.');
		}

		$this->adminUserManager->updatePassword($user, $password);
		return $user;
	}
}