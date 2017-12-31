<?php

namespace DHMO\xialotecon\provider;

use DHMO\xialotecon\MainClass;

abstract class BaseProvider implements Provider
{

    /** @var MainClass */
    protected $plugin;

    public function __construct(MainClass $plugin)
    {
        $this->plugin = $plugin;

        $this->initialize();
    }

    /**
     * @return bool
     */
    public function initialize(): bool
    {
        return false;
    }

    /**
     * @return MainClass
     */
    public function getPlugin(): MainClass
    {
        return $this->plugin;
    }
}
