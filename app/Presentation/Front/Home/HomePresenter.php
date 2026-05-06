<?php declare(strict_types=1);

namespace App\Presentation\Front\Home;

use App\Model\Orm\Article\ArticleRepository;
use Nette;


final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
	)
	{
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->articles = $this->articleRepository
			->findLatest()
			->fetchAll();
	}
}
