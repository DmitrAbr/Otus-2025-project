<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Localization\Loc;

$this->setFrameMode(true);

Loc::loadMessages(__FILE__);

\CJSCore::Init(['popup', 'otus.auto_add_window']);

$pageNavigation = $arResult['pageNavigation'];
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
                            'CLASS' => 'icon remove grid-delete-button',
                        ],
                        [ 
                            'ID' => 'edit', 
                            'TYPE' => 'BUTTON', 
                            'TEXT' => 'Редактировать', 
                            'CLASS' => 'icon edit', 
                            'ONCHANGE' => '' 
                        ], 
                    ], 
                ] 
            ], 
        ],
    ],
    $component
);
?>

<script>
    BX.ready(function(){
        const buttonAdd = BX('add-button');

        var popup = BX.PopupWindowManager.create();

        buttonAdd.addEventListener('click', function(){
            (new BX.AddAutoWindow()).init();
        });
    });
</script>