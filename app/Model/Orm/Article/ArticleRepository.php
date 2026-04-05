<?php declare(strict_types=1);

namespace App\Model\Orm\Article;

use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Repository\Repository;


/**
 * @extends Repository<Article>
 */
final class ArticleRepository extends Repository
{
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
}
