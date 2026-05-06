<?php declare(strict_types=1);

namespace App\Presentation\Admin\Home;

use App\Model\Orm\Article\ArticleRepository;
use Contributte\Datagrid\Datagrid;
use Nette;


final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function __construct(
		private readonly ArticleRepository $articleRepository,
	)
	{
		parent::__construct();
	}

	protected function createComponentArticlesGrid(): Datagrid
	{
		$grid = new Datagrid;

		$grid->setDataSource($this->articleRepository->findLatest());
		$grid->setDefaultSort(['createdAt' => 'DESC']);

		$grid->addColumnNumber('id', 'ID')
			->setSortable();

		$grid->addColumnText('title', 'Titulek')
			->setSortable()
			->setFilterText();

		$grid->addColumnDateTime('createdAt', 'Vytvoreno')
			->setFormat('j. n. Y H:i')
			->setSortable();

		return $grid;
	}


	public function renderDefault(): void
	{
	}
}
