<?php declare(strict_types=1);

namespace App\Admin\Model\Orm\AdminUser;

use Nextras\Orm\Mapper\Dbal\DbalMapper;


/**
 * @extends DbalMapper<AdminUser>
 */
class AdminUserMapper extends DbalMapper
{
	protected string|\Nextras\Dbal\Platforms\Data\Fqn|null $tableName = 'admin_user';
}
