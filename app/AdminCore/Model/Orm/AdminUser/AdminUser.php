<?php declare(strict_types=1);

namespace App\AdminCore\Model\Orm\AdminUser;

use Nextras\Orm\Entity\Entity;


/**
 * @property int $id {primary}
 * @property string $username
 * @property string $name
 * @property string $role {default superadmin}
 * @property bool $enabled {default true}
 * @property string $passwordHash
 * @property int $failedCount {default 0}
 * @property ?\DateTimeImmutable $blockedUntil
 * @property ?\DateTimeImmutable $lastAttemptAt
 * @property \DateTimeImmutable $createdAt {default now}
 * @property \DateTimeImmutable $updatedAt {default now}
 */
class AdminUser extends Entity
{
}
