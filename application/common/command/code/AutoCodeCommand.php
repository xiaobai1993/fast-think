<?php

namespace app\common\command\code;

/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2019/7/23
 * Time: 下午1:35
 */
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;


class AutoCodeCommand extends Command
{
    public function parseTableSql(): AutoCodeParam
    {
        $file = ROOT_PATH . 'auto_code.sql';
        if (!is_file($file)) {
            exception("文件不存在");
        }
        $tableSQL = file_get_contents(ROOT_PATH . 'auto_code.sql');
        if (empty($tableSQL)) {
            exception("SQL不存在无法创建");
        }
        $this->checkSQL($tableSQL);
        $autoCodeParam = new AutoCodeParam();
        $autoCodeParam->tableSQL = $tableSQL;

        //匹配表的名字
        //CREATE TABLE `dbase_banner`
        if (preg_match('/^(.*?CREATE\s*TABLE\s*)`(.*?)`.*\(/', $tableSQL, $m)) {
            $autoCodeParam->tableName = $m[2];
        } else {
            exception("没有扫描到表的名字");
        }
        //扫描主键，没有的扫描到用id
        if (preg_match('/PRIMARY\s*KEY\s*\(`(.*?)`\)/', $tableSQL, $m1)) {
            $autoCodeParam->pkName = $m1[1];
        }
        if (preg_match('/COMMENT=\'(.*?)\';$/', $tableSQL, $m2)) {
            $autoCodeParam->tableDesc = $m2[1];
        }
        return $autoCodeParam;
    }

    protected function checkSQL($tableSQL)
    {
        //检查是否满足条件，必须全部加注释
        preg_match_all('/^\s*`.*/m', $tableSQL, $lines);
        preg_match_all('/^\s*`.*?COMMENT.*?/m', $tableSQL, $comments);
        if (count($lines) != count($comments)) {
            exception("SQL创建表有些字段缺少注释，不能生成");
        }
        return true;
    }

    protected function checkFile(AutoCodeParam $param)
    {

        //检查文件是否存在
        return true;
    }


    /**
     * 生成接口传参文档
     * @param AutoCodeParam $param
     * @return string
     */
    public static function createDoc(AutoCodeParam $param)
    {
        $tableSql = $param->tableSQL;
        $matches = [];
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $tableSql, $matches);
        $fields = $matches[1];
        $types = $matches[2];
        $comments = $matches[3];
        $doc = "参数名称 | 参数类型 | 是否必填 | 说明
:--- | :---: | :---: | :---
";
        for ($i = 0; $i < count($matches[0]); $i++) {
            $field = $fields[$i];
            $type = strpos($types[$i], 'int') !== false ? 'Number' : 'String';
            $must = "N";
            $comment = $comments[$i];
            $doc .= $field . " | " . $type . " | " . $must . " | " . $comment . "\n";

        }
        return $doc;
    }


    /**
     * 生成服务器返回结果文档
     * @param AutoCodeParam $param
     * @return string
     */
    public static function createResDoc(AutoCodeParam $param)
    {
        $tableSql = $param->tableSQL;
        $matches = [];
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $tableSql, $matches);
        $fields = $matches[1];
        $types = $matches[2];
        $comments = $matches[3];
        $doc = "参数名称 | 参数类型  | 说明
:--- | :---: | :---
";
        for ($i = 0; $i < count($matches[0]); $i++) {
            $field = $fields[$i];
            $type = strpos($types[$i], 'int') !== false ? 'Number' : 'String';

            $comment = $comments[$i];
            $doc .= $field . " | " . $type . " | " . $comment . "\n";

        }
        return $doc;
    }


    public static function createDTO(AutoCodeParam $param)
    {
        $templateFile = __DIR__ . "/templates/DTO.html";
        if (!is_file($templateFile)) {
            exception("DTO模板文件不存在");
        }
        $tableSql = $param->tableSQL;
        $nameSpace = $param->getNameSpace() . "\\logic";
        $matches = [];
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $tableSql, $matches);
        $defaults = $matches[2];
        $fields = $matches[1];
        $comments = $matches[3];

        $classBody = "\n";
        $tabs = "   ";
        for ($i = 0; $i < count($matches[0]); $i++) {

            $docComment = "$tabs/**\n";
            $comment = $comments[$i];
            //注释
            if (!empty($comment)) {
                $docComment .= "$tabs * {$comment}\n";
            }
            $docComment .= "$tabs * @var ";
            //数据类型
            if (strpos($defaults[$i], 'char') !== false || strpos($defaults[$i], 'text') !== false) {
                $type = "string";
            } elseif (strpos($defaults[$i], 'int') !== false) {
                $type = "int";
            } else {
                $type = '';
            }
            $docComment .= $type . "\n" . "$tabs */\n";

            $field = "{$tabs}public $" . $fields[$i] . ";\n";
            $classBody .= $docComment . $field;
        }

        $varMaps = [
            "nameSpace" => $nameSpace,
            'className' => $param->getDtoName(),
            'classBody' => $classBody,
            'time' => date("H:i:s"),
            'date' => date("Y/m/d")
        ];

        $content = file_get_contents($templateFile);
        $content = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($varMaps) {
            return $varMaps[$matches[1]]??"模板未定义";
        }, $content);
        return $content;
    }


    /**
     * 创建模型
     * @param AutoCodeParam $param
     * @return bool|mixed|string
     */
    public static function createModel(AutoCodeParam $param)
    {
        $templateFile = __DIR__ . "/templates/Model.html";
        if (!is_file($templateFile)) {
            exception("模型模板文件不存在");
        }
        $nameSpace = $param->getNameSpace() . "\\model";
        $propertyDoc = self::createPropertyDoc($param);
        $varMaps = [
            "nameSpace" => $nameSpace,
            'className' => $param->getModelName(),
            'tableName' => $param->tableName,
            'pk' => $param->pkName,
            'time' => date("H:i:s"),
            'date' => date("Y/m/d"),
            'varComment' => $propertyDoc
        ];
        $content = file_get_contents($templateFile);
        $content = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($varMaps) {
            return $varMaps[$matches[1]]??"模板未定义";
        }, $content);
        return $content;
    }


    /**
     * 创建验证器
     * @param AutoCodeParam $param
     * @return bool|mixed|string
     */
    public static function createValidate(AutoCodeParam $param)
    {
        $templateFile = __DIR__ . "/templates/Validate.html";
        if (!is_file($templateFile)) {
            exception("模型模板文件不存在");
        }

        $tabs = "        ";
        $nameSpace = $param->getNameSpace() . "\\validate";
        $matches = [];
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $param->tableSQL, $matches);
        $fields = $matches[1];
        $comments = $matches[3];
        $doc = "";
        for ($i = 0; $i < count($matches[0]); $i++) {
            $field = $fields[$i];
            $comment = $comments[$i];
            $doc .= $tabs . "'$field|$comment'" . " => 'require'," . "\n";
        }
        $varMaps = [
            "nameSpace" => $nameSpace,
            'className' => $param->getValidateName(),
            'tableName' => $param->tableName,
            'pk' => $param->pkName,
            'time' => date("H:i:s"),
            'date' => date("Y/m/d"),
            'rules' => $doc,
            'param_desc' => self::createDoc($param),
            'controller' => $param->getControllerName(),
            'module' => $param->module,
            'tableDesc' => $param->tableDesc
        ];

        $content = file_get_contents($templateFile);
        $content = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($varMaps) {
            return $varMaps[$matches[1]]??"模板未定义";
        }, $content);
        return $content;
    }

    /**
     * 创建控制器
     * @param AutoCodeParam $param
     * @return bool|mixed|string
     */
    public static function createController(AutoCodeParam $param)
    {
        $templateFile = __DIR__ . "/templates/Controller.html";
        if (!is_file($templateFile)) {
            exception("控制器模板文件不存在");
        }

        $nameSpace = $param->getNameSpace() . "\\controller\\admin";
        $varMaps = [
            "nameSpace" => $nameSpace,
            'className' => $param->getControllerName(),
            'tableDesc' => $param->tableDesc,
            'tableName' => $param->tableName,
            'pk' => $param->pkName,
            'time' => date("H:i:s"),
            'date' => date("Y/m/d"),
            'validateNameSpace' => $param->getNameSpace() . "\\validate\\" . $param->getValidateName(),
            'dtoNameSpace' => $param->getNameSpace() . "\\logic\\" . $param->getDtoName(),
            'serviceNameSpace' => $param->getNameSpace() . "\\service\\" . $param->getServiceName(),
            'serviceClassName' => $param->getServiceName(),
            'validateClassName' => $param->getValidateName(),
            'dtoClassName' => $param->getDtoName(),
        ];
        $content = file_get_contents($templateFile);
        $content = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($varMaps) {
            return $varMaps[$matches[1]]??"模板未定义";
        }, $content);
        return $content;
    }


    /**
     * 创建service
     * @param AutoCodeParam $param
     * @return bool|mixed|string
     */
    public static function createService(AutoCodeParam $param)
    {
        $templateFile = __DIR__ . "/templates/Service.html";
        if (!is_file($templateFile)) {
            exception("控制器模板文件不存在");
        }
        $nameSpace = $param->getNameSpace() . "\\service";
        $varMaps = [
            "nameSpace" => $nameSpace,
            'className' => $param->getServiceName(),
            'tableDesc' => $param->tableDesc,
            'time' => date("H:i:s"),
            'date' => date("Y/m/d"),
            'dtoNameSpace' => $param->getNameSpace() . "\\logic\\" . $param->getDtoName(),
            'modelNameSpace' => $param->getNameSpace() . "\\model\\" . $param->getModelName(),
            'serviceClassName' => $param->getServiceName(),
            'dtoClassName' => $param->getDtoName(),
            'modelClassName' => $param->getModelName(),
        ];
        $content = file_get_contents($templateFile);
        $content = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($varMaps) {
            return $varMaps[$matches[1]]??"模板未定义";
        }, $content);
        return $content;
    }


    protected function configure()
    {
        $this->setName('auto_code')
            ->addArgument('module', Argument::OPTIONAL, "模块名")
            ->addOption('model', null, Option::VALUE_REQUIRED, '模型的名字，不传用数据表名')
            ->setDescription('代码自动生成器');
    }

    protected function execute(Input $input, Output $output)
    {
        $param = $this->parseTableSql();
        $module = trim($input->getArgument('module'));
        if (!$module) {
            exception("请输入模块名，以便确定生成文件的存放目录");
        }
        if ($input->hasOption('model')) {
            $param->inputTableName = $input->getOption('model');
        }

        $param->module = $module;
        $dto = AutoCodeCommand::createDTO($param);
        $model = AutoCodeCommand::createModel($param);
        $validate = AutoCodeCommand::createValidate($param);
        $controller = AutoCodeCommand::createController($param);
        $service = AutoCodeCommand::createService($param);

        !is_file($param->getDtoFilePath()) ? file_put_contents($param->getDtoFilePath(), $dto) : $output->writeln("DTO文件已存在，没有重新生成");
        !is_file($param->getModeFilePath()) ? file_put_contents($param->getModeFilePath(), $model) : $output->writeln("Model文件已存在，没有重新生成");
        !is_file($param->getValidateFilePath()) ? file_put_contents($param->getValidateFilePath(), $validate) : $output->writeln("Validate文件已存在，没有重新生成");
        !is_file($param->getControllerFilePath()) ? file_put_contents($param->getControllerFilePath(), $controller) : $output->writeln("Controller文件已存在，没有重新生成");
        !is_file($param->getServiceFilePath()) ? file_put_contents($param->getServiceFilePath(), $service) : $output->writeln("Service文件已存在，没有重新生成");

        $output->writeln("基本代码已经自动生成，祝你工作愉快！");
    }

    public static function createPropertyDoc(AutoCodeParam $param)
    {
        $tableSql = $param->tableSQL;
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $tableSql, $matches);
        $fields = $matches[1];
        $comments = $matches[3];
        $result = [];
        $tabs = "   ";
        for ($i = 0; $i < count($matches[0]); $i++) {
            $result[] = " * @property $".$fields[$i]."$tabs".$comments[$i];
        }
        return "\n/**\n".implode("\n",$result)."\n*/\n";
    }
}