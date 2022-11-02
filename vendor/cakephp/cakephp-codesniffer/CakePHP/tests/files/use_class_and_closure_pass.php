<?php
namespace Beakman;

class TraitUser
{

    use FunctionsTrait;

    /**
     * [doThing description]
     *
     * @param callable $callback The description.
     * @return void
     */
    public function doThing(callable $callback)
    {
        $visitor = function ($expression) use (&$visitor, $callback) {
            echo 'It works';
        };
        $visitor($this);
    }
}
