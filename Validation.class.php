<?php

class Validation
{
    // 待验证数据
    private $data;
    // 验证规则
    private $rule;
    // 内置正则验证库
    private $pattern = array(
        'email' => '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i',
        'mobile' => '/^1[3|7|8|5][0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/',
        'tel' => '/((\d{11})|^((\d{7,8})|(\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$)/',
        'digital' => '/^[1-9]{1}[0-9]{0,}$/',
        'money' => '/^[0-9]*[\.]{0,1}[0-9]{0,2}$/'
    );
    // 是否显示所有报告
    private $showAllReport = false;
    // 错误提示
    private $error;
    // 自定义规则
    private $customerRule;
    // 验证结果
    private $userReport;
    // 异常错误
    private $exceptionError = array();

    function __construct($config)
    {
        isset($config['showAllReport']) && $this->showAllReport = $config['showAllReport'];
        isset($config['error']) && $this->error = $config['error'];
        isset($config['rule']) && $this->customerRule = $config['rule'];
        
        // 自定义规则错误注入
        if ($this->customerRule) {
            foreach ($this->customerRule as $name => $value) {
                $this->error[$name] = $value['error'];
            }
        }
    }
    
    // 必填项
    function ruleRequire($key, $param)
    {
        return ! empty($this->data[$key]);
    }
    
    // 字符串长度
    function ruleSize($key, $param)
    {
        $length = $this->strLen($this->data[$key]);
        return $length >= $param[0] && $length <= $param[1];
    }
    
    // 最小值值限制
    function ruleMax($key, $param)
    {
        return $this->data[$key] <= $param[0];
    }
    
    // 最大值限制
    function ruleMin($key, $param)
    {
        return $this->data[$key] >= $param[0];
    }
    
    // 字符串长度（最长）
    function ruleMaxSize($key, $param)
    {
        $length = $this->strLen($this->data[$key]);
        return $length <= $param[0];
    }
    
    // 字符串长度（最短）
    function ruleMinSize($key, $param)
    {
        $length = $this->strLen($this->data[$key]);
        return $length >= $param[0];
    }
    
    // 返回字符串长度
    private function strLen($str)
    {
        return mb_strlen($str, 'utf8');
    }
    
    // 设置验证规则
    function rule(array $rule)
    {
        $this->rule = $rule;
        return $this;
    }
    
    // 验证数据
    function check(array $data)
    {
        $this->data = $data;
        foreach ($this->rule as $key => $rule) {
            $arr = explode('|', $rule['rule']);
            foreach ($arr as $ruleName) {
                $params = array();
                // 验证规则解析
                if (preg_match('/\[(.*)\]/', $ruleName, $matchs)) {
                    $ruleName = str_replace($matchs[0], '', $ruleName);
                    $params = explode(',', $matchs[1]);
                    $this->rule[$key]['params'] = $params;
                }
                
                // 验证处理
                $result = $this->handle($key, $ruleName, $params);
                if ($result) {
                    continue;
                }
                $report[$key][$ruleName] = $result;
                if (! $result) {
                    break;
                }
            }
            if (! $this->showAllReport && ! $result) {
                break;
            }
        }
        $this->userReport = $this->translateError($report);
        return ! $this->userReport;
    }

    /**
     * 翻译错误
     *
     * @author guosi <memcache@qq.com>
     */
    private function translateError($report)
    {
        $return = null;
        if(!$report)
            return $return;
        foreach ($report as $fieldName => $value) {
            $rule = $this->rule[$fieldName];
            foreach ($value as $ruleName => $result) {
                if ($result) {
                    continue;
                }
                $error = $rule['error'];
                $error || $error = $rule['lable'] . sprintf($this->error[$ruleName], $rule['params'][0], $rule['params'][1]);
                $this->exceptionError[$fieldName] && $error = $this->exceptionError[$fieldName];
                if (! $this->showAllReport) {
                    $return[$fieldName] = $error;
                    break;
                }
                $return[$fieldName][] = $error;
            }
        }
        return $return;
    }

    /**
     * 验证处理
     *
     * @author guosi <memcache@qq.com>
     */
    private function handle($key, $ruleName, $params)
    {
        // 静态方法验证
        $methodName = "rule" . ucfirst($ruleName);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($key, $params);
        }
        // 正则方法验证
        $patternName = strtolower($ruleName);
        if ($pattern = $this->pattern[$patternName]) {
            return preg_match($pattern, $this->data[$key]);
        }
        // 自定义方法验证
        if ($this->customerRule[$ruleName]) {
            try {
                return call_user_func($this->customerRule[$ruleName]['callable'], $this->data[$key], $this->data, $params);
            } catch (\Exception $e) {
                $this->exceptionError[$key] = $e->getMessage();
                return false;
            }
        }
        
        throw new \Exception('Undefined Rule:' . $ruleName);
    }

    /**
     * 获取用户报表
     *
     * @author guosi <memcache@qq.com>
     */
    function getReport()
    {
        return $this->userReport;
    }
}
