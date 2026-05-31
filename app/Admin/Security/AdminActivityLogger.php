<?php declare(strict_types=1);

namespace App\Admin\Security;

use App\Admin\Model\Orm\AdminActivityLog\AdminActivityLog;
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
		$entry->userId = $this->resolveExistingUserId($userId);
		$entry->action = $action;
		$entry->payloadJson = json_encode($data, JSON_THROW_ON_ERROR);
		$entry->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($entry);
	}

	private function resolveExistingUserId(?int $userId): ?int
	{
		if ($userId === null || $userId <= 0) {
			return null;
		}

		return $this->orm->adminUsers->getById($userId) !== null ? $userId : null;
	}
}
