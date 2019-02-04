//настройки и вспомогательные переменные
var settings = {
                "action":""
               };

//загрузка меню
$.get("../api/menu.php?method=get&parentId=0", function( data ) {
    var menu = JSON.parse(data);
    var menuHtml = getMenuHtml(menu.data);
    $("#menuContent")[0].innerHTML = menuHtml;
    $("#menuLoader").hide();
});

//обработка результатов загрузки меню и формирование html
function getMenuHtml(data) {
    var menuHtml = '';
    menuHtml = '<div class="menu-block">';

    data.forEach(function(item) {
        menuHtml += '<div id="item'+item.id+'" data-id='+item.id+' data-name='+item.name+' data-url='+item.url+' data-parentId='+item.parentId+' data-sort='+item.sort+' class="menu-item">'+item.name+'';

        if (item.children) {
            menuHtml += getMenuHtml(item.children);
        }

        menuHtml += '</div>';
    });

    menuHtml += '</div>';
    return menuHtml;
}

//универсальная функция добавления нового пункта меню
function addMenuItem(direction) {
    var activeItem = $(".menu-item-selected");
    var name = $("#formName").val().trim();
    var url = $("#formUrl").val().trim();

    if (direction == "sub") {
        parentId = activeItem.attr("data-id");
    } else {
        parentId = activeItem.attr("data-parentId");
    }

    if (name == "" || url == "") {
        showMsg("Задайте название и url");
        return false;
    }

    $.post("../api/menu.php?method=add",
    {
        parentId:parentId,
        name:name,
        url:url,
        siblingId:activeItem.attr("data-id"),
        direction:direction

    },
    function(data) {
        console.log(data);
        var item = JSON.parse(data);
        if(item.error == 1) {
            showMsg(item.errorMsg);
            return false;
        }

        if (direction == "sub") {
            itemHtml = '<div class="menu-block">';
            itemHtml += '<div id="item'+item.id+'" data-id='+item.id+' data-name='+item.name+' data-url='+item.url+' data-parentId='+item.parentId+' data-sort='+item.sort+' class="menu-item">'+item.name+'</div>';
            itemHtml += '</div>';
        } else {
            itemHtml = '<div id="item'+item.id+'" data-id='+item.id+' data-name='+item.name+' data-url='+item.url+' data-parentId='+item.parentId+' data-sort='+item.sort+' class="menu-item">'+item.name+'</div>';
        }

        if (direction == "up") {
            $(".menu-item-selected").before(itemHtml);
        } else if (direction == "down") {
            $(".menu-item-selected").after(itemHtml);
        } else if (direction == "sub") {
            $(".menu-item-selected").html(function(index, value){
                return value + itemHtml;
            });
        }

        $("#addForm").removeClass("add-form-show");
        $("#serverMsg").removeClass("server-msg-show");
        $("#formName").val("");
        $("#formUrl").val("");
        settings.action = "";
    });
}

//смещение пунктов
function moveItem(direction) {
    var item = $(".menu-item-selected");

    $.post("../api/menu.php?method=move",
    {
        itemId:item.attr("data-id"),
        direction:direction
    },
    function(data) {
        console.log(data);
        var data = JSON.parse(data);
        if (data.error == 1) {
            showMsg(data.errorMsg);
            return;
        }

        if (direction == "up") {
            $(item.prev()).before(item);
        } else {
            $(item.next()).after(item);
        }

        settings.action = "";
    });
}

//выдача сообщений
function showMsg(msg) {
    $("#serverMsgContent")[0].innerHTML = msg;
    $("#serverMsg").addClass("server-msg-show");
}

//закрывашка сообщений
$("#serverMsgClose").click(function() {
    $("#serverMsg").removeClass("server-msg-show");
});


//обработка клика по пунктам меню
$("#mainMenuBlock").click(function(e) {
    if ($(e.target).hasClass("menu-item")) {
        $(".menu-item-selected").removeClass("menu-item-selected");
        $(e.target).addClass("menu-item-selected");
    }
});

//проверка на активный пункт и сетап экшена
function setupForAction(action) {
    if ($(".menu-item-selected")[0]) {
        settings.action = action;

        if (action == "addUp" || action == "addDown" || action == "addSub" || action == "edit") {
            $("#addForm").addClass("add-form-show");
        }

        $("#serverMsg").removeClass("server-msg-show");
    } else {
        showMsg("Сначала выделите пункт меню.");
    }
}

//обработчик кнопки добавления наверх
$("#itemAddUpBtn").click(function() {
    setupForAction("addUp");
});

//добавить вниз
$("#itemAddDownBtn").click(function() {
    setupForAction("addDown");
});

//добавить подпункт
$("#itemAddSubBtn").click(function() {
    setupForAction("addSub");
});

//наверх
$("#itemUpBtn").click(function() {
    setupForAction("moveUp");
    moveItem("up");
});

//вниз
$("#itemDownBtn").click(function() {
    setupForAction("moveDown");
    moveItem("down");
});

//редактирование
$("#itemEditBtn").click(function() {
    setupForAction("edit");

    $("#formName").val($(".menu-item-selected").attr("data-name"));
    $("#formUrl").val($(".menu-item-selected").attr("data-url"));
});

function changeItem() {
    var id = $(".menu-item-selected").attr("data-id");
    var name = $("#formName").val();
    var url = $("#formUrl").val();

    $.post("../api/menu.php?method=change",
    {
        itemId:id,
        name:name,
        url:url
    },
    function(data) {
        console.log(data);
        var data = JSON.parse(data);
        if (data.error == 1) {
            showMsg(data.errorMsg);
            return;
        }

        $(".menu-item-selected").text(name);
        $(".menu-item-selected").attr("data-name", name);
        $(".menu-item-selected").attr("data-url", url);

        settings.action = "";
    });
}

//удаление
$("#itemDelBtn").click(function() {
    setupForAction("delete");

    var id = $(".menu-item-selected").attr("data-id");

    $.post("../api/menu.php?method=delete",
    {
        itemId:id,
    },
    function(data) {
        console.log(data);
        var data = JSON.parse(data);
        if (data.error == 1) {
            showMsg(data.errorMsg);
            return;
        }

        if ($(".menu-item-selected").parent().hasClass("menu-block")) {
            $(".menu-item-selected").parent().remove();
        } else {
            $(".menu-item-selected").remove();
        }

        settings.action = "";
    });
});

//закрывашка формы
$(".add-form-close").click(function() {
   $("#addForm").removeClass("add-form-show");
});

//отправка формы
$("#formSubmit").click(function() {
    if (settings.action == "addUp") {
        addMenuItem("up");
    } else if (settings.action == "addDown") {
        addMenuItem("down");
    } else if (settings.action == "addSub") {
        addMenuItem("sub");
    } else if (settings.action == "edit") {
        changeItem();
    } else {
        showMsg("Action не задан");
    }
});
