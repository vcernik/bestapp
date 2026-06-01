<?php declare(strict_types=1);

namespace App\AdminCore\Model\Orm\AdminActivityLog;

use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;


/**
 * @extends Repository<AdminActivityLog>
 */
class AdminActivityLogRepository extends Repository
{
	public static function getEntityClassNames(): array
	{
		return [AdminActivityLog::class];
	}

	/**
	 * @return ICollection<AdminActivityLog>
	 */
	public function findOlderThan(\DateTimeImmutable $threshold): ICollection
	{
		return $this->findBy(['createdAt<' => $threshold]);
	}
}
