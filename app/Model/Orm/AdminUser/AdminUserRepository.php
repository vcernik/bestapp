<?php declare(strict_types=1);

namespace App\Model\Orm\AdminUser;

use Nextras\Orm\Repository\Repository;


/**
 * @extends Repository<AdminUser>
 */
final class AdminUserRepository extends Repository
{
	public static function getEntityClassNames(): array
	{
		return [AdminUser::class];
	}

	public function getByUsername(string $username): ?AdminUser
	{
		/** @var ?AdminUser $user */
		$user = $this->getBy(['username' => mb_strtolower(trim($username))]);
		return $user;
	}
}
