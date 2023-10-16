<?php

namespace PrestaShop\PsAccountsInstaller\Tests;

use Faker\Generator;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Generator
     */
    public $faker;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->faker = \Faker\Factory::create();
    }
}
