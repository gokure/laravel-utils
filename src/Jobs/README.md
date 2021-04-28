# Jobs

基于 Laravel 5.4 环境，针对 Jobs 的扩充，支持定制化的延时重试机制，Laravel 5.5之后已经添加了`retryUntil`方法，基于时间的任务尝试策略，这里不再赘述。

## 示例

```php
class DemoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Runnable;

    public function __construct($id)
    {
        $this->id = $id;
        $this->tries(3);
    }

    public function retriesIn(): int
    {
        return 2 ** $this->attempts();
    }

    public function run()
    {
        $user = Account::findOrFail($this->id);
        // 业务逻辑处理
        ...
    }
}
```

```php
dispatch(DemoJob::make(1)->tries(10));
...
```
