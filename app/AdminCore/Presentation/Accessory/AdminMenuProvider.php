<?php declare(strict_types=1);

namespace App\AdminCore\Presentation\Accessory;

use InvalidArgumentException;

/**
 * @phpstan-type AdminMenuChild array{name: string, destination?: string, params?: array<string, scalar|null>, items?: list<mixed>, collapsible?: bool}
 * @phpstan-type AdminMenuItem array{name: string, destination?: string, params?: array<string, scalar|null>, items?: list<AdminMenuChild>, collapsible?: bool}
 */
final class AdminMenuProvider
{
	/** @var list<AdminMenuItem> */
	private array $items;

	private readonly string $appName;

	/**
	 * @param array<int, array{name?: mixed, link?: mixed, items?: mixed, collapsible?: mixed}> $items
	 */
	public function __construct(string $appName, array $items)
	{
		$this->appName = $appName;
		$this->items = array_values(array_map(
			fn (array $item): array => $this->normalizeItem($item),
			$items,
		));
	}

	/** @return list<AdminMenuItem> */
	public function getItems(): array
	{
		return $this->items;
	}

	public function getAppName(): string
	{
		return $this->appName;
	}

	/**
	 * @param array{name?: mixed, link?: mixed, items?: mixed, collapsible?: mixed} $item
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
}
