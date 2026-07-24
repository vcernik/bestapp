<?php declare(strict_types=1);

namespace App\AdminCore\Model;

use Nextras\Orm\Entity\Entity;
use Nextras\Orm\Repository\Repository;

/**
 * Sdílený trait pro centralizovanou logiku sortování v repositories.
 * Entita MUSÍ implementovat rozhraní SortableEntity.
 *
 * @template T of Entity&SortableEntity
 * @phpstan-require-extends Repository<T>
 *
 * Požadavky pro repository:
 * - Musí mít metodu persistAndFlush() pro uložení entit
 * - Musí mít metodu removeAndFlush() pro smazání entity
 * - Entita musí implementovat SortableEntity rozhraní
 */
trait SortableRepositoryTrait
{
	/**
	 * Přeřadí položku podle prev/next ID (ublaboo datagrid drag-and-drop event).
	 *
	 * @param list<T> $orderedItems seznam všech seřazených položek
	 */
	protected function performReorderByIds(int $itemId, ?int $prevId, ?int $nextId, array $orderedItems): bool
	{
		// Najdi položku dle ID
		$item = null;
		foreach ($orderedItems as $entity) {
			if ($entity->getId() === $itemId) {
				$item = $entity;
				break;
			}
		}

		if ($item === null) {
			return false;
		}

		// Vytvoř seznam bez aktuální položky
		$withoutCurrent = array_values(
			array_filter($orderedItems, static fn(Entity $e) => $e->getId() !== $itemId)
		);

		// Urči cílový index (defaultně na konec)
		$targetIndex = count($withoutCurrent);

		if ($prevId !== null) {
			// Umístit za položku s ID $prevId
			foreach ($withoutCurrent as $index => $entity) {
				if ($entity->getId() === $prevId) {
					$targetIndex = $index + 1;
					break;
				}
			}
		} elseif ($nextId !== null) {
			// Umístit před položku s ID $nextId
			foreach ($withoutCurrent as $index => $entity) {
				if ($entity->getId() === $nextId) {
					$targetIndex = $index;
					break;
				}
			}
		}

		// Vlož položku na cílový index
		array_splice($withoutCurrent, $targetIndex, 0, [$item]);

		// Přečísluji a uložím
		$this->setOrderAndFlush($withoutCurrent);

		return true;
	}

	/**
	 * Přepočítá sortOrder pro všechny položky a uloží.
	 *
	 * @param list<T> $orderedItems
	 */
	protected function setOrderAndFlush(array $orderedItems): void
	{
		foreach ($orderedItems as $index => $item) {
			$item->setSortOrder($index + 1);
			$this->persistAndFlush($item);
		}
	}

	/**
	 * Smaže položku a znovu setřídí sortOrder zbytku.
	 *
	 * @param T $item
	 * @param list<T> $allItems seznam všech položek v aktuálním pořadí
	 */
	protected function removeAndResequenceAll(Entity $item, array $allItems): void
	{
		$remainingItems = array_values(
			array_filter($allItems, static fn (Entity $entity): bool => $entity->getId() !== $item->getId())
		);

		$this->removeAndFlush($item);
		$this->setOrderAndFlush($remainingItems);
	}
}
