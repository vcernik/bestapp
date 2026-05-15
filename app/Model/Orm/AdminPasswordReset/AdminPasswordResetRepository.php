<?php declare(strict_types=1);

namespace App\Model\Orm\AdminPasswordReset;

use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;


/**
 * @extends Repository<AdminPasswordReset>
 */
final class AdminPasswordResetRepository extends Repository
{
	public static function getEntityClassNames(): array
	{
		return [AdminPasswordReset::class];
	}

	public function findActiveByUserId(int $userId): ICollection
	{
		return $this->findBy(['userId' => $userId, 'usedAt' => null]);
	}
}
