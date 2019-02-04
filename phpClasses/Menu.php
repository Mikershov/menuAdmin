<?php
/**
 * По сути класс фабрика, которая выдает объекты для манипуляцией данными меню исходя из настроек
 * Но при этом содержит логику по конвертации данных в нужный формат для вывода в апи
 */
class Menu implements iMenuDataManageable
{
    private $dataManager;

    function __construct()
    {
        $dataManagerClassName = ucfirst(Settings::getWorkMode()).'MenuDataManager';
        $this->dataManager = new $dataManagerClassName();
    }

    private function dataTransformation($data)
    {
        $answerMode = Settings::getAnswerMode();

        if ($answerMode == "native") {
            return $data;
        } elseif ($answerMode == "json") {
            return json_encode($data);
        } elseif ($answerMode == "xml") {
            //не сейчас
        }
    }

    public function get($parentId, $recursively = true)
    {
        $data = $this->dataManager->get($parentId);
        return $this->dataTransformation($data);
    }

    public function add($parentId, $name, $url, $siblingId, $direction)
    {
        $data = $this->dataManager->add($parentId, $name, $url, $siblingId, $direction);
        return $this->dataTransformation($data);
    }

    public function delete($itemId)
    {
        $data = $this->dataManager->delete($itemId);
        return $this->dataTransformation($data);
    }

    public function change($itemId, $name, $url)
    {
        $data = $this->dataManager->change($itemId, $name, $url);
        return $this->dataTransformation($data);
    }

    public function move($itemId, $direction)
    {
        $data = $this->dataManager->move($itemId, $direction);
        return $this->dataTransformation($data);
    }

    public function save()
    {
        return $this->dataManager->save();
    }
}
