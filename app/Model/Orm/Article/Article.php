<?php declare(strict_types=1);

namespace App\Model\Orm\Article;

use App\AdminCore\Model\SortableEntity;
use Nextras\Orm\Entity\Entity;


/**
 * @property int $id {primary}
 * @property string $title
 * @property int $sortOrder {default 1}
 * @property \DateTimeImmutable $createdAt {default now}
 */
final class Article extends Entity implements SortableEntity
{
	public function getId(): int
	{
		return $this->id;
	}

	public function getSortOrder(): int
	{
		return $this->sortOrder;
	}

	public function setSortOrder(int $sortOrder): static
	{
		$this->sortOrder = $sortOrder;

		return $this;
	}
}
