<?php declare(strict_types=1);

use App\AdminCore\Security\AdminUserManager;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/_helpers.php';

$manager = testContainer()->getByType(AdminUserManager::class);

test('assertStrongPassword throws for weak password', function () use ($manager): void {
	Assert::exception(
		static fn() => $manager->assertStrongPassword('short123'),
		RuntimeException::class,
		'Heslo musí mít alespoň 10 znaků.',
	);
});


test('normalizeUsername trims and lowercases username', function (): void {
	Assert::same('admin.user', AdminUserManager::normalizeUsername('  ADMIN.User  '));
});


test('createUser throws on duplicate username when force is false', function () use ($manager): void {
	$user = createTestAdminUser();

	try {
		Assert::exception(
			static fn() => $manager->createUser($user->username, 'Second User', 'AnotherStrongPassword123', false),
			RuntimeException::class,
			'Uživatel s tímto uživatelským jménem už existuje.',
		);
	} finally {
		cleanupAdminUser($user);
	}
});


test('createUser stores normalized values and resets security fields', function () use ($manager): void {
	$usernameInput = '  TESTER.USER  ';
	$user = $manager->createUser($usernameInput, '  Tester Name  ', 'veryStrongPassword123', true);

	try {
		Assert::same('tester.user', $user->username);
		Assert::same('Tester Name', $user->name);
		Assert::same('superadmin', $user->role);
		Assert::same(0, $user->failedCount);
		Assert::null($user->blockedUntil);
		Assert::null($user->lastAttemptAt);
		Assert::true($manager->verifyPassword($user, 'veryStrongPassword123'));
	} finally {
		cleanupAdminUser($user);
	}
});


test('createUser throws for unsupported role', function () use ($manager): void {
	Assert::exception(
		static fn() => $manager->createUser(generateTestUsername(), 'Role Test', 'veryStrongPassword123', true, 'unknown-role'),
		RuntimeException::class,
		'Unsupported admin role "unknown-role".',
	);
});
