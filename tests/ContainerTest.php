<?php
declare(strict_types=1);

namespace arhan\tests;

use arhan\Container;
use PHPUnit\Framework\TestCase;

class Taylor
{
    public $name;
    public function __construct($name="")
    {
        $this->name = $name;
    }
}

final class ContainerTest extends TestCase
{
    public function testClosureResolution(){
        $container = new Container();
        Container::setInstance($container);
        $container->register("name", function(){
            return "Taylor";
        });
        $this->assertEquals("Taylor", $container->create("name"));
    }
}