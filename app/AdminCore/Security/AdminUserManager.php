<?php declare(strict_types=1);

namespace App\AdminCore\Security;

use App\AdminCore\Model\Orm\AdminOrm;
use App\AdminCore\Model\Orm\AdminUser\AdminUser;
use Nette\Security\Passwords;

final class AdminUserManager
{
	/** @var list<string> */
	private array $availableRoles;

	private string $defaultRole;

	/**
	 * @param list<string> $availableRoles
	 */
	public function __construct(
		private readonly AdminOrm $orm,
		private readonly Passwords $passwords,
		array $availableRoles = [],
	)
	{
		$this->availableRoles = array_values(array_filter(
			$availableRoles,
			static fn (string $role): bool => $role !== '',
		));

		if ($this->availableRoles === []) {
			throw new \RuntimeException('Admin roles are not configured. Set parameters.admin.acl.roles in config/admin.neon.');
		}

		$this->defaultRole = $this->availableRoles[0];
	}

	public function createUser(string $username, string $name, string $password, bool $force = false, ?string $role = null, bool $enabled = true): AdminUser
	{
		$normalizedUsername = self::normalizeUsername($username);
		$normalizedName = trim($name);
		$normalizedRole = $this->normalizeRole($role ?? $this->defaultRole);
		$this->assertStrongPassword($password);

		if ($normalizedName === '') {
			throw new \RuntimeException('Jméno je povinné.');
		}

		$existing = $this->orm->adminUsers->getByUsername($normalizedUsername);
		if ($existing !== null && !$force) {
			throw new \RuntimeException('Uživatel s tímto uživatelským jménem už existuje.');
		}

		if ($existing === null) {
			$existing = new AdminUser;
		}

		$existing->username = $normalizedUsername;
		$existing->name = $normalizedName;
		$existing->role = $normalizedRole;
		$existing->enabled = $enabled;

		$existing->passwordHash = $this->passwords->hash($password);
		$existing->failedCount = 0;
		$existing->blockedUntil = null;
		$existing->lastAttemptAt = null;
		$existing->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($existing);
		return $existing;
	}

	public function updateRole(AdminUser $user, string $role): void
	{
		$user->role = $this->normalizeRole($role);
		$user->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($user);
	}

	public function updateIdentity(AdminUser $user, string $username, string $name): void
	{
		$normalizedUsername = self::normalizeUsername($username);
		$normalizedName = trim($name);

		if ($normalizedName === '') {
			throw new \RuntimeException('Jméno je povinné.');
		}

		$existing = $this->orm->adminUsers->getByUsername($normalizedUsername);
		if ($existing !== null && $existing->id !== $user->id) {
			throw new \RuntimeException('Uživatel s tímto uživatelským jménem už existuje.');
		}

		if ($user->username === $normalizedUsername && $user->name === $normalizedName) {
			return;
		}

		$user->username = $normalizedUsername;
		$user->name = $normalizedName;
		$user->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($user);
	}

	public function setEnabled(AdminUser $user, bool $enabled): void
	{
		$user->enabled = $enabled;
		$user->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$this->orm->persistAndFlush($user);
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
			throw new \RuntimeException('Heslo musí mít alespoň 10 znaků.');
		}
	}

	public static function normalizeUsername(string $username): string
	{
		return mb_strtolower(trim($username));
	}

	/**
	 * @return list<string>
	 */
	public function getAvailableRoles(): array
	{
		return $this->availableRoles;
	}

	public function getDefaultRole(): string
	{
		return $this->defaultRole;
	}

	private function normalizeRole(string $role): string
	{
		$normalizedRole = mb_strtolower(trim($role));
		if (!in_array($normalizedRole, $this->availableRoles, true)) {
			throw new \RuntimeException(sprintf('Unsupported admin role "%s".', $normalizedRole));
		}

		return $normalizedRole;
	}
}
