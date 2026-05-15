<?php declare(strict_types=1);

namespace App\Presentation\Admin\Accessory;

use Contributte\FormsBootstrap\BootstrapForm;
use Contributte\FormsBootstrap\Enums\BootstrapVersion;

final class BootstrapFormFactory
{
	private static bool $initialized = false;

	public function create(): BootstrapForm
	{
		if (!self::$initialized) {
			BootstrapForm::switchBootstrapVersion(BootstrapVersion::V5);
			self::$initialized = true;
		}

		return new BootstrapForm;
	}
}
