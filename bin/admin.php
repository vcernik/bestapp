<?php declare(strict_types=1);

use App\Bootstrap;
use App\Core\Command\CleanupAdminActivityLogCommand;
use App\Core\Command\CreateAdminUserCommand;
use App\Core\Command\SetAdminUserPasswordCommand;

require __DIR__ . '/../vendor/autoload.php';

$container = (new Bootstrap())->bootWebApplication();

$args = $argv;
array_shift($args);
$command = $args[0] ?? null;

if ($command === null) {
	fwrite(STDERR, "Missing command. Available: admin:user:create, admin:user:set-password, admin:activity-log:cleanup\n");
	exit(1);
}

$options = [];
foreach (array_slice($args, 1) as $arg) {
	if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
		[$name, $value] = explode('=', substr($arg, 2), 2);
		$options[$name] = $value;
	} elseif ($arg === '--force') {
		$options['force'] = '1';
	}
}

$readline = static function (string $prompt): string {
	fwrite(STDOUT, $prompt);
	$line = fgets(STDIN);
	if ($line === false) {
		throw new RuntimeException('Unable to read from STDIN.');
	}
	return trim($line);
};

$readHidden = static function (string $prompt) use ($readline): string {
	if (!function_exists('shell_exec')) {
		return $readline($prompt);
	}

	fwrite(STDOUT, $prompt);
	shell_exec('stty -echo');
	$line = fgets(STDIN);
	shell_exec('stty echo');
	fwrite(STDOUT, PHP_EOL);
	if ($line === false) {
		throw new RuntimeException('Unable to read hidden input from STDIN.');
	}

	return trim($line);
};

try {
	switch ($command) {
		case 'admin:user:create':
			/** @var CreateAdminUserCommand $createCommand */
			$createCommand = $container->getByType(CreateAdminUserCommand::class);
				$username = (string) ($options['username'] ?? $readline('Username: '));
				$name = (string) ($options['name'] ?? $readline('Name: '));
			$password = (string) ($options['password'] ?? $readHidden('Password: '));
			if (!isset($options['password'])) {
				$confirm = $readHidden('Confirm password: ');
				if ($password !== $confirm) {
					fwrite(STDERR, "Passwords do not match.\n");
					exit(1);
				}
			}

				$user = $createCommand->execute($username, $name, $password, isset($options['force']));
			fwrite(STDOUT, "[OK] Admin user created\n");
			fwrite(STDOUT, 'ID: ' . $user->id . "\n");
				fwrite(STDOUT, 'Username: ' . $user->username . "\n");
				fwrite(STDOUT, 'Name: ' . $user->name . "\n");
			fwrite(STDOUT, 'Created at: ' . $user->createdAt->format('Y-m-d H:i:s') . "\n");
			exit(0);

			case 'admin:user:set-password':
				/** @var SetAdminUserPasswordCommand $setPasswordCommand */
				$setPasswordCommand = $container->getByType(SetAdminUserPasswordCommand::class);
				$username = (string) ($options['username'] ?? $readline('Username: '));
				$password = (string) ($options['password'] ?? $readHidden('New password: '));
				if (!isset($options['password'])) {
					$confirm = $readHidden('Confirm new password: ');
					if ($password !== $confirm) {
						fwrite(STDERR, "Passwords do not match.\n");
						exit(1);
					}
				}

				$user = $setPasswordCommand->execute($username, $password);
				fwrite(STDOUT, "[OK] Password updated\n");
				fwrite(STDOUT, 'ID: ' . $user->id . "\n");
				fwrite(STDOUT, 'Username: ' . $user->username . "\n");
				fwrite(STDOUT, 'Name: ' . $user->name . "\n");
				exit(0);

		case 'admin:activity-log:cleanup':
			/** @var CleanupAdminActivityLogCommand $cleanupCommand */
			$cleanupCommand = $container->getByType(CleanupAdminActivityLogCommand::class);
			$olderThan = (string) ($options['older-than'] ?? '6 months');
			$deleted = $cleanupCommand->execute($olderThan);
			fwrite(STDOUT, '[OK] Deleted rows: ' . $deleted . "\n");
			exit(0);

		default:
			fwrite(STDERR, "Unknown command: {$command}\n");
			fwrite(STDERR, "Available: admin:user:create, admin:user:set-password, admin:activity-log:cleanup\n");
			exit(1);
	}
} catch (Throwable $exception) {
	fwrite(STDERR, '[ERROR] ' . $exception->getMessage() . "\n");
	exit(1);
}
