<?php declare(strict_types=1);

namespace App\Presentation\Admin\Home;

use App\Model\Orm\Article\ArticleRepository;
use App\Presentation\Admin\Accessory\BasePrivatePresenter;
use App\Presentation\Admin\Accessory\AdminMenuProvider;
use App\Presentation\Admin\Accessory\DatagridFactory;
use Contributte\Datagrid\Datagrid;


final class HomePresenter extends BasePrivatePresenter
{
	public function __construct(
		AdminMenuProvider $adminMenuProvider,
		private readonly ArticleRepository $articleRepository,
		private readonly DatagridFactory $datagridFactory,
	)
	{
		parent::__construct($adminMenuProvider);
	}

	protected function createComponentArticlesGrid(): Datagrid
	{
		$grid = $this->datagridFactory->create();

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
