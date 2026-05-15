<?php declare(strict_types=1);

use App\Core\Security\AdminAuthenticator;
use Nette\Security\Authenticator as AuthenticatorCodes;
use Nette\Security\AuthenticationException;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/_helpers.php';

$authenticator = testContainer()->getByType(AdminAuthenticator::class);

test('authenticate throws invalid credential for unknown username', function () use ($authenticator): void {
	$action = 'auth.login.failed';
	$beforeCount = count(findLogsByAction($action));
	$username = generateTestUsername();

	try {
		$authenticator->authenticate($username, 'wrong-password-123');
		Assert::fail('AuthenticationException was expected.');
	} catch (AuthenticationException $exception) {
		Assert::same(AuthenticatorCodes::InvalidCredential, $exception->getCode());
	}

	$afterCount = count(findLogsByAction($action));
	Assert::true($afterCount >= $beforeCount + 1);
});


test('authenticate increments failed count on wrong password', function () use ($authenticator): void {
	$user = createTestAdminUser(password: 'correct-password-12345');

	try {
		try {
			$authenticator->authenticate($user->username, 'wrong-password-12345');
			Assert::fail('AuthenticationException was expected.');
		} catch (AuthenticationException $exception) {
			Assert::same(AuthenticatorCodes::InvalidCredential, $exception->getCode());
		}

		$reloadedUser = testOrm()->adminUsers->getById($user->id);
		Assert::notNull($reloadedUser);
		Assert::same(1, $reloadedUser->failedCount);
		Assert::notNull($reloadedUser->lastAttemptAt);
	} finally {
		cleanupAdminUser($user);
	}
});


test('authenticate throws not approved for blocked account', function () use ($authenticator): void {
	$user = createTestAdminUser(password: 'correct-password-12345');

	try {
		$reloadedUser = testOrm()->adminUsers->getById($user->id);
		Assert::notNull($reloadedUser);
		$reloadedUser->blockedUntil = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+5 minutes');
		testOrm()->persistAndFlush($reloadedUser);

		try {
			$authenticator->authenticate($user->username, 'correct-password-12345');
			Assert::fail('AuthenticationException was expected.');
		} catch (AuthenticationException $exception) {
			Assert::same(AuthenticatorCodes::NotApproved, $exception->getCode());
		}
	} finally {
		cleanupAdminUser($user);
	}
});


test('authenticate succeeds and resets security counters', function () use ($authenticator): void {
	$user = createTestAdminUser(password: 'correct-password-12345');

	try {
		$reloadedUser = testOrm()->adminUsers->getById($user->id);
		Assert::notNull($reloadedUser);
		$reloadedUser->failedCount = 4;
		$reloadedUser->blockedUntil = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-1 minute');
		testOrm()->persistAndFlush($reloadedUser);

		$identity = $authenticator->authenticate($user->username, 'correct-password-12345');

		Assert::same($user->id, $identity->getId());
		Assert::same(['admin'], $identity->getRoles());
		Assert::same($user->username, $identity->getData()['username']);

		$finalUser = testOrm()->adminUsers->getById($user->id);
		Assert::notNull($finalUser);
		Assert::same(0, $finalUser->failedCount);
		Assert::null($finalUser->blockedUntil);
	} finally {
		cleanupAdminUser($user);
	}
});
