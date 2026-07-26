<?php declare(strict_types=1);

use App\AdminCore\Security\AdminAuthorizator;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$authorizator = new AdminAuthorizator(
	['all', 'limited', 'viewer'],
	['Resource:Dashboard', 'Resource:Records'],
	[
		'all' => [
			['resource' => '*', 'privileges' => ['*']],
		],
		'limited' => [
			['resource' => 'Resource:Records', 'privileges' => ['read', 'update']],
			['resource' => 'Resource:Dynamic', 'privileges' => ['read']],
		],
		'viewer' => [
			['resource' => 'Resource:Dashboard', 'privileges' => ['read']],
		],
		'ignored' => [
			['resource' => 'Resource:Dashboard', 'privileges' => ['read']],
		],
	],
);

test('wildcard role allows configured resources', function () use ($authorizator): void {
	Assert::true($authorizator->isAllowed('all', 'Resource:Dashboard', 'read'));
	Assert::true($authorizator->isAllowed('all', 'Resource:Records', 'delete'));
	Assert::false($authorizator->isAllowed('all', 'Resource:Missing', 'read'));
});


test('privileges are limited per resource', function () use ($authorizator): void {
	Assert::true($authorizator->isAllowed('limited', 'Resource:Records', 'read'));
	Assert::true($authorizator->isAllowed('limited', 'Resource:Records', 'update'));
	Assert::false($authorizator->isAllowed('limited', 'Resource:Records', 'delete'));
	Assert::false($authorizator->isAllowed('viewer', 'Resource:Records', 'read'));
});


test('rules may add resources outside the initial resource list', function () use ($authorizator): void {
	Assert::true($authorizator->isAllowed('limited', 'Resource:Dynamic', 'read'));
});


test('unknown role or resource is denied', function () use ($authorizator): void {
	Assert::false($authorizator->isAllowed(null, 'Resource:Dashboard', 'read'));
	Assert::false($authorizator->isAllowed('', 'Resource:Dashboard', 'read'));
	Assert::false($authorizator->isAllowed('ignored', 'Resource:Dashboard', 'read'));
	Assert::false($authorizator->isAllowed('limited', 'Resource:Missing', 'read'));
});
