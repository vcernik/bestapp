<?php declare(strict_types=1);

namespace App\Model\Orm\Article;

use Nextras\Orm\Mapper\Dbal\DbalMapper;


/**
 * @extends DbalMapper<Article>
 */
final class ArticleMapper extends DbalMapper
{
	protected string|\Nextras\Dbal\Platforms\Data\Fqn|null $tableName = 'article';
}
