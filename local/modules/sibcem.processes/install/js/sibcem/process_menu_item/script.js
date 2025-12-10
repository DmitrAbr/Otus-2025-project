BX.ready(function () {
    BX.addCustomEvent("BX.UI.EntityConfigurationManager:onInitialize", BX.delegate((editor, settings) => {

        if (editor.getId() != "intranet-user-profile") {
            return;
        }

        let topMenuId = "#socialnetwork_profile_menu_user_" + editor._entityId;
        let topMenuNode = document.querySelector(topMenuId);

        if (!BX.type.isDomNode(topMenuNode)) {
            return;
        }

        let item = BX.create("div", {
            attrs: {
                className: "main-buttons-item",
                id: "socialnetwork_profile_menu_user_" + editor._entityId + "_processes",
                draggable: true,
                tabindex: -1,
            },
            dataset: {
                disabled: false,
                id: "processes",
                topMenuId: topMenuId,
            },
        });

        item.innerHTML = '<span class="main-buttons-item-link"><span class="main-buttons-item-text"><span class="main-buttons-item-text-title"><span class="main-buttons-item-text-box">'+ BX.message("TITLE_BUTTON_PROCESSES") +'</span></span></span></span>'

        item.onclick = function (event) {
            BX.SidePanel.Instance.open("/processes/" + editor._entityId + "/", {
                cacheable: false,
            });
        }

        item.onmouseout = item.onmouseover = handler;

        function handler(event) {
            if (event.type == "mouseover") {
                this.classList.add("--over");
            } else {
                this.classList.remove("--over");
            }
        }

        BX.insertAfter(item, topMenuNode.firstElementChild);
    }));
});