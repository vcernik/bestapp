<?php declare(strict_types=1);

namespace App\AdminCore\Model;

use Nextras\Orm\Entity\Entity;

/**
 * Rozhraní pro entity, které podporují sortování.
 * Entity, které chceme řadit, musí implementovat toto rozhraní.
 */
interface SortableEntity
{
	public function getId(): int|string;

	public function getSortOrder(): int;

	public function setSortOrder(int $sortOrder): static;
}
