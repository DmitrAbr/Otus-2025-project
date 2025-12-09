<?php

use Bitrix\Main\Application;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Otus\Dealerservice\Orm\AutoTable;
use Otus\Dealerservice\Auto;
use Bitrix\Main\Localization\Loc;
use Otus\Dealerservice\Helpers\Actions;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Diag\Debug;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

class AutoListViewComponent extends \CBitrixComponent implements Controllerable
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

        if(!Loader::includeModule(self::MODULE_ID) || !Loader::includeModule('crm') || !Loader::includeModule('ui'))
        {
            $errors->setError(new Error(Loc::getMessage("ERROR_LOADED_MODULES")));
        }

        if(!$USER->IsAdmin() && Actions::checkRightsUser($USER->GetID()) === false)
        {
            $errors->setError(new Error(Loc::getMessage("DENIED_ACCESS")));
        }
        
        try {
            $this->arResult["CURRENT_USER_ID"] = $USER->GetID();
            
            $this->getClientInfo($this->arParams['contactID']);
            
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
    
    private function getClientInfo(int $contactId): void
    {
        Loader::includeModule('crm');
        
        $contact = \CCrmContact::GetByID($contactId, false);
        
        if ($contact) {
            $this->arResult['CLIENT_NAME'] = \CUser::FormatName(
                \CSite::GetNameFormat(),
                [
                    'NAME' => $contact['NAME'] ?? '',
                    'LAST_NAME' => $contact['LAST_NAME'] ?? '',
                    'SECOND_NAME' => $contact['SECOND_NAME'] ?? ''
                ]
            );
            
            $this->arResult['CLIENT_DATA'] = [
                'ID' => $contact['ID'],
                'NAME' => $contact['NAME'] ?? '',
                'LAST_NAME' => $contact['LAST_NAME'] ?? '',
                'SECOND_NAME' => $contact['SECOND_NAME'] ?? '',
                'FULL_NAME' => $this->arResult['CLIENT_NAME'],
                'PHOTO' => $contact['PHOTO'] ?? null,
                'EMAIL' => $contact['EMAIL'] ?? '',
                'PHONE' => $contact['PHONE'] ?? '',
            ];
        } else {
            $this->arResult['CLIENT_NAME'] = 'Контакт #' . $contactId;
            $this->arResult['CLIENT_DATA'] = [
                'ID' => $contactId,
                'FULL_NAME' => $this->arResult['CLIENT_NAME']
            ];
        }
        
        $this->arParams['clientName'] = $this->arResult['CLIENT_NAME'];
    }

    public function configureActions(): array
    {
        return [
            'addAuto' => [
                'prefilters' => [],
                'postfilters' => [],
            ],
            'updateAuto' => [
                'prefilters' => [],
            ],
            'getAuto' => [
                'prefilters' => [],
            ],
            'deleteAuto' => [
                'prefilters' => [],
            ],
            'getAutoDeals' => [
                'prefilters' => [],
            ],
        ];
    }

    public function getAutoDealsAction(int $id): array
    {
        Loader::includeModule(self::MODULE_ID);

        $response = [
            'success' => false,
            'errors' => []
        ];

        try {
            $deals = Auto::getDeals($id);
            
            $groupedDeals = [];
            foreach($deals as $deal) {
                $dealId = $deal['ID'];
                
                if (!isset($groupedDeals[$dealId])) {
                    $groupedDeals[$dealId] = array_diff_key($deal, [
                        'PRODUCT_NAME' => '',
                        'PRODUCT_QUANTITY' => ''
                    ]);
                    $groupedDeals[$dealId]['PRODUCTS'] = [];
                }
                
                if (!empty($deal['PRODUCT_NAME'])) {
                    $groupedDeals[$dealId]['PRODUCTS'][] = [
                        'NAME' => $deal['PRODUCT_NAME'],
                        'QUANTITY' => $deal['PRODUCT_QUANTITY'] ?? 0
                    ];
                }
            }
            
            $groupedDeals = array_values($groupedDeals);
            
            foreach($groupedDeals as &$deal) {
                $nameParts = array_filter([
                    $deal['ASSIGNED_BY_NAME'] ?? '',
                    $deal['ASSIGNED_BY_LAST_NAME'] ?? '',
                    $deal['ASSIGNED_BY_SECOND_NAME'] ?? ''
                ]);
                
                $deal['ASSIGNED_BY_FULL_NAME'] = trim(implode(' ', $nameParts));
                
                if (empty($deal['ASSIGNED_BY_FULL_NAME']) && !empty($deal['ASSIGNED_BY_ID'])) {
                    $deal['ASSIGNED_BY_FULL_NAME'] = 'ID: ' . $deal['ASSIGNED_BY_ID'];
                }
            }
            unset($deal); 
            
            $stageList = [];
            if (!empty($groupedDeals)) {
                $categoryIds = array_column($groupedDeals, 'CATEGORY_ID');
                $categoryIds = array_filter(array_unique($categoryIds));
                
                foreach($categoryIds as $categoryId) {
                    $categoryStages = \Bitrix\Crm\Category\DealCategory::getStageList($categoryId);
                    if (is_array($categoryStages)) {
                        $stageList = array_merge($stageList, $categoryStages);
                    }
                }
                
                if (empty($stageList)) {
                    $stageList = \Bitrix\Crm\Category\DealCategory::getStageList(0);
                }
            }

            $response['data'] = [
                'deals' => $groupedDeals, 
                'stages' => $stageList 
            ];
            $response['success'] = true;

        } catch (\Exception $e) {
            $response['errors'][] = $e->getMessage();
        }

        return $response;
    }

    public function deleteAutoAction(array $ids)
    {
        Loader::includeModule(self::MODULE_ID);

        $response = [
            'success' => false,
            'errors' => []
        ];

        try {
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) {
                return $id > 0;
            });

            if (empty($ids)) {
                $response['errors'][] = Loc::getMessage('NOT_SELECTED_AUTO_FOR_DELETE');
                return $response;
            }

            foreach ($ids as $id) {
                $result = AutoTable::delete($id);
                if (!$result->isSuccess()) {
                    $response['errors'] = array_merge($response['errors'], $result->getErrorMessages());
                }
            }

            if (empty($response['errors'])) {
                $response['success'] = true;
            }

        } catch (\Exception $e) {
            $response['errors'][] = $e->getMessage();
        }

        return $response;
    }

    public function getAutoAction($id)
    {
        Loader::includeModule(self::MODULE_ID);

        $response = [
            'success' => false,
            'data' => [],
            'errors' => []
        ];

        try {
            $auto = AutoTable::getById($id)->fetch();
            
            if ($auto) {
                $response['success'] = true;
                $response['data'] = $auto;
            } else {
                $response['errors'][] = Loc::getMessage("AUTO_NOT_FOUND");
            }

        } catch (\Exception $e) {
            $response['errors'][] = $e->getMessage();
        }

        return $response;
    }

    public function addAutoAction(array $params)
    {
        Loader::includeModule(self::MODULE_ID);

        $response = [
            'success' => false,
            'errors' => []
        ];

        try {
            $currentUserId = (int)($params['UPDATED_BY_ID'] ?? 0);
            
            if ($currentUserId === 0) {
                global $USER;
                if ($USER && $USER->IsAuthorized()) {
                    $currentUserId = (int)$USER->GetID();
                }else
                {
                    $response['errors'][] = Loc::getMessage("UNAUTHORIZED_ACCESS");
                    return $response;
                }
            }

            $data = [
                'CLIENT_ID' => (int)$params['CLIENT_ID'],
                'STATUS' => $params['STATUS'] ?? AutoTable::NEW,
                'MAKE' => $params['MAKE'] ?? '',
                'MODEL' => $params['MODEL'] ?? '',
                'NUMBER' => $params['NUMBER'] ?? '',
                'YEAR' => isset($params['YEAR']) ? (int)$params['YEAR'] : 0,
                'COLOR' => $params['COLOR'] ?? '',
                'MILEAGE' => isset($params['MILEAGE']) ? (int)$params['MILEAGE'] : 0,
                'CREATED_BY_ID' => $currentUserId,
                'UPDATED_BY_ID' => $currentUserId, 
            ];

            $result = AutoTable::add($data);

            if ($result->isSuccess()) {
                $response['success'] = true;
                $response['id'] = $result->getId();
            } else {
                $response['errors'] = $result->getErrorMessages();
            }

        } catch (\Exception $e) {
            $response['errors'][] = $e->getMessage();
        }

        return $response;
    }

    public function updateAutoAction(array $params)
    {
        Loader::includeModule(self::MODULE_ID);

        $response = [
            'success' => false,
            'errors' => []
        ];

        try {
            if (empty($params['ID'])) {
                $response['errors'][] = Loc::getMessage("ID_AUTO_NOT_FOUND");
                return $response;
            }

            $currentUserId = (int)($params['UPDATED_BY_ID'] ?? 0);
            if ($currentUserId === 0) {
                global $USER;
                if ($USER && $USER->IsAuthorized()) {
                    $currentUserId = (int)$USER->GetID();
                }
            }

            $data = [
                'MAKE' => $params['MAKE'] ?? '',
                'MODEL' => $params['MODEL'] ?? '',
                'NUMBER' => $params['NUMBER'] ?? '',
                'YEAR' => isset($params['YEAR']) ? (int)$params['YEAR'] : 0,
                'COLOR' => $params['COLOR'] ?? '',
                'MILEAGE' => isset($params['MILEAGE']) ? (int)$params['MILEAGE'] : 0,
                'UPDATED_BY_ID' => $currentUserId,
            ];

            $result = AutoTable::update((int)$params['ID'], $data);

            if ($result->isSuccess()) {
                $response['success'] = true;
                $response['id'] = $result->getId();
            } else {
                $response['errors'] = $result->getErrorMessages();
            }

        } catch (\Exception $e) {
            $response['errors'][] = $e->getMessage();
        }

        return $response;
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
                'name' => Loc::getMessage("TITLE_AUTO"),
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
                'name' => Loc::getMessage("TITLE_MAKE"),
                'sort' => 'MAKE',
                'default' => true
            ],
            [
                'id' => 'MODEL',
                'name' => Loc::getMessage("TITLE_MODEL"),
                'sort' => 'MODEL',
                'default' => true
            ],
            [
                'id' => 'NUMBER',
                'name' => Loc::getMessage("TITLE_NUMBER"),
                'sort' => 'NUMBER',
                'default' => true
            ],
            [
                'id' => 'YEAR',
                'name' => Loc::getMessage("TITLE_YEAR"),
                'sort' => 'YEAR',
                'default' => true
            ],
            [
                'id' => 'COLOR',
                'name' => Loc::getMessage("TITLE_COLOR"),
                'sort' => 'COLOR',
                'default' => true
            ],
            [
                'id' => 'MILEAGE',
                'name' => Loc::getMessage("TITLE_MILEAGE"),
                'sort' => 'MILEAGE',
                'default' => true
            ],
            [
                'id' => 'STATUS',
                'name' => Loc::getMessage("TITLE_STATUS"),
                'sort' => 'STATUS',
                'default' => true
            ],
            [
                'id' => 'CREATED_AT',
                'name' => Loc::getMessage("TITLE_CREATED"),
                'sort' => 'CREATED_AT',
                'default' => false
            ],
            [
                'id' => 'CREATOR_NAME',
                'name' => Loc::getMessage("TITLE_CREATED_BY"),
                'sort' => 'CREATED_BY_USER.NAME',
                'default' => true
            ],
            [
                'id' => 'UPDATED_AT',
                'name' => Loc::getMessage("TITLE_UPDATED"),
                'sort' => 'UPDATED_AT',
                'default' => true
            ],
            [
                'id' => 'UPDATER_NAME',
                'name' => Loc::getMessage("TITLE_UPDATED_BY"),
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
                'CREATED_AT', 'UPDATED_AT', 'CLIENT_ID',
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
                        'text' => Loc::getMessage("TITLE_ACTIONS_VIEW"),
                        'default' => true,
                        'onclick' => '(new BX.AutoPopup('.$item["ID"].', '.json_encode([
                            'name' => $this->arResult['CLIENT_NAME'], 
                            'id' => $this->arParams['contactID']]).')).init()'
                    ],
                    [
                        'text' => Loc::getMessage("TITLE_ACTIONS_EDIT"),
                        'onclick' => "BX.AddAutoWindow.edit(" . $item['ID'] . ", " . json_encode([
                            'name' => $this->arResult['CLIENT_NAME'], 
                            'id' => $this->arParams['contactID']
                        ]) . ", '" . (defined('AIR_SITE_TEMPLATE') ? '--air' : '') . "', " . $this->arResult['CURRENT_USER_ID'] . ", '" . $this->arResult['gridId'] . "')"
                    ],
                    [
                        'text' => Loc::getMessage("TITLE_ACTIONS_DELETE"),
                        'onclick' => "BX.AutoGrid.deleteOne(" . $item['ID'] . ", '" . $this->arResult['gridId'] . "')"
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
            '<span class="auto-title-link" data-auto-id="%s" style="color: #2067b0; cursor: pointer; text-decoration: underline;" title="Перейти к просмотру">%s</span>',
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
            ['id' => 'MAKE', 'name' => Loc::getMessage("TITLE_MAKE"), 'type' => 'text', 'default' => true],
            ['id' => 'MODEL', 'name' => Loc::getMessage("TITLE_MODEL"), 'type' => 'text', 'default' => true],
            ['id' => 'NUMBER', 'name' => Loc::getMessage("TITLE_NUMBER"), 'type' => 'text', 'default' => true],
            ['id' => 'YEAR', 'name' => Loc::getMessage("TITLE_YEAR"), 'type' => 'number', 'default' => true],
            ['id' => 'COLOR', 'name' => Loc::getMessage("TITLE_COLOR"), 'type' => 'text', 'default' => true],
            ['id' => 'MILEAGE', 'name' => Loc::getMessage("TITLE_MILEAGE"), 'type' => 'number', 'default' => true],
            [
                'id' => 'STATUS', 
                'name' => Loc::getMessage("TITLE_STATUS"), 
                'type' => 'list', 
                'default' => true,
                'items' => [
                    AutoTable::NEW => Loc::getMessage("STATUS_NEW"),
                    AutoTable::REJECTED => Loc::getMessage("STATUS_REJECTED"),
                    AutoTable::IN_WORK => Loc::getMessage("STATUS_IN_WORK"),
                    AutoTable::DONE => Loc::getMessage("STATUS_DONE"),
                ]
            ],
            ['id' => 'CREATED_AT', 'name' => Loc::getMessage("TITLE_CREATED"), 'type' => 'date', 'default' => true],
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