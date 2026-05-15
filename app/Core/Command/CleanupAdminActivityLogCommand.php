<?php declare(strict_types=1);

namespace App\Core\Command;

use App\Model\Orm\Orm;

final class CleanupAdminActivityLogCommand
{
	public function __construct(
		private readonly Orm $orm,
	)
	{
	}

	public function execute(string $olderThanExpression): int
	{
		$interval = \DateInterval::createFromDateString($olderThanExpression);
		if ($interval === false) {
			throw new \RuntimeException('Invalid --older-than value.');
		}

		$threshold = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->sub($interval);
		$toDelete = $this->orm->adminActivityLogs->findOlderThan($threshold);

		$count = 0;
		foreach ($toDelete as $entity) {
			$this->orm->remove($entity);
			$count++;
		}

		$this->orm->flush();
		return $count;
	}
}
