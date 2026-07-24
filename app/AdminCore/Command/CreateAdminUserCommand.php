<?php declare(strict_types=1);

namespace App\AdminCore\Command;

use App\AdminCore\Model\Orm\AdminUser\AdminUser;
use App\AdminCore\Security\AdminUserManager;

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
