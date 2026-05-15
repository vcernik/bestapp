<?php declare(strict_types=1);

namespace App\Model\Orm\AdminPasswordReset;

use Nextras\Orm\Mapper\Dbal\DbalMapper;


/**
 * @extends DbalMapper<AdminPasswordReset>
 */
final class AdminPasswordResetMapper extends DbalMapper
{
	protected string|\Nextras\Dbal\Platforms\Data\Fqn|null $tableName = 'admin_password_reset';
}
