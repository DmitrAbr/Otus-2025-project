<?

use Bitrix\Main\Application;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}


class TableViewComponent extends \CBitrixComponent
{
	const MODULE_ID = "otus.dealerservice";
	
	protected $request;
	protected const GRID_ID = 'AUTO_GRID';
	protected const NAVIGATION_ID = 'PAGE';
	protected const FILTER_ID = self::GRID_ID . '_FILTER';
	
	public function onPrepareComponentParams($arParams)
	{
		return $arParams;
	}
	
	public function executeComponent()
	{
		try
		{
			Loader::IncludeModule(self::MODULE_ID);
			$this->getOptions();
			$this->fillGridInfo();
			$this->fillGridData();
			
			$this->IncludeComponentTemplate();
			
		}
		catch(SystemException $e)
		{
			ShowError($e->getMessage());	
		}
	}
	
	private function fillGridInfo(): void
	{
		$this->arResult['gridId'] = static::GRID_ID;
		$this->arResult['filterId'] = static::FILTER_ID;
		$this->arResult['navigationId'] = static::NAVIGATION_ID;
		$this->arResult['uiFilter'] = $this->getFilterFields();
		$this->arResult['gridColumns'] = $this->getColumn();
		$this->arResult['pageNavigation'] = $this->getPageNavigation();
		$this->arResult['pageSizes'] = $this->getPageSizes();
	}
	
	private function getColumn()
	{
		return[
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'sort' => 'ID',
			],	
			[
				'id' => 'TITLE',
				'name' => 'Название книги',
				'sort' => 'TITLE',
				'default' => true
			],
			[
				'id' => 'DESCRIPTION',
				'name' => 'Описание',
				'sort' => 'DESCRIPTION',
				'default' => true
			],
			[
				'id' => 'PUBLISH_DATE',
				'name' => 'Публикация',
				'sort' => 'PUBLISH_DATE',
				'default' => true
			],
			[
				'id' => 'FIRST_NAME_AUTHOR',
				'name' => 'Имя автора',
				'sort' => 'FIRST_NAME_AUTHOR',
				'default' => true
			],
			[
				'id' => 'LAST_NAME_AUTHOR',
				'name' => 'Фамилия автора',
				'sort' => 'LAST_NAME_AUTHOR',
				'default' => true
			],
			[
				'id' => 'SECOND_NAME_AUTHOR',
				'name' => 'Отчество автора',
				'sort' => 'SECOND_NAME_AUTHOR',
				'default' => true
			],
			[
				'id' => 'BIOGRAPHY_AUTHOR',
				'name' => 'Биография',
				'sort' => 'BIOGRAPHY_AUTHOR',
				'default' => true
			],
		];
	}
	
	private function getOptions()
	{
		$GridOptions = new GridOptions(static::GRID_ID);
		
		$this->arResult['GridOptions'] = $GridOptions;
		
		$filterOptions = new FilterOptions(static::FILTER_ID);
		
		$this->arResult['FilterOptions'] = $filterOptions;
	}
	
	private function getPageNavigation()
	{
		$navParams = $this->arResult['GridOptions']->GetNavParams();

		$pageNavigation = new PageNavigation(static::NAVIGATION_ID);
		$pageNavigation->setPageSize($navParams['nPageSize'])->initFromUri();

		$currentPage = $this->request->getQuery(static::NAVIGATION_ID);
		
		if (is_numeric($currentPage))
		{
			$pageNavigation->setCurrentPage((int)$currentPage);
		}

		return $pageNavigation;
	}
	
	private function fillGridData(): void
	{
		
		/** @var \Bitrix\Main\UI\PageNavigation $pageNav */
		$pageNav = $this->arResult['pageNavigation'];

		$offset = $pageNav->getOffset();
		$limit = $pageNav->getLimit();
		$sort = $this->arResult['GridOptions']->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
		$filter = $this->arResult['FilterOptions']->getFilter($this->getFilterFields());
		$preparedFilter = [];
		
		if(!empty($filter))
		{
		    // Получаем список допустимых полей из метода getFilterFields()
		    $allowedFields = array_column($this->getFilterFields(), 'id');
		    
		    foreach($filter as $key => $value)
		    {
		        // Оставляем только те поля, которые есть в списке разрешенных
		        if(in_array($key, $allowedFields) && !empty($value))
		        {
		            $preparedFilter[$key] = $value;
		        }
		    }
		}
		
		$list = [];
		$dataBook = BookTable::getList([
			'filter' => $preparedFilter,
			'select' => ['ID','TITLE','DESCRIPTION', 'PUBLISH_DATE', 
				'FIRST_NAME_AUTHOR' => 'AUTHORS.FIRST_NAME',
				'LAST_NAME_AUTHOR' => 'AUTHORS.LAST_NAME',
				'SECOND_NAME_AUTHOR' => 'AUTHORS.SECOND_NAME',
				'BIOGRAPHY_AUTHOR' => 'AUTHORS.BIOGRAPHY'
			],
			'order' => $sort['sort'],
			'limit' => $limit,
			'offset' => $offset
		]);
		
		while($item = $dataBook->fetch())
		{
			if(is_object($item['PUBLISH_DATE'])){
				$item['PUBLISH_DATE'] = $item['PUBLISH_DATE']->toString();
			}
			$list[] = [
				'data' => $item,
				'actions'=>[
					[
						'text' => 'Просмотр',
						'default' => true,
						'onclick' => 'document.location.href="?op=view&id='.$item['ID'].'"'
					],
					[
						'text' => 'Удалить',
						'default' => true,
						'onclick' => 'if(confirm("Точно?")){document.location.href="?op=delete&id='.$item['ID'].'"}'
					]
				]
			];
		}
		$pageNav->setRecordCount(BookTable::getCount());
		$this->arResult['LIST'] = $list;
	}
	
	private function getFilterFields():array
	{
		return[
			['id' => 'TITLE', 'name' => 'Название', 'type' => 'text', 'default' => true],
			['id' => 'DESCRIPTION', 'name' => 'Описание', 'type' => 'text', 'default' => true],
			['id' => 'PUBLISH_DATE', 'name' => 'Дата Публикации', 'type' => 'date', 'default' => true],
			['id' => 'FIRST_NAME_AUTHOR', 'name' => 'Имя автора', 'type' => 'text', 'default' => true],
			['id' => 'LAST_NAME_AUTHOR', 'name' => 'Фамилия автора', 'type' => 'text', 'default' => true],
			['id' => 'SECOND_NAME_AUTHOR', 'name' => 'Отчество автора', 'type' => 'text', 'default' => true],
		];
	}
	
	private function getPageSizes(): array
	{
		return [
			['NAME' => '1', 'VALUE' => '1'],
			['NAME' => '2', 'VALUE' => '2'],
			['NAME' => '3', 'VALUE' => '3'],
			['NAME' => '50', 'VALUE' => '50'],
			['NAME' => '100', 'VALUE' => '100'],
		];
	}
	
}