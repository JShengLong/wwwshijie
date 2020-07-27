<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;


class Hxc extends Command
{
    protected function configure()
    {
        $this->setName('hxc')->setDescription('代码生成器开启');
    }

    protected function execute(Input $input, Output $output)
    {
        $doc = <<<DOC
#介绍
代码生成工具主要用于生成后台开发中简单的增删改查代码，复杂的逻辑还需自己实现，不过你无须担心，我们通过简单的封装简化了开发流程，即便你是新手，也可以写出出色的代码

#项目上线
务必将根目录下的hxc.lock文件删除
本项目建议在php7.0以上的环境中运行，如使用php5.6，请将php.ini中的always_populate_raw_post_data值改为-1

#如何使用
0、操作之前请先完善数据表，因为所有的下面的全部操作都是基于数据的字段

1、封装和说明
  1)、增删改查统一封装：/application/admin/library/hxc/curd
  2)、所有数据库操作均使用TP5的模型

2、名词解释
  1）、字段，数据库中的字段名
  2）、名称，例如form中的label名称，用于：列表页，新增加页，编辑页
  3）、类型，该字段在数据库中的字段类型
  4）、检索，这个字段是否用于查询，在列表页的顶部展示
  5）、操作，该字段是否需要进行增，改，查的操作
  6）、业务类型，选择输入方式，用于新增页和编辑页
  7）、排序，选择字段排序方式，用于列表页
  8）、必填，这个字段是否必填，自动生成新增和修改的验证规则
  9）、类型自动转换，选择转换的目标类型，使用模型在写入和读取的时候自动进行类型转换处理，详见tp5文档(https://www.kancloud.cn/manual/thinkphp5/138669)
  10）、注释，字段在数据库中的注释
  
3、开发规范
  1）、写在前面，遵循开发规范会大大节省你的开发时间和更漂亮的代码结构
  
  2）、数据库查询的封装均为TP5的Db类，具体可以参考TP5文档
  
  3）、在生成的控制器中，会有一些固定的私有变量，下面我们来逐个解释
    
    ①、protected \$modelName  = '模型名';  //用于add和update方法
    ②、protected \$tableName = '要查询的表名';//用于index和delete方法
    ③、protected \$indexField = ['列表页要显示字段']; 在日常开发中，我们需要在列表中追加表格的列，首先我们需要在这里进行编辑
    ④、protected \$addField = ['新增页面的表单输入字段'];
    ⑤、protected \$editField = ['编辑页面的表单输入字段'];
            /**
             * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
             * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
             * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
             * @var array
             */
        protected \$searchField = [];
    ⑥、protected \$pageSize = 10;                 //当前页面显示的数据数量，用于分页
    ⑦、protected \$addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    ⑧、protected \$editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    ⑨、protected \$deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑
    ⑩、//添加数据验证规则，参考tp5的验证规则
        protected \$add_rule = [
            'nickName|昵称'  => 'require|max:25'
        ];
        //编辑数据验证规则，参考tp5的验证规则
        protected \$edit_rule = [
            'nickName|昵称'  => 'require|max:25'
        ];
    
  4）、每个页面都有自己的生命周期，下面我们依次来说明（方法所在目录：/application/admin/library/hxc/Common.php）
    ①、列表页
       indexQuery(\\think\\Query \$sql)   列表查询的sql语句，如果再列表查询上我们需要其他的操作，可以进行链式操作，如\$sql->where(['id' => 1])
       indexAssign(\$data) 列表页面输出到视图的数据，如果我们需要往视图中追加数据可以在此方法中实现，如\$data['id'] = 1
       deleteEnd(\$id)     数据删除完成后，我们还需要其他操作？那么你可以选择在这个方法里书写你的逻辑，\$id是你插入数据库后的id
                           如果开启事务，只需在方法内判断错误即可，如
                           if(!false){//业务逻辑判断
                               return json_error(); //返回错误提示，用于页面接收
                           }
                           特别注意：无需书写事务提交方法（Db::commit()）和成功提示
       
    ②、数据新增页
       addAssign(\$data)    用法跟indexAssign相同
       addData(\$data)      要入库的数据数组，如果你需要追加数据，那么在此方法中操作是一个好的选择，如\$data['create_time'] = time()
       addEnd(\$id,\$data)  用法跟deleteEnd相同,特别说明\$data是当前接受的参数，是包含追加后的数据集合
       
    ③、数据编辑页
        editAssign(\$data)
        editData(\$data)
        editEnd(\$id,\$data)
        这三个方法跟数据新增页的用法相同
DOC;

        file_put_contents(ROOT_PATH . 'hxc.lock', $doc);
        $output->writeln("---------------------------------------");
        $output->writeln("Starting success");
        $output->writeln("---------------------------------------");
        $output->writeln("Url:/admin/generate");
        $output->writeln("Document path:/hxc.lock");
        $output->writeln("---------------------------------------");
    }
}