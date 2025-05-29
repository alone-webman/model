# model生成和sql备份还原

```text
composer require alone-webman/model
```

### 使用此库要安装以下库

* https://www.workerman.net/doc/webman/db/tutorial.html

```
composer require -W webman/database
```

* https://www.workerman.net/doc/webman/plugin/console.html

```text
composer require webman/console

```

## 配置文件

* `config/plugin/alone/model/app.php`

## 命令

* 生成model文件

```
php webman alone:model
```

* 备份还原SQL

```
php webman alone:sql
```

* 查看mysql配置

```
php webman alone:mysql
```