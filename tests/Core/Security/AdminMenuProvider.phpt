<?php declare(strict_types=1);

use App\AdminCore\Presentation\Accessory\AdminMenuProvider;
use Nette\Security\Authorizator;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class TestAdminMenuUserStorage implements UserStorage
{
	private bool $authenticated = false;
	private ?Nette\Security\IIdentity $identity = null;
	private ?int $reason = null;

	public function saveAuthentication(Nette\Security\IIdentity $identity): void
	{
		$this->authenticated = true;
		$this->identity = $identity;
		$this->reason = null;
	}


	public function clearAuthentication(bool $clearIdentity): void
	{
		$this->authenticated = false;
		$this->reason = User::LogoutManual;
		if ($clearIdentity) {
			$this->identity = null;
		}
	}


	public function getState(): array
	{
		return [$this->authenticated, $this->identity, $this->reason];
	}


	public function setExpiration(?string $expire, bool $clearIdentity): void
	{
	}
}

final class TestAdminMenuAuthorizator implements Authorizator
{
	/** @param list<string> $allowed */
	public function __construct(
		private readonly array $allowed,
	)
	{
	}


	public function isAllowed(?string $role, ?string $resource, ?string $privilege): bool
	{
		return in_array($resource . ':' . $privilege, $this->allowed, true);
	}
}

function createAdminMenuProvider(array $allowed): AdminMenuProvider
{
	$user = new User(
		new TestAdminMenuUserStorage,
		authorizator: new TestAdminMenuAuthorizator($allowed),
	);
	$user->login(new SimpleIdentity(1, ['role']));

	return new AdminMenuProvider('Test Admin', [
		[
			'name' => 'Visible',
			'link' => ':Visible:Page:default',
			'permission' => ['resource' => 'Visible:Page', 'privilege' => 'default'],
		],
		[
			'name' => 'Group',
			'items' => [
				[
					'name' => 'Allowed child',
					'link' => ':Allowed:Child:default',
					'permission' => ['resource' => 'Allowed:Child', 'privilege' => 'default'],
				],
				[
					'name' => 'Denied child',
					'link' => ':Denied:Child:default',
					'permission' => ['resource' => 'Denied:Child', 'privilege' => 'default'],
				],
			],
		],
		[
			'name' => 'Parent link',
			'link' => ':Denied:Parent:default',
			'permission' => ['resource' => 'Denied:Parent', 'privilege' => 'default'],
			'items' => [
				[
					'name' => 'Inferred child',
					'link' => ':Inferred:Child:default',
				],
			],
		],
	], $user);
}

test('getItems hides denied leaves and empty groups', function (): void {
	$items = createAdminMenuProvider([
		'Visible:Page:default',
	])->getItems();

	Assert::count(1, $items);
	Assert::same('Visible', $items[0]['name']);
});


test('getItems keeps allowed children and removes denied parent destination', function (): void {
	$items = createAdminMenuProvider([
		'Visible:Page:default',
		'Allowed:Child:default',
		'Inferred:Child:default',
	])->getItems();

	Assert::same('Visible', $items[0]['name']);
	Assert::same('Group', $items[1]['name']);
	Assert::count(1, $items[1]['items']);
	Assert::same('Allowed child', $items[1]['items'][0]['name']);
	Assert::same('Parent link', $items[2]['name']);
	Assert::hasNotKey('destination', $items[2]);
	Assert::count(1, $items[2]['items']);
	Assert::same('Inferred child', $items[2]['items'][0]['name']);
	Assert::count(3, $items);
});


test('resolvePermissionForPresenterAction uses explicit and inferred permissions', function (): void {
	$provider = createAdminMenuProvider([
		'Visible:Page:default',
		'Allowed:Child:default',
		'Inferred:Child:default',
	]);

	Assert::same(
		['resource' => 'Denied:Parent', 'privilege' => 'default'],
		$provider->resolvePermissionForPresenterAction('Denied:Parent', 'default'),
	);
	Assert::same(
		['resource' => 'Inferred:Child', 'privilege' => 'default'],
		$provider->resolvePermissionForPresenterAction('Inferred:Child', 'default'),
	);
	Assert::null($provider->resolvePermissionForPresenterAction('Missing:Page', 'default'));
});
