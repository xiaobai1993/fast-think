<?php
/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2020/4/2
 * Time: 下午2:35
 */

namespace xiaobai\think\command;


use think\App;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Loader;
use think\Model;

class ModelProperty extends Command
{
    protected static $tabs = "   ";

    protected function configure()
    {
        $this->setName('amp')
            ->addArgument('model', Argument::OPTIONAL, "模型的名字")
            ->addOption('override', null, Option::VALUE_OPTIONAL, '是否强制覆盖')
            ->setDescription('模型自动增加属性注释');
    }

    protected function execute(Input $input, Output $output)
    {
        $modelPath = $input->getArgument('model');
        if (defined('APP_PATH')){
            $path = APP_PATH . $modelPath;
        }else{
            $path = \think\App::getInstance()->getAppPath() . $modelPath;
        }
        if (is_dir($path)) {
            foreach (scandir($path) as $value) {
                if ($value == '.' || $value == '..') {
                    continue;
                }
                $filePath = $path . "/" . $value;
                if (is_file($filePath)) {
                    try{
                        $this->parseSingleFile($filePath);
                    }catch (\Exception $exception){
                        echo  $exception->getMessage();
                    }
                } else {
                    continue;//目录嵌套暂时不处理
                }
            }
        }elseif (is_file($path)){
            $this->parseSingleFile($path);
        }else{
            exception("$path 文件不存在");
        }
    }


    public function parseSingleFile($filePath)
    {
        $fileContent = file_get_contents($filePath);
        if (preg_match('/namespace (.*?);/', $fileContent, $spaceMatch)) {
            $spaceName = $spaceMatch[1];
            if (preg_match('/class (.*?) extends .*?Model/', $fileContent, $classMatch)) {
                $className = $classMatch[1];
                $class = $spaceName . "\\" . $className;
                if (class_exists($class)) {
                    $instance = new $class();
                    if ($instance instanceof Model) {
                        $comments = [];
                        $this->parseTableAttr($instance, $comments);
                        $this->parseClass($instance, $comments);
                        $classComments = "\n\n/**\n" . implode("\n", $comments) . "\n*/\n\n";
                        $result = preg_replace('/^([\s\S]*;)([\s\S]*?)(class.*?extends[\s\S]*)$/', "$1$classComments$3", $fileContent);
                        file_put_contents($filePath, $result);
                    } else {
                        exception("$class 不是模型类");
                    }
                } else {
                    exception("$class 不存在");
                }
            } else {
                exception("未能找到" . basename($filePath) . "类的名字");
            }

        } else {
            exception("未能找到" . basename($filePath) . "类的命名空间");
        }
    }

    /**
     * 扫码数据表的属性
     * @param Model $model
     * @param $comments
     * @return string
     */
    protected function parseTableAttr(Model $model, &$comments)
    {
        $tableSql = $model->query("show create table " . $model->getTable())[0]['Create Table'];
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $tableSql, $matches);
        $fields = $matches[1];
        $cts = $matches[3];
        if (preg_match('/COMMENT=\'(.*?)\'$/', $tableSql, $m2)) {
            $comments[] = " * " . $m2[1];
        }
        for ($i = 0; $i < count($matches[0]); $i++) {
            $comments[] = " * @property $" . $fields[$i] . self::$tabs . $cts[$i];
        }
    }

    /**
     * 获取模型文件的方法
     * @param $model
     * @param $comments
     */
    protected function parseClass($model, &$comments)
    {
        $classReflect = new \ReflectionClass($model);
        $filePath = $classReflect->getFileName();
        if ($filePath) {
            $content = file_get_contents($filePath);
        } else {
            $content = "";
        }
        $methods = $classReflect->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->isAbstract() || $method->isStatic()) {
                continue;
            }
            $methodName = $method->getName();
            //只查询本类文件存在的
            if ($content && strpos($content, "function " . $methodName)) {
                if (preg_match('/get(.*?)Attr/', $methodName, $match)) { //属性
                    $propertyName = Loader::parseName($match[1], 0, false);
                    $comments[] = " * @property $" . $propertyName . self::$tabs . $this->getDocTitle($method->getDocComment());
                } else {
                    $startLine = $method->getStartLine();
                    $endLine = $method->getEndLine();
                    $methodContent = $this->readFile($filePath, $startLine, $endLine);
                    if (preg_match('/return.*?->(.*?)\([,]?(.*?)::class,/', $methodContent, $match)) {
                        $relation = $match[1];
                        $relationModel = $match[2];
                        $propertyName = Loader::parseName($methodName, 0, false);
                        if ($relation == 'hasMany' || $relation == 'belongsToMany') {
                            $comments[] = " * @property $" . $relationModel . "[]" . self::$tabs . $propertyName . self::$tabs . $this->getDocTitle($method->getDocComment());
                        } else {
                            $comments[] = " * @property $" .  $relationModel . self::$tabs .$propertyName . self::$tabs . $this->getDocTitle($method->getDocComment());
                        }
                    }
                }
            } else {
                continue;
            }
        }
    }

    /**
     * 读文件
     * @param $file_name
     * @param $start
     * @param $end
     * @return string
     */
    protected function readFile($file_name, $start, $end)
    {
        $limit = $end - $start;
        $f = new \SplFileObject($file_name, 'r');
        $f->seek($start);
        $ret = "";
        for ($i = 0; $i < $limit; $i++) {
            $ret .= $f->current();
            $f->next();
        }
        return $ret;
    }

    /**
     * 获取类或者方法注释的标题，第一行
     * @param $docComment
     * @return string
     */
    protected function getDocTitle($docComment)
    {
        if ($docComment !== false) {
            $docCommentArr = explode("\n", $docComment);
            $comment = trim($docCommentArr[1]);
            return trim(substr($comment, strpos($comment, '*') + 1));
        }
        return '';
    }

}