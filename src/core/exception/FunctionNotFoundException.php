<?php
/**
 * Created by PhpStorm.
 * User: junchow
 * Date: 2020/11/29
 * Time: 20:17
 */

namespace arhan\exception;


use RuntimeException;
use Throwable;

class FunctionNotFoundException extends RuntimeException
{
    protected $functionName;

    public function __construct(string $message = "", string $functionName = "", Throwable $previous = null)
    {
        $this->message = $message;
        $this->functionName = $functionName;

        $code = 0;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取函数名
     * @access public
     * @return string
     */
    public function getFunction()
    {
        return $this->functionName;
    }
}