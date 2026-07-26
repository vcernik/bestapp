<?php declare(strict_types=1);

namespace App\AdminCore\Security;

use App\AdminCore\Model\Orm\AdminOrm;
use App\AdminCore\Model\Orm\AdminUser\AdminUser;
use Nextras\Orm\Collection\ICollection;

final class AdminUsersAdministrationFacade
{
	public function __construct(
		private readonly AdminOrm $orm,
		private readonly AdminUserManager $adminUserManager,
		private readonly AdminActivityLogger $adminActivityLogger,
	)
	{
	}

	/**
	 * @return list<AdminUser>
	 */
	public function getUsers(): array
	{
		$users = [];
		foreach ($this->orm->adminUsers->findAll()->orderBy('id', ICollection::ASC) as $user) {
			$users[] = $user;
		}

		return $users;
	}

	public function getUserOrFail(int $id): AdminUser
	{
		$user = $this->orm->adminUsers->getById($id);
		if ($user === null) {
			throw new \RuntimeException('Uživatel nebyl nalezen.');
		}

		return $user;
	}

	public function createUser(
		int $actorUserId,
		string $username,
		string $name,
		string $role,
		bool $enabled,
		string $password,
	): AdminUser
	{
		$user = $this->adminUserManager->createUser($username, $name, $password, false, $role, $enabled);
		$this->adminActivityLogger->log($actorUserId, 'admin.user.created', [
			'createdUserId' => $user->id,
			'username' => $user->username,
			'name' => $user->name,
			'role' => $user->role,
			'enabled' => $user->enabled,
		]);

		return $user;
	}

	public function updateUser(
		int $actorUserId,
		int $editedUserId,
		string $username,
		string $name,
		string $role,
		bool $enabled,
		?string $newPassword,
	): void
	{
		$editedUser = $this->getUserOrFail($editedUserId);
		$normalizedRole = mb_strtolower(trim($role));
		$previousUsername = $editedUser->username;
		$previousName = $editedUser->name;

		$this->adminUserManager->updateIdentity($editedUser, $username, $name);
		if ($previousUsername !== $editedUser->username || $previousName !== $editedUser->name) {
			$this->adminActivityLogger->log($actorUserId, 'admin.user.identity.changed', [
				'editedUserId' => $editedUserId,
				'username' => $editedUser->username,
				'name' => $editedUser->name,
			]);
		}

		if ($editedUserId === $actorUserId && !$enabled) {
			throw new \RuntimeException('Nelze zakázat vlastní účet.');
		}

		$primaryRole = $this->adminUserManager->getDefaultRole();

		if ($editedUser->role === $primaryRole && (!$enabled || $normalizedRole !== $primaryRole)) {
			if (!$this->hasAnotherEnabledSuperadmin($editedUser->id)) {
				throw new \RuntimeException('Nelze změnit posledního aktivního superadmina.');
			}
		}

		if ($editedUser->role !== $normalizedRole) {
			if ($editedUserId === $actorUserId && $normalizedRole !== $primaryRole) {
				throw new \RuntimeException('Nelze si odebrat roli superadmin.');
			}

			$this->adminUserManager->updateRole($editedUser, $normalizedRole);
			$this->adminActivityLogger->log($actorUserId, 'admin.user.role.changed', [
				'editedUserId' => $editedUserId,
				'role' => $normalizedRole,
			]);
		}

		if ($editedUser->enabled !== $enabled) {
			$this->adminUserManager->setEnabled($editedUser, $enabled);
			$this->adminActivityLogger->log($actorUserId, 'admin.user.enabled.changed', [
				'editedUserId' => $editedUserId,
				'enabled' => $enabled,
			]);
		}

		if ($newPassword !== null && trim($newPassword) !== '') {
			$this->adminUserManager->updatePassword($editedUser, $newPassword);
			$this->adminActivityLogger->log($actorUserId, 'admin.user.password.changed', [
				'editedUserId' => $editedUserId,
			]);
		}
	}

	private function hasAnotherEnabledSuperadmin(int $exceptUserId): bool
	{
		$primaryRole = $this->adminUserManager->getDefaultRole();
		foreach ($this->orm->adminUsers->findBy(['role' => $primaryRole, 'enabled' => true]) as $user) {
			if ($user->id !== $exceptUserId) {
				return true;
			}
		}

		return false;
	}
}
