<?php declare(strict_types=1);

namespace App\Core\Security;

use App\Model\Orm\Orm;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

final class AdminAuthenticator implements Authenticator
{
	private const int MAX_FAILED_ATTEMPTS = 5;
	private const int BLOCK_MINUTES = 10;

	public function __construct(
		private readonly Orm $orm,
		private readonly AdminUserManager $adminUserManager,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
	}

	public function authenticate(string $username, string $password): IIdentity
	{
		$normalizedUsername = AdminUserManager::normalizeUsername($username);
		$user = $this->orm->adminUsers->getByUsername($normalizedUsername);
		$now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

		if ($user === null) {
			$this->adminActivityLogger->log(null, 'auth.login.failed', ['username' => $normalizedUsername]);
			throw new AuthenticationException('Neplatné přihlašovací údaje.', self::InvalidCredential);
		}

		if ($user->blockedUntil !== null && $user->blockedUntil > $now) {
			$this->adminActivityLogger->log($user->id, 'auth.login.blocked', ['blockedUntil' => $user->blockedUntil->format(DATE_ATOM)]);
			throw new AuthenticationException('Účet je dočasně zablokován. Zkuste to později.', self::NotApproved);
		}

		if (!$this->adminUserManager->verifyPassword($user, $password)) {
			$user->failedCount++;
			$user->lastAttemptAt = $now;
			if ($user->failedCount >= self::MAX_FAILED_ATTEMPTS) {
				$user->blockedUntil = $now->modify('+' . self::BLOCK_MINUTES . ' minutes');
			}
			$this->orm->persistAndFlush($user);
			$this->adminActivityLogger->log($user->id, 'auth.login.failed', ['failedCount' => $user->failedCount]);
			throw new AuthenticationException('Neplatné přihlašovací údaje.', self::InvalidCredential);
		}

		$user->failedCount = 0;
		$user->blockedUntil = null;
		$user->lastAttemptAt = $now;
		$this->orm->persistAndFlush($user);
		$this->adminActivityLogger->log($user->id, 'auth.login.success', []);

		return new SimpleIdentity($user->id, ['admin'], [
			'username' => $user->username,
			'name' => $user->name,
		]);
	}
}
