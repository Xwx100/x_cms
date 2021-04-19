<?php
declare (strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\db\Query;
use think\facade\Db;
use think\Model;

class ModelProp extends Command
{
    protected $name = 'make:model_prop';

    protected function configure()
    {
        // 指令配置
        $this->setName($this->name)
            ->addArgument('connect_name', Argument::OPTIONAL, "数据库连接配置")
            ->addOption('output_dir', null, Option::VALUE_REQUIRED, "root输出目录", 'app/model_prop')
            ->setDescription('制作数组表格');
    }


    protected function execute(Input $input, Output $output)
    {
        $name      = $input->getArgument('connect_name');
        $c         = Db::connect($name);
        $tables    = $c->getTables();
        $root      = app()->getRootPath();
        $outputDir = $input->getOption('output_dir');
        $outputDir = $root . str_replace(['\\'], ['/'], trim(trim($outputDir), '\\/')) . DIRECTORY_SEPARATOR;
        if (count(scandir($outputDir)) > 2) {
            x_exception("输出目录：{$outputDir}-存在文件,请先清空");
        }
        /**
         * @var Query $query
         */
        $query = app(Query::class, [$c]);
        foreach ($tables as $table) {
            var_dump($query->table($table)->getFieldsType());
            //            var_dump($fields);
        }

        $output->writeln($this->name);
    }
}
