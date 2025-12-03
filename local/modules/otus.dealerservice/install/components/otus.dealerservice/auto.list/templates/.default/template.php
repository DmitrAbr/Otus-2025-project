<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);

Loc::loadMessages(__FILE__);

\CJSCore::Init(['popup', 'otus.auto_add_window', 'otus.auto_popup']);

// Подключаем наши скрипты
$APPLICATION->AddHeadScript($this->GetFolder().'/script.js');

$pageNavigation = $arResult['pageNavigation'];

$onchange = new Onchange();
$onchange->addAction(
    [
        'ACTION' => Actions::CALLBACK,
        'CONFIRM' => true,
        'CONFIRM_APPLY_BUTTON'  => 'Подтвердить',
        'CONFIRM_MESSAGE' => 'Вы действительно хотите удалить выбранные автомобили?',
        'DATA' => [
            ['JS' => "deleteSelectedCars('{$arResult['gridId']}')"]
        ]
    ]
);
?>
<div class="workplace">
    <div class="ui-toolbar workplace-toolbar">
        <div class="ui-toolbar-left">
            <button class="ui-btn ui-btn-primary <?=defined('AIR_SITE_TEMPLATE') ? '--air' : ''?>" id="add-button">
                <?=Loc::getMessage('TITLE_BTN_ADD')?>
            </button>
        </div>
        <div class="ui-toolbar-right">
            <div class="toolbar_custom">
                <?$APPLICATION->IncludeComponent(
                    "bitrix:main.ui.filter",
                    '',
                    [
                        'FILTER_ID' => $arResult['filterId'],
                        'GRID_ID' => $arResult['gridId'],
                        'FILTER' => $arResult['uiFilter'],
                        'ENABLE_LIVE_SEARCH' => true,
                        'ENABLE_LABEL' => true
                    ]
                );?>
            </div>
        </div>
    </div>
</div>

<?
$APPLICATION->IncludeComponent(
    "bitrix:main.ui.grid",
    "",
    [
        "GRID_ID" => $arResult['gridId'],
        "COLUMNS" => $arResult['gridColumns'],
        "ROWS" => $arResult['LIST'],
        "NAV_OBJECT" => $pageNavigation,
        "AJAX_MODE" => "Y",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_HISTORY" => "N",
        "SHOW_ROW_CHECKBOXES" => $arResult['SHOW_ROW_CHECKBOXED'],
        'TOTAL_ROWS_COUNT' => $arResult['pageNavigation']->getRecordCount(),
        'ENABLE_NEXT_PAGE' => $pageNavigation->getCurrentPage() < $pageNavigation->getPageCount(),
        'CURRENT_PAGE' => $pageNavigation->getCurrentPage(),
        'NAV_PARAM_NAME' => $arResult['navigationId'],
        'PAGE_SIZES' => $arResult['pageSizes'],
        'SHOW_ROW_ACTIONS_MENU' => true,
        'SHOW_GRID_SETTINGS_MENU' => true,
        'SHOW_NAVIGATION_PANEL' => true,
        'SHOW_PAGINATION' => true,
        'SHOW_TOTAL_COUNTER' => true,
        'SHOW_PAGESIZE' => true,
        'SHOW_ACTION_PANEL' => true,
        'ALLOW_COLUMNS_SORT' => true,
        'ALLOW_COLUMNS_RESIZE' => true,
        'ALLOW_HORIZONTAL_SCROLL' => true,
        'ALLOW_INLINE_EDIT' => true,
        'ALLOW_SORT' => true,
        'ALLOW_PIN_HEADER' => true,
        'AJAX_OPTION_HISTORY' => 'N',
        'HANDLE_RESPONSE_ERROR' => true,
        'ACTION_PANEL' => [ 
            'GROUPS' => [ 
                'TYPE' => [ 
                    'ITEMS' => [ 
                        [
                            'ID' => 'delete',
                            'TYPE' => 'BUTTON',
                            'TEXT' => 'Удалить',
                            'CLASS' => 'ui-btn ui-btn-link ui-btn-icon-cancel',
                            'ONCHANGE' => $onchange->toArray(),
                        ],
                    ], 
                ] 
            ], 
        ],
    ],
    $component
);
?>
<?
    $clientFields = ['name' => $arResult["CLIENT_NAME"], 'id' => $arParams["contactID"]];
?>
<script>
    function deleteSelectedCars(gridId) {
        if (typeof BX.AutoGrid !== 'undefined' && typeof BX.AutoGrid.deleteSelected === 'function') {
            BX.AutoGrid.deleteSelected(gridId);
        } else {
            console.error('BX.AutoGrid is not defined');
            var grid = BX.Main.gridManager.getInstanceById(gridId);
            if (grid) {
                var selectedIds = grid.getRows().getSelectedIds();
                if (selectedIds.length > 0) {
                    if (confirm('Вы действительно хотите удалить выбранные автомобили?')) {
                        BX.ajax.runComponentAction('otus.dealerservice:auto.list', 'deleteAuto', {
                            mode: 'class',
                            data: {
                                ids: selectedIds
                            }
                        }).then(function(response) {
                            if (response.data && response.data.success === true) {
                                grid.reloadTable();
                                BX.UI.Notification.Center.notify({
                                    content: 'Автомобили успешно удалены',
                                    autoHideDelay: 3000
                                });
                            }
                        });
                    }
                }
            }
        }
    }

    BX.ready(function(){
        const buttonAdd = BX('add-button');

        buttonAdd.addEventListener('click', function(){
            (new BX.AddAutoWindow(
                <?=json_encode($clientFields)?>,
                <?=json_encode(defined('AIR_SITE_TEMPLATE') ? '--air' : '')?>,
                <?=json_encode($arResult["CURRENT_USER_ID"])?>,
                <?=json_encode($arResult["gridId"])?>
            )).init();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('auto-title-link') && e.target.dataset.autoId) {
                e.preventDefault();
                (new BX.AutoPopup(
                    e.target.dataset.autoId,
                    <?=json_encode($clientFields)?>
                )).init();
            }
        });
    });
</script>
<?php if (!empty($arParams['AJAX_LOADER'])) { ?>
    <script>
        BX.addCustomEvent('Grid::beforeRequest', function (gridData, argse) {
            if (argse.gridId != '<?=$arResult['gridId'];?>') {
                return;
            }

			if(argse.url === '')
			{
				argse.url = "<?=$component->getPath()?>/lazyload.ajax.php?site<?=\SITE_ID?>&internal=true&grid_id=<?=$arResult['gridId']?>&grid_action=filter&"
			}

            argse.method = 'POST'
            argse.data = <?= \Bitrix\Main\Web\Json::encode($arParams['AJAX_LOADER']['data']) ?>
        });
    </script>
<?php } ?>