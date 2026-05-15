<?php declare(strict_types=1);

use App\Core\Security\AdminUserManager;
use App\Model\Orm\AdminActivityLog\AdminActivityLog;
use App\Model\Orm\AdminUser\AdminUser;
use Nextras\Orm\Collection\ICollection;

function generateTestUsername(): string
{
	return 'test-admin-' . bin2hex(random_bytes(6));
}

function createTestAdminUser(
	?string $username = null,
	string $password = 'veryStrongPassword123',
	string $name = 'Test Admin',
): AdminUser
{
	$manager = testContainer()->getByType(AdminUserManager::class);
	return $manager->createUser($username ?? generateTestUsername(), $name, $password, true);
}

/**
 * @return list<AdminActivityLog>
 */
function findLogsByAction(string $action): array
{
	$collection = testOrm()->adminActivityLogs->findBy(['action' => $action])
		->orderBy('id', ICollection::DESC);

	$result = [];
	foreach ($collection as $item) {
		$result[] = $item;
	}

	return $result;
}

function cleanupAdminUser(AdminUser $user): void
{
	$orm = testOrm();
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
	$orm = testOrm();
	foreach ($orm->adminActivityLogs->findBy(['action' => $action]) as $log) {
		$orm->remove($log);
	}
	$orm->flush();
}
