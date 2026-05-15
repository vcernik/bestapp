<?php declare(strict_types=1);

namespace App\Core\Security;

use App\Model\Orm\AdminUser\AdminUser;
use App\Model\Orm\Orm;
use Nette\Security\Passwords;

final class AdminUserManager
{
	public function __construct(
		private readonly Orm $orm,
		private readonly Passwords $passwords,
	)
	{
	}

	public function createUser(string $username, string $name, string $password, bool $force = false): AdminUser
	{
		$normalizedUsername = self::normalizeUsername($username);
		$normalizedName = trim($name);
		$this->assertStrongPassword($password);

		if ($normalizedName === '') {
			throw new \RuntimeException('Name is required.');
		}

		$existing = $this->orm->adminUsers->getByUsername($normalizedUsername);
		if ($existing !== null && !$force) {
			throw new \RuntimeException('Admin user with this username already exists.');
		}

		if ($existing === null) {
			$existing = new AdminUser;
		}

		$existing->username = $normalizedUsername;
		$existing->name = $normalizedName;

		$existing->passwordHash = $this->passwords->hash($password);
		$existing->failedCount = 0;
		$existing->blockedUntil = null;
		$existing->lastAttemptAt = null;
		$existing->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($existing);
		return $existing;
	}

	public function updatePassword(AdminUser $user, string $password): void
	{
		$this->assertStrongPassword($password);
		$user->passwordHash = $this->passwords->hash($password);
		$user->failedCount = 0;
		$user->blockedUntil = null;
		$user->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($user);
	}

	public function verifyPassword(AdminUser $user, string $password): bool
	{
		return $this->passwords->verify($password, $user->passwordHash);
	}

	public function assertStrongPassword(string $password): void
	{
		if (strlen($password) < 10) {
			throw new \RuntimeException('Password must be at least 10 characters long.');
		}
	}

	public static function normalizeUsername(string $username): string
	{
		return mb_strtolower(trim($username));
	}
}
