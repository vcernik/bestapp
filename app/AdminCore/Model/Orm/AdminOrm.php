<?php declare(strict_types=1);

namespace App\AdminCore\Model\Orm;

use App\AdminCore\Model\Orm\AdminActivityLog\AdminActivityLogRepository;
use App\AdminCore\Model\Orm\AdminUser\AdminUserRepository;
use Nextras\Orm\Model\Model;


/**
 * @property-read AdminUserRepository $adminUsers
 * @property-read AdminActivityLogRepository $adminActivityLogs
 */
final class AdminOrm extends Model
{
}
