(function() {
    'use strict';
    
    BX.namespace('BX.AutoGrid');

    BX.AutoGrid = {
        /**
         * Удаление одного или нескольких автомобилей
         * @param {Array|number} ids - ID автомобилей для удаления
         * @param {string} gridId - ID грида для обновления
         */
        delete: function(ids, gridId) {
            // Нормализуем ids в массив
            if (!Array.isArray(ids)) {
                ids = [ids];
            }

            // Фильтруем валидные ID
            ids = ids.filter(function(id) {
                return parseInt(id) > 0;
            });

            if (ids.length === 0) {
                return;
            }

            // Показываем индикатор загрузки
            BX.UI.Notification.Center.notify({
                content: 'Удаление...',
                autoHideDelay: 1000
            });

            BX.ajax.runComponentAction('otus.dealerservice:auto.list', 'deleteAuto', {
                mode: 'class',
                data: {
                    ids: ids
                }
            }).then(function(response) {
                if (response.data && response.data.success === true) {
                    var successMessage = ids.length === 1 
                        ? 'Автомобиль успешно удален' 
                        : 'Автомобили успешно удалены';
                    
                    BX.UI.Notification.Center.notify({
                        content: successMessage,
                        autoHideDelay: 3000
                    });

                    // Обновляем грид если передан gridId
                    if (gridId) {
                        var grid = BX.Main.gridManager.getInstanceById(gridId);
                        if (grid) {
                            grid.reloadTable();
                        }
                    }
                } else {
                    var errorMessage = 'Ошибка при удалении';
                    if (response.data && response.data.errors && response.data.errors.length > 0) {
                        errorMessage = response.data.errors.join(', ');
                    }
                    BX.UI.Notification.Center.notify({
                        content: errorMessage,
                        autoHide: true,
                        autoHideDelay: 5000
                    });
                }
            }).catch(function(response) {
                BX.UI.Notification.Center.notify({
                    content: 'Ошибка сети при удалении',
                    autoHide: true,
                    autoHideDelay: 5000
                });
            });
        },

        /**
         * Удаление выбранных автомобилей из грида (для Action Panel)
         * @param {string} gridId - ID грида
         */
        deleteSelected: function(gridId) {
            var grid = BX.Main.gridManager.getInstanceById(gridId);
            if (!grid) {
                return;
            }

            var selectedIds = grid.getRows().getSelectedIds();
            if (selectedIds.length === 0) {
                BX.UI.Notification.Center.notify({
                    content: 'Выберите автомобили для удаления',
                    autoHide: true,
                    autoHideDelay: 3000
                });
                return;
            }

            this.delete(selectedIds, gridId);
        },

        /**
         * Удаление одного автомобиля (для бургер-меню)
         * @param {number} id - ID автомобиля
         * @param {string} gridId - ID грида
         */
        deleteOne: function(id, gridId) {
            if (confirm('Точно удалить автомобиль?')) {
                this.delete(id, gridId);
            }
        }
    };

})();