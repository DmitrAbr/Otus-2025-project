<?php

use Bitrix\Main\Application;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Otus\Dealerservice\Orm\AutoTable;
use Bitrix\Main\Localization\Loc;
use Otus\Dealerservice\Helpers\Actions;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

class AutoListViewComponent extends \CBitrixComponent
{
    const MODULE_ID = "otus.dealerservice";
    
    protected $request;
    protected const GRID_ID = 'AUTO_GRID';
    protected const NAVIGATION_ID = 'PAGE';
    protected const FILTER_ID = self::GRID_ID . '_FILTER';
    
    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->request = Application::getInstance()->getContext()->getRequest();
    }
    
    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }
    
    public function executeComponent()
    {
        $errors = new ErrorCollection();
        global $USER;

        if(!Loader::includeModule(self::MODULE_ID) || !Loader::includeModule('crm'))
        {
            $errors->setError(new Error("Не установлены обязательные модули"));
        }

        if(!$USER->IsAdmin() && Actions::checkRightsUser($USER->GetID()) === false)
        {
            $errors->setError(new Error('Доступ запрещен, обратитесь к менеджеру'));
        }
        
        try {
            $this->getOptions();
            $this->fillGridInfo();
            $this->fillGridData();
            
            if(!empty($errors))
            {
                foreach($errors as $error)
                {
                    ShowError($error->getMessage());
                    return;
                }
            }

            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            ShowError($e->getMessage());    
        }
    }
    
    private function fillGridInfo(): void
    {
        $this->arResult['gridId'] = static::GRID_ID;
        $this->arResult['filterId'] = static::FILTER_ID;
        $this->arResult['navigationId'] = static::NAVIGATION_ID;
        $this->arResult['uiFilter'] = $this->getFilterFields();
        $this->arResult['gridColumns'] = $this->getColumns();
        $this->arResult['pageNavigation'] = $this->getPageNavigation();
        $this->arResult['pageSizes'] = $this->getPageSizes();
    }
    
    private function getColumns()
    {
        return [
            [
                'id' => 'TITLE',
                'name' => 'Автомобиль',
                'sort' => 'MAKE',
                'default' => true,
                'editable' => false
            ],
            [
                'id' => 'ID',
                'name' => 'ID',
                'default' => false,
                'sort' => 'ID',
            ],    
            [
                'id' => 'MAKE',
                'name' => 'Марка автомобиля',
                'sort' => 'MAKE',
                'default' => true
            ],
            [
                'id' => 'MODEL',
                'name' => 'Модель',
                'sort' => 'MODEL',
                'default' => true
            ],
            [
                'id' => 'NUMBER',
                'name' => 'Номер',
                'sort' => 'NUMBER',
                'default' => true
            ],
            [
                'id' => 'YEAR',
                'name' => 'Год выпуска',
                'sort' => 'YEAR',
                'default' => true
            ],
            [
                'id' => 'COLOR',
                'name' => 'Цвет автомобиля',
                'sort' => 'COLOR',
                'default' => true
            ],
            [
                'id' => 'MILEAGE',
                'name' => 'Пробег',
                'sort' => 'MILEAGE',
                'default' => true
            ],
            [
                'id' => 'STATUS',
                'name' => 'Статус',
                'sort' => 'STATUS',
                'default' => true
            ],
            [
                'id' => 'CLIENT_NAME',
                'name' => 'Клиент',
                'sort' => 'CONTACT.NAME',
                'default' => false
            ],
            [
                'id' => 'CREATED_AT',
                'name' => 'Когда создан',
                'sort' => 'CREATED_AT',
                'default' => false
            ],
            [
                'id' => 'CREATOR_NAME',
                'name' => 'Кем создан',
                'sort' => 'CREATED_BY_USER.NAME',
                'default' => true
            ],
            [
                'id' => 'UPDATED_AT',
                'name' => 'Когда обновлен',
                'sort' => 'UPDATED_AT',
                'default' => true
            ],
            [
                'id' => 'UPDATER_NAME',
                'name' => 'Кем обновлен',
                'sort' => 'UPDATED_BY_USER.NAME',
                'default' => false
            ],
        ];
    }
    
    private function getOptions()
    {
        $gridOptions = new GridOptions(static::GRID_ID);
        $this->arResult['GridOptions'] = $gridOptions;
        
        $filterOptions = new FilterOptions(static::FILTER_ID);
        $this->arResult['FilterOptions'] = $filterOptions;
    }
    
    private function getPageNavigation()
    {
        $navParams = $this->arResult['GridOptions']->GetNavParams();

        $pageNavigation = new PageNavigation(static::NAVIGATION_ID);
        $pageNavigation->setPageSize($navParams['nPageSize'])->initFromUri();

        $currentPage = $this->request->get(static::NAVIGATION_ID);
        
        if (is_numeric($currentPage)) {
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
        $preparedFilter = ["CONTACT.ID" => $this->arParams['contactID']];
        
        if (!empty($filter)) {
            $allowedFields = array_column($this->getFilterFields(), 'id');
            
            foreach ($filter as $key => $value) {
                if (in_array($key, $allowedFields) && !empty($value)) {
                    $preparedFilter[$key] = $value;
                }
            }
        }
        
        $list = [];
        $dataAuto = AutoTable::getList([
            'filter' => $preparedFilter,
            'select' => [
                'ID', 'MAKE', 'MODEL', 'NUMBER', 'YEAR', 'COLOR', 'MILEAGE', 'STATUS',
                'CREATED_AT', 'UPDATED_AT',
                'CREATOR_ID' => 'CREATED_BY.ID',
                'UPDATER_ID' => 'UPDATED_BY.ID',
                'CREATOR_LOGIN' => 'CREATED_BY.LOGIN',
                'CREATOR_NAME' => 'CREATED_BY.NAME',
                'CREATOR_LAST_NAME' => 'CREATED_BY.LAST_NAME',
                'CREATOR_SECOND_NAME' => 'CREATED_BY.SECOND_NAME',
                'UPDATER_LOGIN' => 'UPDATED_BY.LOGIN',
                'UPDATER_NAME' => 'UPDATED_BY.NAME',
                'UPDATER_SECOND_NAME' => 'UPDATED_BY.SECOND_NAME',
                'UPDATER_LAST_NAME' => 'UPDATED_BY.LAST_NAME'
            ],
            'order' => $sort['sort'],
            'limit' => $limit,
            'offset' => $offset,
            'count_total' => true
        ]);
        
        while ($item = $dataAuto->fetch()) {
            $preparedItem = $this->prepareItemData($item);
            
            $list[] = [
                'data' => $preparedItem,
                'actions' => [
                    [
                        'text' => 'Просмотр',
                        'default' => true,
                        'onclick' => 'document.location.href="?op=view&id=' . $item['ID'] . '"'
                    ],
                    [
                        'text' => 'Редактировать',
                        'onclick' => 'document.location.href="?op=edit&id=' . $item['ID'] . '"'
                    ],
                    [
                        'text' => 'Удалить',
                        'onclick' => 'if(confirm("Точно удалить автомобиль?")){document.location.href="?op=delete&id=' . $item['ID'] . '"}'
                    ]
                ]
            ];
        }
        
        $pageNav->setRecordCount($dataAuto->getCount());
        $this->arResult['LIST'] = $list;
    }
    
    private function prepareItemData(array $item): array
    {
        if ($item['CREATED_AT'] instanceof DateTime) {
            $item['CREATED_AT'] = $item['CREATED_AT']->format('d.m.Y H:i');
        }
        
        if ($item['UPDATED_AT'] instanceof DateTime) {
            $item['UPDATED_AT'] = $item['UPDATED_AT']->format('d.m.Y H:i');
        }
        
        $carTitle = sprintf(
            '%s %s (%s)',
            $item['MAKE'],
            $item['MODEL'],
            $item['YEAR']
        );
        
        $item['TITLE'] = sprintf(
            '<a href="?op=view&id=%s" class="auto-title-link" title="Перейти к просмотру">%s</a>',
            $item['ID'],
            htmlspecialcharsbx($carTitle)
        );
        
        
        
        $item['CREATOR_NAME'] = \CUser::FormatName(
        \CSite::GetNameFormat(), ['NAME' => $item['CREATOR_NAME'], 'LAST_NAME' => $item['CREATOR_LAST_NAME'], 'SECOND_NAME' => $item['CREATOR_SECOND_NAME']]
        );
        $item['CREATOR_NAME'] = '<a href="/company/personal/user/' . $item['CREATOR_ID'] . '/">' . $item['CREATOR_NAME'] . '</a>';

        $item['UPDATER_NAME'] = \CUser::FormatName(
  \CSite::GetNameFormat(), ['NAME' => $item['UPDATER_NAME'], 'LAST_NAME' => $item['UPDATER_LAST_NAME'], 'SECOND_NAME' => $item['UPDATER_SECOND_NAME']]
        );
        $item['UPDATER_NAME'] = '<a href="/company/personal/user/' . $item['UPDATER_ID'] . '/">' . $item['UPDATER_NAME'] . '</a>';
        
        $statusMap = [
            AutoTable::NEW => ['name' => Loc::getMessage('STATUS_NEW'), 'color' => '#2e86ab'],
            AutoTable::REJECTED => ['name' => Loc::getMessage('STATUS_REJECTED'), 'color' => '#e74c3c'],
            AutoTable::IN_WORK => ['name' => Loc::getMessage('STATUS_IN_WORK'), 'color' => '#f39c12'],
            AutoTable::DONE => ['name' => Loc::getMessage('STATUS_DONE'), 'color' => '#9b59b6']
        ];
        
        $statusInfo = $statusMap[$item['STATUS']] ?? ['name' => $item['STATUS'], 'color' => '#95a5a6'];
        $item['STATUS'] = sprintf(
            '<span style="color: %s; font-weight: 600;">%s</span>',
            $statusInfo['color'],
            htmlspecialcharsbx($statusInfo['name'])
        );
        
        if ($item['MILEAGE']) {
            $item['MILEAGE'] = '<span style="font-weight: 500;">' . 
                            number_format($item['MILEAGE'], 0, '', ' ') . 
                            ' км</span>';
        } else {
            $item['MILEAGE'] = '<span style="color: #95a5a6;">—</span>';
        }
        
        if ($item['NUMBER']) {
            $item['NUMBER'] = '<span style="font-family: monospace; font-weight: 600; background: #f8f9fa; padding: 2px 6px; border-radius: 4px;">' . 
                            htmlspecialcharsbx($item['NUMBER']) . 
                            '</span>';
        } else {
            $item['NUMBER'] = '<span style="color: #95a5a6;">—</span>';
        }
        
        if (!$item['YEAR']) {
            $item['YEAR'] = '<span style="color: #95a5a6;">—</span>';
        }
        
        return $item;
    }
    
    private function getFilterFields(): array
    {
        return [
            ['id' => 'ID', 'name' => 'ID', 'type' => 'number', 'default' => true],
            ['id' => 'MAKE', 'name' => 'Марка автомобиля', 'type' => 'text', 'default' => true],
            ['id' => 'MODEL', 'name' => 'Модель', 'type' => 'text', 'default' => true],
            ['id' => 'NUMBER', 'name' => 'Номер', 'type' => 'text', 'default' => true],
            ['id' => 'YEAR', 'name' => 'Год выпуска', 'type' => 'number', 'default' => true],
            ['id' => 'COLOR', 'name' => 'Цвет', 'type' => 'text', 'default' => true],
            ['id' => 'MILEAGE', 'name' => 'Пробег', 'type' => 'number', 'default' => true],
            [
                'id' => 'STATUS', 
                'name' => 'Статус', 
                'type' => 'list', 
                'default' => true,
                'items' => [
                    AutoTable::NEW => 'Новый',
                    AutoTable::REJECTED => 'Отклонен',
                    AutoTable::IN_WORK => 'В работе',
                    AutoTable::DONE => 'Завершен'
                ]
            ],
            ['id' => 'CREATED_AT', 'name' => 'Дата создания', 'type' => 'date', 'default' => true],
        ];
    }
    
    private function getPageSizes(): array
    {
        return [
            ['NAME' => '5', 'VALUE' => '5'],
            ['NAME' => '10', 'VALUE' => '10'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100'],
        ];
    }
}
