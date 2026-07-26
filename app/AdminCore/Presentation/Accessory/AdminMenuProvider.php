<?php declare(strict_types=1);

namespace App\AdminCore\Presentation\Accessory;

use InvalidArgumentException;
use Nette\Security\User;

/**
 * @phpstan-type AdminPermission array{resource: string, privilege: string}
 * @phpstan-type AdminMenuChild array{name: string, destination?: string, params?: array<string, scalar|null>, permission?: AdminPermission, items?: list<mixed>, collapsible?: bool}
 * @phpstan-type AdminMenuItem array{name: string, destination?: string, params?: array<string, scalar|null>, permission?: AdminPermission, items?: list<AdminMenuChild>, collapsible?: bool}
 */
final class AdminMenuProvider
{
	/** @var list<AdminMenuItem> */
	private array $items;

	/** @var array<string, AdminPermission> */
	private array $permissionByDestination = [];

	private readonly string $appName;

	/**
	 * @param array<int, array{name?: mixed, link?: mixed, permission?: mixed, items?: mixed, collapsible?: mixed}> $items
	 */
	public function __construct(string $appName, array $items, private readonly User $user)
	{
		$this->appName = $appName;
		$this->items = array_values(array_map(
			fn (array $item): array => $this->normalizeItem($item),
			$items,
		));
		$this->permissionByDestination = $this->buildPermissionMap($this->items);
	}

	/** @return list<AdminMenuItem> */
	public function getItems(): array
	{
		return $this->filterItems($this->items);
	}

	public function getAppName(): string
	{
		return $this->appName;
	}

	/**
	 * @return AdminPermission|null
	 */
	public function resolvePermissionForPresenterAction(string $presenterName, string $action): ?array
	{
		$key = $this->normalizeDestinationKey(':' . $presenterName . ':' . $action);
		return $this->permissionByDestination[$key] ?? null;
	}

	/**
	 * @param array{name?: mixed, link?: mixed, permission?: mixed, items?: mixed, collapsible?: mixed} $item
	 * @return AdminMenuItem
	 */
	private function normalizeItem(array $item): array
	{
		$name = $item['name'] ?? null;

		if (!is_string($name) || $name === '') {
			throw new InvalidArgumentException('Admin menu item must define a non-empty "name".');
		}

		$result = ['name' => $name];

		if (array_key_exists('link', $item)) {
			[$destination, $params] = $this->normalizeLink($name, $item['link']);
			$result['destination'] = $destination;
			$result['params'] = $params;
			$inferredPermission = $this->permissionFromDestination($destination);
			if ($inferredPermission !== null) {
				$result['permission'] = $inferredPermission;
			}
		}

		if (array_key_exists('permission', $item)) {
			$result['permission'] = $this->normalizePermission($name, $item['permission']);
		}

		if (array_key_exists('items', $item)) {
			$items = $item['items'];

			if (!is_array($items)) {
				throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define "items" as array.', $name));
			}

			$result['items'] = array_map(
				fn (mixed $child, int $index): array => $this->normalizeChildItem($name, $child, $index),
				$items,
				array_keys($items),
			);
		}

		if (array_key_exists('collapsible', $item)) {
			if (!is_bool($item['collapsible'])) {
				throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define "collapsible" as bool.', $name));
			}

			$result['collapsible'] = $item['collapsible'];
		}

		if (!isset($result['destination']) && !isset($result['items'])) {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define "link" or "items".', $name));
		}

		return $result;
	}

	/**
	 * @param mixed $permission
	 * @return AdminPermission
	 */
	private function normalizePermission(string $name, mixed $permission): array
	{
		if (!is_array($permission)) {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define "permission" as array.', $name));
		}

		$resource = $permission['resource'] ?? null;
		$privilege = $permission['privilege'] ?? null;

		if (!is_string($resource) || trim($resource) === '') {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" has invalid permission.resource.', $name));
		}

		if (!is_string($privilege) || trim($privilege) === '') {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" has invalid permission.privilege.', $name));
		}

		return [
			'resource' => trim($resource),
			'privilege' => trim($privilege),
		];
	}

	/**
	 * @param mixed $link
	 * @return array{0: string, 1: array<string, scalar|null>}
	 */
	private function normalizeLink(string $name, mixed $link): array
	{
		if (is_string($link) && $link !== '') {
			return [$link, []];
		}

		if (!is_array($link)) {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define "link" as string or array.', $name));
		}

		$destination = $link['destination'] ?? null;
		$params = $link['params'] ?? [];

		if (!is_string($destination) || $destination === '') {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define non-empty link.destination.', $name));
		}

		if (!is_array($params)) {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" must define link.params as array.', $name));
		}

		foreach ($params as $paramName => $value) {
			if (!is_string($paramName)) {
				throw new InvalidArgumentException(sprintf('Admin menu item "%s" contains invalid link param name.', $name));
			}

			if (!is_scalar($value) && $value !== null) {
				throw new InvalidArgumentException(sprintf('Admin menu item "%s" contains unsupported value for param "%s".', $name, $paramName));
			}
		}

		return [$destination, $params];
	}

	/**
	 * @param mixed $child
	 * @return AdminMenuChild
	 */
	private function normalizeChildItem(string $parentName, mixed $child, int $index): array
	{
		if (!is_array($child)) {
			throw new InvalidArgumentException(sprintf('Admin menu item "%s" contains invalid child at index %d.', $parentName, $index));
		}

		return $this->normalizeItem($child);
	}

	/**
	 * @param list<AdminMenuItem> $items
	 * @return list<AdminMenuItem>
	 */
	private function filterItems(array $items): array
	{
		$filtered = [];
		foreach ($items as $item) {
			$children = $item['items'] ?? null;
			if (is_array($children)) {
				$item['items'] = $this->filterItems($children);
			}

			$hasChildren = isset($item['items']) && $item['items'] !== [];
			$hasDestination = isset($item['destination']);

			if ($hasDestination && isset($item['permission']) && !$this->isAllowed($item['permission'])) {
				if (!$hasChildren) {
					continue;
				}
				unset($item['destination'], $item['params']);
			}

			if (!$hasDestination && !$hasChildren) {
				continue;
			}

			$filtered[] = $item;
		}

		return $filtered;
	}

	/**
	 * @param list<AdminMenuItem> $items
	 * @return array<string, AdminPermission>
	 */
	private function buildPermissionMap(array $items): array
	{
		$map = [];
		foreach ($items as $item) {
			if (isset($item['destination'], $item['permission'])) {
				$map[$this->normalizeDestinationKey($item['destination'])] = $item['permission'];
			}

			if (isset($item['items'])) {
				$map += $this->buildPermissionMap($item['items']);
			}
		}

		return $map;
	}

	/**
	 * @return AdminPermission|null
	 */
	private function permissionFromDestination(string $destination): ?array
	{
		$normalized = ltrim(trim($destination), ':');
		if ($normalized === '') {
			return null;
		}

		$parts = explode(':', $normalized);
		if (count($parts) < 2) {
			return null;
		}

		$privilege = array_pop($parts);
		if ($privilege === '') {
			return null;
		}

		$resource = implode(':', $parts);
		if ($resource === '') {
			return null;
		}

		return [
			'resource' => $resource,
			'privilege' => $privilege,
		];
	}

	/**
	 * @param AdminPermission $permission
	 */
	private function isAllowed(array $permission): bool
	{
		try {
			return $this->user->isAllowed($permission['resource'], $permission['privilege']);
		} catch (\Throwable) {
			return false;
		}
	}

	private function normalizeDestinationKey(string $destination): string
	{
		return mb_strtolower(trim($destination));
	}
}
