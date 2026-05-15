<?php declare(strict_types=1);

use App\Core\Security\AdminPasswordChangeFacade;
use App\Core\Security\AdminUserManager;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/_helpers.php';

$facade = testContainer()->getByType(AdminPasswordChangeFacade::class);
$manager = testContainer()->getByType(AdminUserManager::class);

test('changePassword throws when user does not exist', function () use ($facade): void {
	Assert::exception(
		static fn() => $facade->changePassword(PHP_INT_MAX, 'any-password-123', 'new-password-12345'),
		RuntimeException::class,
		'Přihlášený uživatel neexistuje.',
	);
});


test('changePassword throws for invalid current password and logs failure', function () use ($facade): void {
	$user = createTestAdminUser(password: 'old-password-12345');
	$action = 'auth.password_change.failed';
	$beforeCount = count(findLogsByAction($action));

	try {
		Assert::exception(
			static fn() => $facade->changePassword($user->id, 'wrong-current-password-12345', 'new-password-12345'),
			RuntimeException::class,
			'Aktuální heslo není správné.',
		);

		$afterCount = count(findLogsByAction($action));
		Assert::true($afterCount >= $beforeCount + 1);
	} finally {
		cleanupAdminUser($user);
	}
});


test('changePassword updates password and logs success', function () use ($facade, $manager): void {
	$user = createTestAdminUser(password: 'old-password-12345');
	$action = 'auth.password_change.success';
	$beforeCount = count(findLogsByAction($action));

	try {
		$facade->changePassword($user->id, 'old-password-12345', 'new-password-12345');

		$reloadedUser = testOrm()->adminUsers->getById($user->id);
		Assert::notNull($reloadedUser);
		Assert::true($manager->verifyPassword($reloadedUser, 'new-password-12345'));
		Assert::false($manager->verifyPassword($reloadedUser, 'old-password-12345'));

		$afterCount = count(findLogsByAction($action));
		Assert::true($afterCount >= $beforeCount + 1);
	} finally {
		cleanupAdminUser($user);
	}
});
