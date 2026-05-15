<?php declare(strict_types=1);

namespace App\Core\Security;

use App\Model\Orm\AdminPasswordReset\AdminPasswordReset;
use App\Model\Orm\Orm;

final class AdminPasswordResetFacade
{
	private const int TOKEN_VALIDITY_MINUTES = 60;

	public function __construct(
		private readonly Orm $orm,
		private readonly AdminUserManager $adminUserManager,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
	}

	public function createResetTokenForUsername(string $username): ?string
	{
		$normalizedUsername = AdminUserManager::normalizeUsername($username);
		$user = $this->orm->adminUsers->getByUsername($normalizedUsername);

		$this->adminActivityLogger->log($user?->id, 'auth.password_reset.request', ['username' => $normalizedUsername]);
		if ($user === null) {
			return null;
		}

		$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
		foreach ($this->orm->adminPasswordResets->findActiveByUserId($user->id) as $activeToken) {
			$activeToken->usedAt = $now;
			$this->orm->persist($activeToken);
		}

		$token = bin2hex(random_bytes(32));
		$reset = new AdminPasswordReset;
		$reset->userId = $user->id;
		$reset->tokenHash = hash('sha256', $token);
		$reset->expiresAt = $now->modify('+' . self::TOKEN_VALIDITY_MINUTES . ' minutes');
		$reset->usedAt = null;
		$reset->createdAt = $now;

		$this->orm->persistAndFlush($reset);
		return $token;
	}

	public function resetPassword(string $token, string $newPassword): bool
	{
		$resetEntity = $this->getValidTokenEntity($token);
		if ($resetEntity === null) {
			return false;
		}

		$user = $this->orm->adminUsers->getById($resetEntity->userId);
		if ($user === null) {
			return false;
		}

		$this->adminUserManager->updatePassword($user, $newPassword);
		$resetEntity->usedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
		$this->orm->persistAndFlush($resetEntity);
		$this->adminActivityLogger->log($user->id, 'auth.password_reset.success', []);

		return true;
	}

	public function isResetTokenValid(string $token): bool
	{
		return $this->getValidTokenEntity($token) !== null;
	}

	private function getValidTokenEntity(string $token): ?AdminPasswordReset
	{
		$tokenHash = hash('sha256', $token);
		$resetEntity = $this->orm->adminPasswordResets->getBy(['tokenHash' => $tokenHash, 'usedAt' => null]);
		if ($resetEntity === null) {
			return null;
		}

		$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
		if ($resetEntity->expiresAt < $now) {
			return null;
		}

		return $resetEntity;
	}
}
