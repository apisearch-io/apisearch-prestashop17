<?php

namespace Apisearch\Rates;

class Rate
{
    private $rate;
    private $nb;

    /**
     * @param $rate
     * @param $nb
     */
    public function __construct($rate, $nb)
    {
        $this->rate = $rate;
        $this->nb = $nb;
    }

    /**
     * @return mixed
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @return mixed
     */
    public function getNb()
    {
        return $this->nb;
    }
}