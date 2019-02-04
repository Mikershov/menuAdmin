<?php

interface iMenuDataManageable
{
    public function get($parentId, $recursively = true);

    public function add($parentId, $name, $url, $siblingId, $direction);

    public function delete($itemId);

    public function change($itemId, $name, $url);

    public function move($itemId, $direction);

    public function save();
}
