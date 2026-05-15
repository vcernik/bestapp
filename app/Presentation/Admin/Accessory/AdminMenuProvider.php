<?php declare(strict_types=1);

namespace App\Presentation\Admin\Accessory;

use InvalidArgumentException;

final class AdminMenuProvider
{
	/**
	 * @var list<array{name: string, destination: string, params: array<string, scalar|null>}>
	 */
	private array $items;

	private readonly string $appName;

	/**
	 * @param array<int, array{name?: mixed, link?: mixed}> $items
	 */
	public function __construct(string $appName, array $items)
	{
		$this->appName = $appName;
		$this->items = array_map(
			fn (array $item): array => $this->normalizeItem($item),
			$items,
		);
	}

	/**
	 * @return list<array{name: string, destination: string, params: array<string, scalar|null>}>
	 */
	public function getAppName(): string
	{
		return $this->appName;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @param array{name?: mixed, link?: mixed} $item
	 * @return array{name: string, destination: string, params: array<string, scalar|null>}
	 */
	private function normalizeItem(array $item): array
	{
		$name = $item['name'] ?? null;
		$link = $item['link'] ?? null;

		if (!is_string($name) || $name === '') {
			throw new InvalidArgumentException('Admin menu item must define a non-empty "name".');
		}

		if (is_string($link) && $link !== '') {
			return [
				'name' => $name,
				'destination' => $link,
				'params' => [],
			];
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

		return [
			'name' => $name,
			'destination' => $destination,
			'params' => $params,
		];
	}
}