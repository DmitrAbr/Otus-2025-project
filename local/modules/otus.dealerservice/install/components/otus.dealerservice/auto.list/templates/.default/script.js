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
            if (!Array.isArray(ids)) {
                ids = [ids];
            }

            ids = ids.filter(function(id) {
                return parseInt(id) > 0;
            });

            if (ids.length === 0) {
                return;
            }

            BX.UI.Notification.Center.notify({
                content: BX.message("WAIT_DELETE"),
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
                        ? BX.message("AUTO_SUCCESS_DELETE") 
                        : BX.message("AUTOS_SUCCESS_DELETE");
                    
                    BX.UI.Notification.Center.notify({
                        content: successMessage,
                        autoHideDelay: 3000
                    });
                    if (gridId) {
                        var grid = BX.Main.gridManager.getInstanceById(gridId);
                        if (grid) {
                            grid.reloadTable();
                        }
                    }
                } else {
                    var errorMessage = BX.message("ERROR_DELETE");
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
                    content: BX.message("ERROR_SERVER_DELETE"),
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
                    content: BX.message("SELECT_AUTO_DELETE"),
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
            if (confirm(BX.message("CONFIRM_MESSAGE_DELETE"))) {
                this.delete(id, gridId);
            }
        }
    };

})();