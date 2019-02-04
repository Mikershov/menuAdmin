//загрузка меню
$.get("http://bgstat.ru/SGM_menu/api/menu.php?method=get&parentId=0", function( data ) {
    var menu = JSON.parse(data);
    var menuHtml = getMenuHtml(menu.data);
    //$("#mainMenuBlock")[0].innerHTML = menuHtml;
    //$("#menuLoader").hide();
});

//обработка результатов загрузки меню и формирование html
function getMenuHtml(data) {
    var menuHtml = '';
    console.log(data);

    if (data[0].parentId == 0) {
        menuHtml = '<ul class="menu dropdown" data-dropdown-menu>';
    } else {
        menuHtml = '<ul class="menu">';
    }


    data.forEach(function(item) {
        menuHtml += '<li><a href="'+item.url+'">'+item.name+'</a>';

        if (item.children) {
            menuHtml += getMenuHtml(item.children);
        }

        menuHtml += '</li>';
    });

    menuHtml += '</ul>';
    return menuHtml;
}
