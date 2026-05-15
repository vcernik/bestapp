<?php declare(strict_types=1);

namespace App\Core\Command;

use App\Core\Security\AdminUserManager;
use App\Model\Orm\AdminUser\AdminUser;

final class CreateAdminUserCommand
{
	public function __construct(
		private readonly AdminUserManager $adminUserManager,
	)
	{
	}

	public function execute(string $username, string $name, string $password, bool $force = false): AdminUser
	{
		return $this->adminUserManager->createUser($username, $name, $password, $force);
	}
}
