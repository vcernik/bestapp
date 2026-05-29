<?php declare(strict_types=1);

use App\Core\Security\AdminSessionSecurityService;
use App\Model\Orm\AdminUser\AdminUser;
use Nette\Bridges\SecurityHttp\SessionStorage;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/_helpers.php';

$service = testContainer()->getByType(AdminSessionSecurityService::class);
$user = testContainer()->getByType(User::class);

function prepareAdminNamespace(User $user): void
{
	$storage = $user->getStorage();
	if ($storage instanceof SessionStorage) {
		$storage->setNamespace('admin');
	}
}

function loginAsAdminIdentity(User $user, AdminUser $adminUser, ?int $updatedAt = null): void
{
	prepareAdminNamespace($user);
	$user->logout(true);

	$user->login(new SimpleIdentity($adminUser->id, ['admin'], [
		'username' => $adminUser->username,
		'name' => $adminUser->name,
		'updatedAt' => $updatedAt ?? $adminUser->updatedAt->getTimestamp(),
	]));
}

test('validateAndRefresh logs out when admin row no longer exists', function () use ($service, $user): void {
	$admin = createTestAdminUser(password: 'correct-password-12345');

	try {
		loginAsAdminIdentity($user, $admin);
		cleanupAdminUser($admin);

		Assert::false($service->validateAndRefresh($user));
		Assert::false($user->isLoggedIn());
	} finally {
		$user->logout(true);
	}
});


test('validateAndRefresh logs out when admin is disabled', function () use ($service, $user): void {
	$admin = createTestAdminUser(password: 'correct-password-12345');

	try {
		$managed = testOrm()->adminUsers->getById($admin->id);
		Assert::notNull($managed);
		$managed->enabled = false;
		testOrm()->persistAndFlush($managed);

		loginAsAdminIdentity($user, $managed);
		Assert::false($service->validateAndRefresh($user));
		Assert::false($user->isLoggedIn());
	} finally {
		cleanupAdminUser($admin);
		$user->logout(true);
	}
});


test('validateAndRefresh keeps session when updatedAt matches', function () use ($service, $user): void {
	$admin = createTestAdminUser(password: 'correct-password-12345');

	try {
		$managed = testOrm()->adminUsers->getById($admin->id);
		Assert::notNull($managed);
		loginAsAdminIdentity($user, $managed);

		Assert::true($service->validateAndRefresh($user));
		Assert::true($user->isLoggedIn());

		$identity = $user->getIdentity();
		Assert::notNull($identity);
		Assert::same($managed->name, $identity->getData()['name']);
		Assert::same($managed->updatedAt->getTimestamp(), $identity->getData()['updatedAt']);
	} finally {
		cleanupAdminUser($admin);
		$user->logout(true);
	}
});


test('validateAndRefresh refreshes identity when updatedAt changed', function () use ($service, $user): void {
	$admin = createTestAdminUser(password: 'correct-password-12345');

	try {
		$managed = testOrm()->adminUsers->getById($admin->id);
		Assert::notNull($managed);
		$oldTimestamp = $managed->updatedAt->getTimestamp();
		loginAsAdminIdentity($user, $managed, $oldTimestamp - 120);

		$managed->name = 'Updated Session Name';
		$managed->updatedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+5 minutes');
		testOrm()->persistAndFlush($managed);

		Assert::true($service->validateAndRefresh($user));
		Assert::true($user->isLoggedIn());

		$identity = $user->getIdentity();
		Assert::notNull($identity);
		Assert::same('Updated Session Name', $identity->getData()['name']);
		Assert::same($managed->updatedAt->getTimestamp(), $identity->getData()['updatedAt']);
	} finally {
		cleanupAdminUser($admin);
		$user->logout(true);
	}
});
