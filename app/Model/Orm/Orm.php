<?php declare(strict_types=1);

namespace App\Model\Orm;

use App\Admin\Model\Orm\AdminActivityLog\AdminActivityLogRepository;
use App\Admin\Model\Orm\AdminUser\AdminUserRepository;
use App\Model\Orm\Article\ArticleRepository;
use Nextras\Orm\Model\Model;


/**
 * @property-read AdminUserRepository $adminUsers
 * @property-read AdminActivityLogRepository $adminActivityLogs
 * @property-read ArticleRepository $articles
 */
final class Orm extends Model
{
}
