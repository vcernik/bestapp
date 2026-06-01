<?php declare(strict_types=1);

use App\Admin\Model\Orm\AdminActivityLog\AdminActivityLog;
use App\Admin\Model\Orm\AdminUser\AdminUser;
use App\Admin\Security\AdminUserManager;
use Nextras\Orm\Collection\ICollection;

function generateTestUsername(): string
{
	return 'test-admin-' . bin2hex(random_bytes(6));
}

function createTestAdminUser(
	?string $username = null,
	string $password = 'veryStrongPassword123',
	string $name = 'Test Admin',
	bool $enabled = true,
): AdminUser
{
	$manager = testContainer()->getByType(AdminUserManager::class);
	$user = $manager->createUser($username ?? generateTestUsername(), $name, $password, true);
	if (!$enabled) {
		$user->enabled = false;
		testAdminOrm()->persistAndFlush($user);
	}

	return $user;
}

/**
 * @return list<AdminActivityLog>
 */
function findLogsByAction(string $action): array
{
	$collection = testAdminOrm()->adminActivityLogs->findBy(['action' => $action])
		->orderBy('id', ICollection::DESC);

	$result = [];
	foreach ($collection as $item) {
		$result[] = $item;
	}

	return $result;
}

function cleanupAdminUser(AdminUser $user): void
{
	$orm = testAdminOrm();
	foreach ($orm->adminActivityLogs->findBy(['userId' => $user->id]) as $log) {
		$orm->remove($log);
	}

	$managedUser = $orm->adminUsers->getById($user->id);
	if ($managedUser !== null) {
		$orm->remove($managedUser);
	}

	$orm->flush();
}

function cleanupLogsByAction(string $action): void
{
	$orm = testAdminOrm();
	foreach ($orm->adminActivityLogs->findBy(['action' => $action]) as $log) {
		$orm->remove($log);
	}
	$orm->flush();
}
