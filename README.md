Crontab extension for laravel-admin
======

[//]: # ([Crontab]&#40;https://github.com/ArrowJustDoIt/Crontab&#41;)

![crontab列表](https://raw.githubusercontent.com/ArrowJustDoIt/crontab/master/crontab_list.png)

![crontab创建](https://raw.githubusercontent.com/ArrowJustDoIt/crontab/master/crontab_create.png)

![crontablog列表](https://raw.githubusercontent.com/ArrowJustDoIt/crontab/master/crontab_log_list.png)

![crontablog详情](https://raw.githubusercontent.com/ArrowJustDoIt/crontab/master/crontab_log_detail.png)
## install

```bash
composer require tungnt/crontab "@dev"
php artisan migrate
```

## setting

add `config/admin.php``extensions`
```php

    'extensions' => [

        'crontab' => [
            'enable' => true,
        ]
    ]

```

add in OS LINUX crontab

```
crontab -e //
* * * * * php /your web dir/artisan autotask:run >>/home/crontab.log 2>&1 //>>后面为日志文件保存地址,可加可不加
```

## Test

```
https://your domain/admin/crontabs 
https://your domain/admin/crontabLogs 
```


## License

Licensed under [The MIT License (MIT)](LICENSE).
