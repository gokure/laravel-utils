# XlsWrapper

基于 [ext-xlswriter 1.3.6+](https://github.com/viest/php-ext-xlswriter) 封装的工具包，可以更方便的进行导入/导出。

## 示例

```php
$data = [
    ['张三', '二年一班', 'A'],
    ['李四', '三年二班', 'B'],
    ['大卫', '四年五班', 'C'],
];

$path = XlsWrapper::create('/tmp/grades.xlsx')
    ->header(['姓名', '年级', '成绩'])
    ->data($data)
    ->output();

$rows = iterator_to_array(XlsWrapper::open('/tmp/grades.xlsx')->openSheetByIndex(0)->iterRowWithHeader());
var_export($rows);
// output
array (
  0 =>
  array (
    '姓名' => '张三',
    '年级' => '二年一班',
    '成绩' => 'A',
  ),
  1 =>
  array (
    '姓名' => '李四',
    '年级' => '三年二班',
    '成绩' => 'B',
  ),
  2 =>
  array (
    '姓名' => '大卫',
    '年级' => '四年五班',
    '成绩' => 'C',
  ),
)
```
