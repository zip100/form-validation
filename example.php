<?php
require 'Validation.class.php';

// 数据源
$post = array(
    'name' => 'mike',
    'age' => 28,
    'mobile' => '18610009876',
    'email' => 'memcache@qq.com'
);

// 验证配置
$config = array(
    // 错误语言包
    'error' => array(
        'require' => '为必填项',
        'size' => "长度为%d到%d位",
        'email' => '格式错误',
        'mobile' => '格式错误',
        'tel' => '格式错误',
        'digital' => '格式错误',
        'money' => '格式错误',
        'maxSize' => '长度不能超过%d个字符',
        'minSize' => '长度不能少于%d个字符',
        'max' => '不能大于%d',
        'min' => '不能小于%d'
    ),
    // 是否显示全量错误
    'showAllReport' => true,
    // 自定义业务规则
    'rule' => array(
        'isChild' => array(
            'callable' => function($data,$post,$params){
                return $data < 18;
            },
            'error' => '格式错误'
        ),
        'isDisabled' => array(
            'callable' => function($data,$post,$params){
                throw new \Exception('抛异常弹出错误！');
            },
            'error' => '格式错误'
        )
    )
);

// 验证规则

$rule = array(
    'name' => array(
        'lable' => '名称',
        'rule' => 'require|size[1,32]'
    ),
    'age' => array(
        'lable' => '年纪',
        'rule' => 'require|digital|isChild'
    ),
    'mobile' => array(
        'lable' => '电话',
        'rule' => 'require|mobile'
    ),
    'email' => array(
        'lable' => '邮件',
        'rule' => 'require|email|isDisabled'
    ),
);

$instance = new Validation($config);


$result = $instance->rule($rule)->check($post);
if (! $result) {
    $error = $instance->getReport();
    echo '<pre>';var_dump($error);die('</pre>');
}

echo "Pass";

?>