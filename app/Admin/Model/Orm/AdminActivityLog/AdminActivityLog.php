<?php declare(strict_types=1);

namespace App\Admin\Model\Orm\AdminActivityLog;

use Nextras\Orm\Entity\Entity;


/**
 * @property int $id {primary}
 * @property ?int $userId
 * @property string $action
 * @property string $payloadJson
 * @property \DateTimeImmutable $createdAt
 */
class AdminActivityLog extends Entity
{
}
