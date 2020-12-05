<?php
/**
 * Created by PhpStorm.
 * User: junchow
 * Date: 2020/12/5
 * Time: 17:42
 */
declare(strict_types=1);

namespace arhan;


class Error
{
    /**
     * 注册错误和异常处理
     * @access public
     * @return void
    */
    public function register():void
    {
        //报告所有PHP错误
        error_reporting(E_ALL);
        //设置自定义错误处理器
        set_error_handler([$this, "errorHandler"]);
        register_shutdown_function([$this, "shutdownHandler"]);

        //set_exception_handler([$this, "exceptionHandler"]);
    }
    /**
     * 错误处理器
     * 将错误托管到ErrorException
     * @access public
     * @param integer $errno 错误编号
     * @param string $errmsg 错误信息
     * @param string $errfile 出错文件
     * @param integer $errline 出错行号
     * @throws ErrorException
    */
    public function errorHandler(int $errno, string $errstr, string $errfile="", int $errline=0):void
    {
        //创建错误异常对象
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        //若错误报告开启且当前错误编码不为空则托管
        if(error_reporting() && $errno){
            throw $exception;//抛出异常
        }
    }
}