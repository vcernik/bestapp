<?php declare(strict_types=1);

namespace App\AdminCore\Security;

use function implode, in_array, is_array, is_string, trim;

final class AdminRolePermissionOverview
{
	/**
	 * @param list<string> $roles
	 * @param list<string> $resources
	 * @param array<string, list<array{resource?: mixed, privileges?: mixed}>> $allow
	 */
	public function __construct(
		private readonly array $roles,
		private readonly array $resources,
		private readonly array $allow,
	)
	{
	}

	/**
	 * @return list<array{role: string, permissions: list<array{resource: string, privileges: string}>}>
	 */
	public function getRoles(): array
	{
		$overview = [];
		foreach ($this->roles as $role) {
			if ($role === '') {
				continue;
			}

			$overview[] = [
				'role' => $role,
				'permissions' => $this->getPermissions($role),
			];
		}

		return $overview;
	}

	/**
	 * @return list<array{resource: string, privileges: string}>
	 */
	private function getPermissions(string $role): array
	{
		$permissions = [];
		foreach ($this->allow[$role] ?? [] as $rule) {
			$resource = $this->normalizeResource($rule['resource'] ?? null);
			if ($resource === null) {
				continue;
			}

			$permissions[] = [
				'resource' => $resource,
				'privileges' => $this->formatPrivileges($rule['privileges'] ?? null),
			];
		}

		return $permissions;
	}

	private function normalizeResource(mixed $resource): ?string
	{
		if (!is_string($resource)) {
			return null;
		}

		$resource = trim($resource);
		if ($resource === '') {
			return null;
		}

		if ($resource === '*') {
			return $this->resources === [] ? 'Všechny sekce' : 'Všechny sekce (' . implode(', ', $this->resources) . ')';
		}

		return $resource;
	}

	private function formatPrivileges(mixed $privileges): string
	{
		if ($privileges === null) {
			return 'všechna oprávnění';
		}

		if (!is_array($privileges) || $privileges === [] || in_array('*', $privileges, true)) {
			return 'všechna oprávnění';
		}

		$formattedPrivileges = [];
		foreach ($privileges as $privilege) {
			if (!is_string($privilege)) {
				continue;
			}

			$privilege = trim($privilege);
			if ($privilege !== '') {
				$formattedPrivileges[] = $privilege;
			}
		}

		return $formattedPrivileges === [] ? 'všechna oprávnění' : implode(', ', $formattedPrivileges);
	}
}
