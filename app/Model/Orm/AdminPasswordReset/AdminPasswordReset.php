<?php declare(strict_types=1);

namespace App\Model\Orm\AdminPasswordReset;

use Nextras\Orm\Entity\Entity;


/**
 * @property int $id {primary}
 * @property int $userId
 * @property string $tokenHash
 * @property \DateTimeImmutable $expiresAt
 * @property ?\DateTimeImmutable $usedAt
 * @property \DateTimeImmutable $createdAt {default now}
 */
final class AdminPasswordReset extends Entity
{
}
