<?php
declare(strict_types=1);

namespace arhan;

use arhan\exception\ClassNotFoundException;
use arhan\exception\FunctionNotFoundException;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;

/**
 * IoC容器管理类
 * 支持PSR-11
*/
class Container
{
    /**
     * 容器对象实例
     * 单例模式
     * @var Container|Closure
     */
    protected static $instance;
    
    /**
     * 容器中已注册服务列表
     * 单例对象列表
     * @var array
     */
    protected $instances = [];
    /**
     * 容器中已注册服务列表
     * 容器绑定标识或类名，支持别名。
     * @var array
     */
    protected $bindings = [];

    /**
     * 获取当前容器单例
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        //判断单例是否为空对象
        if(!is_null(static::$instance)){
            static::$instance = new static;
        }
        //判断单例是否为闭包
        if(static::$instance instanceof Closure){
            return (static::$instance)();
        }
        return static::$instance;
    }

    /**
     * 设置当前容器单例
     * @access public
     * @param object|Closure $instance
     * @return void
     */
    public static function setInstance($instance):void
    {
        static::$instance = $instance;
    }

    /**
     * 判断容器中是否存在已绑定的服务
     * @access public
     * @param string $abstract 服务名称 类名或标识
     * @return bool
     */
    public function has(string $abstract):bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * 注册服务
     * 绑定服务到容器
     * 服务支持类名、闭包、实例、接口实现
     * @access public
     * @param string|array $abstract 服务名称 类名标识、接口，支持数组用于配置别名。
     * @param mixed $concrete 服务主体 要绑定的类、闭包、实例
     * @return $this
     */
    public function register($abstract, $concrete=null)
    {
        if(is_array($abstract)){
            foreach($abstract as $key=>$val){
                $this->register($key, $val);
            }
        }elseif($concrete instanceof Closure){
            $this->bindings[$abstract] = $concrete;
        }elseif(is_object($concrete)){
            $this->bind($abstract, $concrete);
        }else{
            $abstract = $this->alias($abstract);
            if($abstract != $concrete){
                $this->bindings[$abstract] = $concrete;
            }
        }
        return $this;
    }

    /**
     * 从绑定标识列表中根据别名获取真实类名
     * @param string $abstract 服务名称/服务别名
     * @return string 真实类名
     */
    public function alias(string $abstract):string
    {
        if(isset($this->bindings[$abstract])){
            $bind = $this->bindings[$abstract];
            if(is_string($bind)){
                return $this->alias($bind);
            }
        }
        return $abstract;
    }

    /**
     * 绑定类实例到容器
     * @access public
     * @param string $abstract 服务名称/服务别名
     * @param object $instance 服务实体/实例对象
     * @return $this
     */
    public function bind(string $abstract, $instance)
    {
        $abstract = $this->alias($abstract);
        $this->instances[$abstract] = $instance;
        return $this;
    }

    /**
     * 从容器中创建实例
     * 若容器的实例列表中已存在则直接获取单例
     * @access public
     * @param string $abstract 服务名称 标识或类名
     * @param array $vars 创建对象时需传入的参数列表
     * @param bool $shared 是否共享对象，即是否每次都创建新的实例。
     * @return mixed
     */
    public function create(string $abstract, array $vars=[], bool $shared=false)
    {
        //获取服务名称对应的真实类名
        $abstract = $this->alias($abstract);
        //若容器的实例列表中已存在则直接获取单例
        if(isset($this->instances[$abstract]) && !$shared){
            return $this->instances[$abstract];
        }
        //若服务为闭包则调用匿名回调函数以创建对象
        //否则通过类名使用反射创建对象
        if(isset($this->bindings[$abstract]) && $this->bindings[$abstract] instanceof Closure){
            $object = $this->invokeFunction($abstract, $vars);
        }else{
            $object = $this->invokeClass($abstract, $vars);
        }
        //共享服务则将对象添加到单例列表，避免每次都需要创建新的实例。
        if(!$shared){
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * 执行函数或闭包方法以创建实例
     * 支持参数调用
     * @access public
     * @param string|Closure $function 函数或闭包
     * @param array $vars 参数列表
     * @return mixed
     */
    public function invokeFunction($function, $vars=[])
    {
        try{
            $reflect = new ReflectionFunction($function);
        }catch(ReflectionException $e){
            throw new FunctionNotFoundException("function not exists: {$function}()", $function, $e);
        }
        $args = $this->bindParams($reflect, $vars);
        return $function(...$args);
    }

    /**
     * 调用反射执行类的实例化
     * 支持依赖注入
     * @access public
     * @param string $classname 类名
     * @param array $vars 参数列表
     * @return mixed
     */
    public function invokeClass(string $classname, array $vars=[])
    {
        try{
            $reflect = new ReflectionClass($classname);
        }catch(ReflectionException $e){
            throw new ClassNotFoundException("class not exists: {$classname}", $classname, $e);
        }

        if($reflect->hasMethod("__create")){
            $method = $reflect->getMethod("__create");
            if($method->isPublic() && $method->isStatic()){
                $args = $this->bindParams($method, $vars);
                return $method->invokeArgs(null, $args);
            }
        }

        $constructor = $reflect->getConstructor();
        $args = $constructor?$this->bindParams($constructor, $vars):[];
        $object = $reflect->newInstanceArgs($args);

        return $object;
    }

    /**
     * 绑定参数
     * @access protected
     * @param ReflectionFunctionAbstract $reflect 反射类
     * @param array $vars 参数
     * @return array
     */
    public function bindParams(ReflectionFunctionAbstract $reflect, array $vars=[]):array
    {
        $args = [];
        if($reflect->getNumberOfParameters() == 0){
            return [];
        }
        reset($vars);

        //判断数组类型 索引数字数组按顺序绑定参数
        $type = key($vars)===0 ? 1 : 0;//1索引数组 0关联数组

        //获取方法参数列表
        $params = $reflect->getParameters();
        foreach($params as $param){
            $name = $param->getName();
            $class = $param->getClass();//参数是否为类
            if($class){
                //处理依赖 获取对象类型的参数值
                $classname = $class->getName();
                $args[] = $this->getObjectParam($classname, $vars);
            }elseif($type==1 && !empty($vars)){
                //索引数组
                $args[] = array_shift($vars);
            }elseif($type==0 && array_key_exists($name, $vars)){
                //关联数组
                $args[] = $vars[$name];
            }elseif($param->isDefaultValueAvailable()){
                //获取参数默认值
                $args[] = $param->getDefaultValue();
            }else{
                throw new InvalidArgumentException("method parameter miss : {$name}");
            }
        }
        return $args;
    }

    /**
     * 获取对象类型的参数值
     * @access protected
     * @param string $classname 类名
     * @param array $vars 参数
     * @return mixed
     */
    protected function getObjectParam(string $classname, array &$vars)
    {
        $array = $vars;
        $value = array_shift($array);
        if($value instanceof $classname){
            $result = $value;
            array_shift($vars);
        }else{
            $result = $this->create($classname);//使用类名创建对象
        }
        return $result;
    }
}