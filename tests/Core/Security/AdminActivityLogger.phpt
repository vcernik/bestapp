<?php declare(strict_types=1);

use App\Core\Security\AdminActivityLogger;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';
require __DIR__ . '/_helpers.php';

$logger = testContainer()->getByType(AdminActivityLogger::class);

test('log stores user id and action', function () use ($logger): void {
	$user = createTestAdminUser();
	$action = 'test.audit.' . bin2hex(random_bytes(4));

	try {
		$logger->log($user->id, $action, ['status' => 'ok']);
		$logs = findLogsByAction($action);
		Assert::true(count($logs) >= 1);
		$latest = $logs[0];
		Assert::same($user->id, $latest->userId);
		Assert::same($action, $latest->action);
	} finally {
		cleanupLogsByAction($action);
		cleanupAdminUser($user);
	}
});


test('log serializes payload as valid json', function () use ($logger): void {
	$action = 'test.payload.' . bin2hex(random_bytes(4));

	try {
		$payload = [
			'key' => 'value',
			'count' => 2,
			'nested' => ['allowed' => 1],
		];
		$logger->log(null, $action, $payload);

		$logs = findLogsByAction($action);
		Assert::true(count($logs) >= 1);
		$latest = $logs[0];
		$decoded = json_decode($latest->payloadJson, true, 512, JSON_THROW_ON_ERROR);
		Assert::same($payload, $decoded);
	} finally {
		cleanupLogsByAction($action);
	}
});
