<?php declare(strict_types=1);

namespace App\Core\Security;

use App\Model\Orm\AdminActivityLog\AdminActivityLog;
use App\Model\Orm\Orm;

final class AdminActivityLogger
{
	public function __construct(
		private readonly Orm $orm,
	)
	{
	}

	/**
	 * @param array<string, scalar|array<array-key, scalar|null>|null> $data
	 */
	public function log(?int $userId, string $action, array $data = []): void
	{
		$entry = new AdminActivityLog;
		$entry->userId = $userId;
		$entry->action = $action;
		$entry->payloadJson = json_encode($data, JSON_THROW_ON_ERROR);
		$entry->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($entry);
	}
}
