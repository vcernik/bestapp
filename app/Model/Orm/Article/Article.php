<?php declare(strict_types=1);

namespace App\Model\Orm\Article;

use Nextras\Orm\Entity\Entity;


/**
 * @property int $id {primary}
 * @property string $title
 * @property \DateTimeImmutable $createdAt {default now}
 */
final class Article extends Entity
{
}
