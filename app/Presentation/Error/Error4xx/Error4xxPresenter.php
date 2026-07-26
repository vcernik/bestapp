<?php declare(strict_types=1);

namespace App\Presentation\Error\Error4xx;

use Nette;
use Nette\Application\Attributes\Requires;


/**
 * Handles 4xx HTTP error responses.
 */
#[Requires(methods: '*', forward: true)]
final class Error4xxPresenter extends Nette\Application\UI\Presenter
{
	public function renderDefault(Nette\Application\BadRequestException $exception): void
	{
		if ($this->isAdminRequest()) {
			$this->forward(':AdminCore:Error4xx:default', [
				'exception' => $exception,
				'request' => $this->getParameter('request'),
			]);
		}

		// renders the appropriate error template based on the HTTP status code
		$code = $exception->getCode();
		$file = is_file($file = __DIR__ . "/$code.latte")
			? $file
			: __DIR__ . '/4xx.latte';
		$this->template->httpCode = $code;
		$this->template->setFile($file);
	}

	private function isAdminRequest(): bool
	{
		$request = $this->getParameter('request');
		if (!$request instanceof Nette\Application\Request) {
			return false;
		}

		$presenterName = $request->getPresenterName();
		return str_starts_with($presenterName, 'AdminCore:') || str_starts_with($presenterName, 'Admin:');
	}
}
