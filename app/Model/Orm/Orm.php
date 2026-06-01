<?php declare(strict_types=1);

namespace App\Model\Orm;

use App\Model\Orm\Article\ArticleRepository;
use Nextras\Orm\Model\Model;


/**
 * @property-read ArticleRepository $articles
 */
final class Orm extends Model
{
}
