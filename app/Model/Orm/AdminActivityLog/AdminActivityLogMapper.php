<?php declare(strict_types=1);

namespace App\Model\Orm\AdminActivityLog;

use Nextras\Orm\Mapper\Dbal\DbalMapper;


/**
 * @extends DbalMapper<AdminActivityLog>
 */
final class AdminActivityLogMapper extends DbalMapper
{
	protected string|\Nextras\Dbal\Platforms\Data\Fqn|null $tableName = 'admin_activity_log';
}
