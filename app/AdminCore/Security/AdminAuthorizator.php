<?php declare(strict_types=1);

namespace App\AdminCore\Security;

use Nette\Security\Authorizator;
use Nette\Security\Permission;

final class AdminAuthorizator implements Authorizator
{
	private Permission $permission;

	/**
	 * @param list<string> $roles
	 * @param list<string> $resources
	 * @param array<string, list<array{resource?: mixed, privileges?: mixed}>> $allow
	 */
	public function __construct(array $roles, array $resources, array $allow)
	{
		$this->permission = new Permission;

		foreach ($roles as $role) {
			if ($role !== '') {
				$this->permission->addRole($role);
			}
		}

		foreach ($resources as $resource) {
			if ($resource !== '' && !$this->permission->hasResource($resource)) {
				$this->permission->addResource($resource);
			}
		}

		foreach ($allow as $role => $rules) {
			if (!$this->permission->hasRole($role)) {
				continue;
			}

			foreach ($rules as $rule) {
				$resource = $rule['resource'] ?? null;
				$privileges = $rule['privileges'] ?? null;

				if (!is_string($resource) || $resource === '') {
					continue;
				}

				if ($resource === '*') {
					if ($this->isGlobalPrivilege($privileges)) {
						$this->permission->allow($role);
						continue;
					}

					$this->allowRoleOnAllResources($role, $privileges);
					continue;
				}

				if (!$this->permission->hasResource($resource)) {
					$this->permission->addResource($resource);
				}

				$this->allowRoleOnResource($role, $resource, $privileges);
			}
		}
	}

	public function isAllowed(?string $role, ?string $resource, ?string $privilege): bool
	{
		if ($role === null || $role === '') {
			return false;
		}

		if (!$this->permission->hasRole($role)) {
			return false;
		}

		if ($resource === null) {
			return $this->permission->isAllowed($role, null, $privilege);
		}

		if (!$this->permission->hasResource($resource)) {
			return false;
		}

		return $this->permission->isAllowed($role, $resource, $privilege);
	}

	/**
	 * @param mixed $privileges
	 */
	private function allowRoleOnResource(string $role, string $resource, mixed $privileges): void
	{
		if ($this->isGlobalPrivilege($privileges)) {
			$this->permission->allow($role, $resource);
			return;
		}

		if (!is_array($privileges) || $privileges === []) {
			$this->permission->allow($role, $resource);
			return;
		}

		$allowedPrivileges = [];
		foreach ($privileges as $privilege) {
			if (is_string($privilege) && $privilege !== '') {
				$allowedPrivileges[] = $privilege;
			}
		}

		if ($allowedPrivileges === []) {
			$this->permission->allow($role, $resource);
			return;
		}

		$this->permission->allow($role, $resource, $allowedPrivileges);
	}

	/**
	 * @param mixed $privileges
	 */
	private function allowRoleOnAllResources(string $role, mixed $privileges): void
	{
		foreach ($this->permission->getResources() as $resource) {
			$this->allowRoleOnResource($role, $resource, $privileges);
		}
	}

	/**
	 * @param mixed $privileges
	 */
	private function isGlobalPrivilege(mixed $privileges): bool
	{
		if ($privileges === null) {
			return true;
		}

		if (is_array($privileges) && in_array('*', $privileges, true)) {
			return true;
		}

		return false;
	}
}
