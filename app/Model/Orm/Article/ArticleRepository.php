<?php declare(strict_types=1);

namespace App\Model\Orm\Article;

use App\AdminCore\Model\SortableRepositoryTrait;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;


/**
 * @extends Repository<Article>
 */
final class ArticleRepository extends Repository
{
	/** @use SortableRepositoryTrait<Article> */
	use SortableRepositoryTrait;

	public static function getEntityClassNames(): array
	{
		return [Article::class];
	}


	/**
	 * @return ICollection<Article>
	 */
	public function findLatest(): ICollection
	{
		return $this->findAll()->orderBy('createdAt', ICollection::DESC);
	}

	/**
	 * @return ICollection<Article>
	 */
	public function findOrdered(): ICollection
	{
		return $this->findAll()
			->orderBy('sortOrder', ICollection::ASC)
			->orderBy('createdAt', ICollection::DESC);
	}

	public function reorderByIds(int $itemId, ?int $prevId, ?int $nextId): bool
	{
		/** @var list<Article> $items */
		$items = $this->findOrdered()->fetchAll();

		return $this->performReorderByIds($itemId, $prevId, $nextId, $items);
	}
}
