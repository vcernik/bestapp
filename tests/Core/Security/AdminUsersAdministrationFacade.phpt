<?php declare(strict_types=1);

use App\AdminCore\Security\AdminUserManager;
use App\AdminCore\Security\AdminUsersAdministrationFacade;
use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/_helpers.php';

$facade = testContainer()->getByType(AdminUsersAdministrationFacade::class);
$manager = testContainer()->getByType(AdminUserManager::class);
$roles = $manager->getAvailableRoles();
$primaryRole = $manager->getDefaultRole();
$secondaryRole = null;
foreach ($roles as $role) {
	if ($role !== $primaryRole) {
		$secondaryRole = $role;
		break;
	}
}

if ($secondaryRole === null) {
	Environment::skip('AdminUsersAdministrationFacade role-change tests require at least two configured admin roles.');
}

test('createUser stores selected role, enabled flag and audit log', function () use ($facade, $manager, $secondaryRole): void {
	$actor = createTestAdminUser();
	$created = null;

	try {
		$created = $facade->createUser(
			$actor->id,
			generateTestUsername(),
			'Created User',
			$secondaryRole,
			false,
			'veryStrongPassword123',
		);

		Assert::same($secondaryRole, $created->role);
		Assert::false($created->enabled);
		Assert::true($manager->verifyPassword($created, 'veryStrongPassword123'));

		$logs = findLogsByAction('admin.user.created');
		Assert::true(count($logs) >= 1);
		Assert::same($actor->id, $logs[0]->userId);
		$payload = json_decode($logs[0]->payloadJson, true, 512, JSON_THROW_ON_ERROR);
		Assert::same($created->id, $payload['createdUserId']);
		Assert::same($secondaryRole, $payload['role']);
		Assert::false($payload['enabled']);
	} finally {
		if ($created !== null) {
			cleanupAdminUser($created);
		}
		cleanupAdminUser($actor);
	}
});


test('updateUser logs role, enabled and password changes', function () use ($facade, $manager, $primaryRole, $secondaryRole): void {
	$actor = createTestAdminUser();
	$edited = createTestAdminUser(role: $secondaryRole);

	try {
		$facade->updateUser(
			$actor->id,
			$edited->id,
			$edited->username,
			$edited->name,
			$primaryRole,
			false,
			'newStrongPassword123',
		);

		$reloaded = testAdminOrm()->adminUsers->getById($edited->id);
		Assert::notNull($reloaded);
		Assert::same($primaryRole, $reloaded->role);
		Assert::false($reloaded->enabled);
		Assert::true($manager->verifyPassword($reloaded, 'newStrongPassword123'));

		Assert::true(count(findLogsByAction('admin.user.role.changed')) >= 1);
		Assert::true(count(findLogsByAction('admin.user.enabled.changed')) >= 1);
		Assert::true(count(findLogsByAction('admin.user.password.changed')) >= 1);
	} finally {
		cleanupAdminUser($edited);
		cleanupAdminUser($actor);
	}
});


test('updateUser prevents disabling own account', function () use ($facade): void {
	$actor = createTestAdminUser();

	try {
		Assert::exception(
			static fn() => $facade->updateUser($actor->id, $actor->id, $actor->username, $actor->name, $actor->role, false, null),
			RuntimeException::class,
			'Nelze zakázat vlastní účet.',
		);
	} finally {
		cleanupAdminUser($actor);
	}
});


test('updateUser prevents removing last enabled primary role user', function () use ($facade, $primaryRole, $secondaryRole): void {
	$actor = createTestAdminUser(role: $secondaryRole);
	$primaryUser = createTestAdminUser(role: $primaryRole);
	$otherPrimaryUser = createTestAdminUser(role: $primaryRole, enabled: false);
	$disabledUsers = [];

	try {
		foreach (testAdminOrm()->adminUsers->findBy(['role' => $primaryRole, 'enabled' => true]) as $activePrimaryUser) {
			if ($activePrimaryUser->id === $primaryUser->id) {
				continue;
			}

			$activePrimaryUser->enabled = false;
			testAdminOrm()->persist($activePrimaryUser);
			$disabledUsers[] = $activePrimaryUser;
		}
		testAdminOrm()->flush();

		Assert::exception(
			static fn() => $facade->updateUser(
				$actor->id,
				$primaryUser->id,
				$primaryUser->username,
				$primaryUser->name,
				$secondaryRole,
				true,
				null,
			),
			RuntimeException::class,
		);
	} finally {
		foreach ($disabledUsers as $disabledUser) {
			$disabledUser->enabled = true;
			testAdminOrm()->persist($disabledUser);
		}
		testAdminOrm()->flush();

		cleanupAdminUser($otherPrimaryUser);
		cleanupAdminUser($primaryUser);
		cleanupAdminUser($actor);
	}
});


test('updateUser prevents actor from removing own primary role', function () use ($facade, $primaryRole, $secondaryRole): void {
	$actor = createTestAdminUser(role: $primaryRole);
	$otherPrimaryUser = createTestAdminUser(role: $primaryRole);

	try {
		Assert::exception(
			static fn() => $facade->updateUser($actor->id, $actor->id, $actor->username, $actor->name, $secondaryRole, true, null),
			RuntimeException::class,
		);
	} finally {
		cleanupAdminUser($otherPrimaryUser);
		cleanupAdminUser($actor);
	}
});
