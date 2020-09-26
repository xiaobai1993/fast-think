<?php
/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2019/7/23
 * Time: 下午3:57
 */

namespace app\common\command\code;

use app\common\lib\DTO;
use think\Loader;


/**
 * 代码生成器参数类
 * Class AutoCodeParam
 * @package app\common\command
 */
class AutoCodeParam extends DTO
{

    /**
     * 控制器名字
     * @var string
     */
    public $controllerName = '';

    /**
     * 模型名
     * @var string
     */
    public $modelName = '';

    /**
     * service名字
     * @var string
     */
    public $serviceName = '';

    /**
     * dto的名字
     * @var string
     */
    public $dtoName = '';

    /**
     * 验证器的名字
     * @var string
     */
    public $validateName = '';

    /**
     * 数据表的主键id
     * @var string
     */
    public $pkName = 'id';

    /**
     * 原始的sql
     * @var string
     */
    public $tableSQL = "";

    /**
     * 表名
     * @var string
     */
    public $tableName = "";

    /**
     * 手动输入用来创建类名用的
     * @var string
     */
    public $inputTableName = "";

    /**
     * 命名空间
     * @var string
     */
    protected $nameSpace = "app";

    /**
     * 模块名
     * @var string
     */
    public $module = "";

    /**
     * 数据表的描述
     * @var string
     */
    public $tableDesc = "";


    public function getServiceName()
    {
        return $this->serviceName ?: Loader::parseName($this->getInputTableName(), 1) . "Service";
    }


    public function getModelName()
    {
        return $this->modelName ?: Loader::parseName($this->getInputTableName(), 1) . "Model";
    }

    public function getInputTableName()
    {
        return $this->inputTableName ?: $this->tableName;
    }

    public function getControllerName()
    {
        return $this->controllerName ?: Loader::parseName($this->getInputTableName(), 1);
    }

    public function getValidateName()
    {
        return $this->validateName ?: Loader::parseName($this->getInputTableName(), 1) . "Validate";
    }

    public function getDtoName()
    {
        return $this->validateName ?: Loader::parseName($this->getInputTableName(), 1) . "Param";
    }

    public function getNameSpace()
    {
        return $this->nameSpace . "\\" . $this->module;
    }

    public function getBaseDirPath()
    {
        // APP_PATH
        return APP_PATH . $this->module . "/";
    }

    public function getModelFilePath()
    {
        return $this->autoMakeDir("model").$this->getModelName() . ".php";
    }

    public function getValidateFilePath()
    {
        return $this->autoMakeDir("validate").$this->getValidateName() . ".php";
    }

    public function getControllerFilePath()
    {
        return $this->autoMakeDir( "controller/admin").$this->getControllerName() . ".php";
    }

    public function getServiceFilePath()
    {
        return $this->autoMakeDir("service").$this->getServiceName() . ".php";
    }

    public function getDtoFilePath()
    {
        return $this->autoMakeDir("logic").$this->getDtoName() . ".php";
    }

    public function autoMakeDir($dirName)
    {
       $dir = $this->getBaseDirPath().$dirName."/";
       if (!is_dir($dir)){
           mkdir($dir,0755,true);
       }
       return $dir;
    }
}