<?php
class FirstClass
{
    private $n;

    function __construct($n)
    {
        $this->n = $n;
    }

    public function getN()
    {
        return $this->n;
    }

    public function setN($n)
    {
        $this->n = $n;
    }
}
