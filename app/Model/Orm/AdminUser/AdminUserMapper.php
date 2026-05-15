<?php declare(strict_types=1);

namespace App\Model\Orm\AdminUser;

use Nextras\Orm\Mapper\Dbal\DbalMapper;


/**
 * @extends DbalMapper<AdminUser>
 */
final class AdminUserMapper extends DbalMapper
{
	protected string|\Nextras\Dbal\Platforms\Data\Fqn|null $tableName = 'admin_user';
}
