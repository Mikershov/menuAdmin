<?php
/**
 *
 */
class MenuApiManager
{
    private $menu;

    function __construct()
    {
        $this->menu = new Menu();
    }

    function requestController($get, $post)
    {
        switch ($get['method']) {
            case 'get':
                echo $this->menu->get($get['parentId']);
                break;
            case 'add':
                echo $this->menu->add(
                    $post['parentId'],
                    $post['name'],
                    $post['url'],
                    $post['siblingId'],
                    $post['direction']
                );
                break;
            case 'change':
                echo $this->menu->change($post['itemId'], $post['name'], $post['url']);
                break;
            case 'delete':
                echo $this->menu->delete($post['itemId']);
                break;
            case 'move':
                echo $this->menu->move($post['itemId'], $post['direction']);
                break;
        }
    }
}
