<?php

/**
 *
 */
class XlsxMenuDataManager implements iMenuDataManageable
{
    private $fileData;
    private $zip;
    private $menu;
    private $menuFullPath;
    private $menuStrings;
    private $menuStringsFullPath;

    function __construct()
    {
        $this->fileData = Settings::getModeData('xlsx');
        $this->menuFullPath = '../data/'.$this->fileData->extractDirectory.$this->fileData->menuName;
        $this->menuStringsFullPath = '../data/'.$this->fileData->extractDirectory.
                                                $this->fileData->menuStringsName;

        if (!file_exists($this->menuFullPath)) {
            $this->zip = new ZipArchive;
            $this->zip->open('../data/'.$this->fileData->name);
            $this->zip->extractTo('../data/'.$this->fileData->extractDirectory);
            $this->zip->close();
        }

        $this->menu = simplexml_load_file($this->menuFullPath);
        $this->menuStrings = simplexml_load_file($this->menuStringsFullPath);
        $this->menu->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    }

    //служебные методы
    function getItemsByColValue($col, $value)
    {
        return $this->menu->xpath("*/m:row[m:c[contains(@r,'$col') and m:v=$value]]");
    }

    //выборка элемента меню по ID в основном файле
    private function getItemById($id)
    {
        return $this->getItemsByColValue('A', $id);
    }

    //выборка пунктов по родителю
    private function getItemsByParent($parentId)
    {
        return $this->getItemsByColValue('D', $parentId);
    }

    //выборка пунктов по parent и значению сортировки
    private function getItemsByParentSort($parentId, $sortValue)
    {
        return $this->menu->xpath("*/m:row[m:c[(contains(@r,'D') and m:v=$parentId)] and m:c[(contains(@r,'E') and m:v>=$sortValue)]]");
    }

    //выборка одного пункта по parent и значению сортировки
    private function getItemByParentSort($parentId, $sortValue)
    {
        return $this->menu->xpath("*/m:row[m:c[(contains(@r,'D') and m:v=$parentId)] and m:c[(contains(@r,'E') and m:v=$sortValue)]]");
    }

    //последний пункт меню в таблице
    private function getLastItem()
    {
        $itemsTotal = $this->menu->sheetData->row->count();
        if ($itemsTotal === 0) {
            return false;
        } else {
            return $this->menu->sheetData->row[$itemsTotal-1];
        }
    }

    //доступные извне методы
    public function getChildren($parentId, $recursively = true)
    {
        $menuData = Array();
        if (!$parentId) {
            $parentId = 0;
        }
        $rows = $this->getItemsByParent($parentId);

        usort($rows, function($a, $b) {
            return $a->c[4]->v - $b->c[4]->v;
        });

        foreach ($rows as $row) {
            $rowData = Array();
            foreach ($row as $cell) {
                $cellAtr = $cell->attributes();
                $cellName = '';
                switch (substr($cellAtr->r[0], 0, 1)) {
                    case 'A': $cellName = 'id'; break;
                    case 'B': $cellName = 'name'; break;
                    case 'C': $cellName = 'url'; break;
                    case 'D': $cellName = 'parentId'; break;
                    case 'E': $cellName = 'sort'; break;
                }

                if ($cellAtr->t == "s") {
                    $rowData[$cellName] = (string) $this->menuStrings->si[intval($cell->v)]->t;
                } else {
                    $rowData[$cellName] = (int) $cell->v;
                }
            }

            $rowId = $row->c[0]->v;
            $rowChildren = $this->menu->xpath("*/m:row[m:c[contains(@r,'D') and m:v=$rowId]]");
            $rowChildren = $this->getItemsByParent($rowId);

            if (count($rowChildren) > 0 && $recursively) {
                $rowData['children'] = $this->getChildren($rowId);
            } else {
                $rowData['children'] = 0;
            }

            $menuData[] = $rowData;
        }

        return $menuData;
    }

    public function get($parentId, $recursively = true)
    {
        //echo '<pre>';
        //print_r($this->menu);
        $menuData = $this->getChildren($parentId, $recursively = true);
        return Array('error'=>0, 'data'=>$menuData);
    }

    //создание
    public function add($parentId, $name, $url, $siblingId, $direction)
    {
        if (trim($name) == "" || trim($url) == "") {
            return Array('error'=>1, 'errorMsg'=>'Название или URL не могут быть пустыми');
        }

        $sibling = $this->getItemById($siblingId);

        if (count($this->getItemsByParent($parentId)) > 0) {
            //так как вставляем рядом с пунктом, то убедимся, что он есть
            if (!$sibling) {
                return Array('error'=>1, 'errorMsg'=>'Переданный ID соседа не существует');
            }

            //в том же подразделе
            if ($sibling[0]->c[3]->v != $parentId) {
                return Array('error'=>1, 'errorMsg'=>'Parent ID не совпадает');
            }


            if ($direction == 'up') { //вставка перед sibling
                $siblingSortValue = (int) $sibling[0]->c[4]->v;
            } else { //вставка после sibling
                $siblingSortValue = (int) $sibling[0]->c[4]->v + 1;
            }
        } else {
            $siblingSortValue = 1; //вставляем первый пункт у данного parent
        }

        //таблица строк
        $newItemRowString = $this->menuStrings->si->count();
        $this->menuStrings->addChild('si')->addChild('t', $name);
        $this->menuStrings->addChild('si')->addChild('t', $url);
        $menuStringsAtr = $this->menuStrings->attributes();
        $menuStringsAtr['count'] += 2;
        $menuStringsAtr['uniqueCount'] += 2;

        //основная таблица
        //обновление сортировки
        $toUpdateItems = $this->getItemsByParentSort($parentId, $siblingSortValue);
        foreach ($toUpdateItems as $item) {
            $item->c[4]->v = $item->c[4]->v + 1;
        }

        //вставить новые значения
        $lastItem = $this->getLastItem();
        if ($lastItem) { //если пункты в меню уже есть
            $newItemRow = $lastItem->attributes()['r'] + 1;
            $newItemId = $lastItem->c[0]->v + 1;
        } else { //если это вообще первый пункт в меню
            $newItemRow = 1;
            $newItemId = 1;
        }

        $newLine = $this->menu->sheetData->addChild('row');
        $newLine->addAttribute('r', $newItemRow);

        //пройти по столбцам и вставить общие для всех данные
        $colums = ['a','b','c','d','e'];
        foreach ($colums as $colIndex => $colValue) {
            $newCell[$colIndex] = $newLine->addChild('c');
            $newCell[$colIndex]->addAttribute('r', strtoupper($colValue).$newItemId);
            $newCell[$colIndex]->addAttribute('s', 1);
        }

        $newCell[0]->addChild('v', $newItemId);
        $newCell[1]->addAttribute('t', 's');
        $newCell[1]->addChild('v', $newItemRowString);
        $newCell[2]->addAttribute('t', 's');
        $newCell[2]->addChild('v', $newItemRowString + 1);
        $newCell[3]->addChild('v', $parentId);
        $newCell[4]->addChild('v', $siblingSortValue);

        $this->save();

        return Array(
            'error'=>0,
            'id'=>$newItemId,
            'name'=>$name,
            'url'=>$url,
            'parentId'=>$parentId,
            'sort'=>$siblingSortValue,
            'child'=>0
        );
    }

    //удаление
    public function delete($itemId)
    {
        if (!$this->getItemById($itemId)) {
            return Array('error'=>1, 'errorMsg'=>'Элемент с ID='.$itemId.' не существует');
        }

        //если у пункта есть подпункты, то удаляем рекурсивно сначала их
        $children = $this->getItemsByParent($itemId);
        if (count($children) > 0) {
            foreach($children as $child) {
                $this->delete($child->c[0]->v);
            }
        }

        $item = $this->getItemById($itemId)[0];
        $itemRow = $item->attributes()['r'];
        $itemSortValue = $item->c[4]->v;
        $itemParent = $item->c[3]->v;
        $itemNameIndex = $item->c[1]->v;
        $itemUrlIndex = $item->c[2]->v;

        //обновление сортировки нижележащих пунктов у parent
        $toUpdateItemsSort = $this->getItemsByParentSort($itemParent, $itemSortValue+1);
        foreach($toUpdateItemsSort as $itemSort) {
            $itemSort->c[4]->v -= 1;
        }

        //удаление текста name и url из $menuStrings
        unset($this->menuStrings->si[intval($itemNameIndex)]);
        //так как удаление идет по индексу, весь массив смещается
        //и получается, что удаляем по одному и тому же индексу
        unset($this->menuStrings->si[intval($itemNameIndex)]);
        //обновление атрибутов
        $this->menuStrings->attributes()['count'] -= 2;
        $this->menuStrings->attributes()['uniqueCount'] -= 2;

        //обновление числовых ссылок на B-name и C-url
        $toUpdateItemsString = $this->menu->xpath("*/m:row[m:c[contains(@r,'B') and m:v>$itemNameIndex]]");
        foreach ($toUpdateItemsString as $itemString) {
          $itemString->c[1]->v -= 2;
          $itemString->c[2]->v -= 2;
        }

        //обновление атрибута r у нижележащих row
        $toUpdateRows = $this->menu->xpath("*/m:row[@r > $itemRow]");
        foreach ($toUpdateRows as $row) {
          $row->attributes()['r'] -= 1;
        }

        //удалить сам элемент меню
        unset($item[0]);
        $this->save();

        return Array('error'=>0, 'id'=>$itemId);
    }

    //обновление
    public function change($itemId, $name, $url)
    {
        if (trim($name) == "" || trim($url) == "") {
            return Array('error'=>1, 'errorMsg'=>'Название или URL не могут быть пустыми');
        }

        $menuItem = $this->getItemById($itemId);

        $this->menuStrings->si[intval($menuItem[0]->c[1]->v)]->t = $name;
        $this->menuStrings->si[intval($menuItem[0]->c[2]->v)]->t = $url;

        $this->save();

        return Array('error'=>0, 'id'=>$itemId, 'name'=>$name, 'url'=>$url);
    }

    //смещение
    public function move($itemId, $direction)
    {
        $item = $this->getItemById($itemId);
        if (count($item) == 0) {
            return Array('error'=>1, 'errorMsg'=>'Элемент не найден');
        }

        //данные по элементу
        $item = $item[0];
        $itemParent = $item->c[3]->v;
        $itemSortValue = (int) $item->c[4]->v;
        $itemSiblingsCount = count($this->getItemsByParent($itemParent));

        //проверка на упоры в смещении
        //первый вверх, последний вниз
        if ($direction == 'up' && $itemSortValue == 1) {
            return Array('error'=>1, 'errorMsg'=>'Пунт уже первый');
        } elseif ($direction == 'down' && $itemSortValue == $itemSiblingsCount) {
            return Array('error'=>1, 'errorMsg'=>'Пунт уже последний');
        }

        //данные по соседу
        if ($direction == 'down') {
            $sibling = $this->getItemByParentSort($itemParent, $itemSortValue + 1)[0];
            $sort = $itemSortValue + 1;
        } else {
            $sibling = $this->getItemByParentSort($itemParent, $itemSortValue - 1)[0];
            $sort = $itemSortValue - 1;
        }

        $item->c[4]->v =  $sibling->c[4]->v;
        $sibling->c[4]->v = $itemSortValue;

        $this->save();

        return Array('error'=>0, 'id'=>$itemId, 'sort'=>$sort, 'direction'=>$direction);
    }

    public function save()
    {
        $this->menu->saveXML($this->menuFullPath);
        $this->menuStrings->saveXML($this->menuStringsFullPath);

        //формируем html для паблика
        $data = $this->getChildren(0);

        function dataToHtml($data) {
            $html = '';

            if ($data[0]['parentId'] == 0) {
                $html .= '<ul class="menu dropdown" data-dropdown-menu>';
            } else {
                $html .= '<ul class="menu">';
            }

            foreach($data as $item) {
                $html .= '<li>';
                $html .= '<a href="'.$item['url'].'">'.$item['name'].'</a>';

                if ($item['children'] != 0) {
                    $html .= dataToHtml($item['children']);
                }

                $html .= '</li>';
            }

            $html .= '</ul>';
            return $html;
        }

        $html = dataToHtml($data);

        file_put_contents('../public_html/xlsxMenu.html', $html);
    }
}
