<?php declare(strict_types=1);

namespace App\Admin\Model\Orm;

use App\Admin\Model\Orm\AdminActivityLog\AdminActivityLogRepository;
use App\Admin\Model\Orm\AdminUser\AdminUserRepository;
use Nextras\Orm\Model\Model;


/**
 * @property-read AdminUserRepository $adminUsers
 * @property-read AdminActivityLogRepository $adminActivityLogs
 */
final class AdminOrm extends Model
{
}
