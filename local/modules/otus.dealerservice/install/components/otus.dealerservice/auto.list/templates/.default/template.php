<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\Grid\Panel\Actions;

$this->setFrameMode(true);

?>


<?

$pageNavigation = $arResult['pageNavigation'];

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.filter",
	'',
	[
		'FILTER_ID' =>	$arResult['filterId'],
		'GRID_ID' => $arResult['gridId'],
		'FILTER' => $arResult['uiFilter'],
		'ENABLE_LIVE_SEARCH' => true,
		'ENABLE_LABEL' => true
	]
);

$onchange = new Onchange();
$onchange->addAction(
    [
        'ACTION' => Actions::CALLBACK,
        'CONFIRM' => true,
        'CONFIRM_APPLY_BUTTON'  => 'Подтвердить',
        'DATA' => [
            ['JS' => 'Grid.removeSelected()']
        ]
    ]
);

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
		'ACTION_PANEL'              => [ 
        'GROUPS' => [ 
            'TYPE' => [ 
                'ITEMS' => [ 
                    [
                        'ID'       => 'delete',
                        'TYPE'     => 'BUTTON',
                        'TEXT'     => 'Удалить',
                        'CLASS'    => 'icon remove grid-delete-button',
                        'ONCHANGE' => $onchange->toArray()
                    ],
                    [ 
                        'ID'       => 'edit', 
                        'TYPE'     => 'BUTTON', 
                        'TEXT'        => 'Редактировать', 
                        'CLASS'        => 'icon edit', 
                        'ONCHANGE' => '' 
                    ], 
                ], 
            ] 
        ], 
    ],
    $component
	]
);
?>

<script>
    BX.ready(function() {
        
        // console.log('BX');
              
        BX.bindDelegate(document.body, 'click', {className: 'grid-delete-button'}, function(event) {
            
            // console.log('bindDelegate');
            
            // event.preventDefault();

            var grid = BX.Main.gridManager.getById('example_list');
            var selectedIds = grid.instance.getRows().getSelectedIds();

            if (selectedIds.length > 0) {
                if (confirm('Вы уверены, что хотите удалить выбранные записи?')) {
                    
                    BX.ajax.runComponentAction('otus:gridcontroller', 'deleteRecords', {
                        data: { ids: selectedIds }
                    }).then(function(response) {
                        if (response.status === 'success') {
                            grid.reload();
                        } else {
                            console.log('Ошибка при удалении записей');
                        }
                    });
                }
            } else {
                console.log('Выберите хотя бы одну запись для удаления');
            }
        });

        BX.bindDelegate(document.body, 'click', {className: 'grid-edit-button'}, function(event) {
            console.log('EDIT');
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