## 描述
- 通过方法注解、反射机制实现轻量级事件回调「将`#[Callee(枚举)]`绑定到任意方法进行回调」
- 功能支持：
  - 发布订阅
  - 优先级
  - 协程异步
  - 事务后执行

## 安装
要通过 Composer 安装此包，请运行以下命令：
```bash
composer require riven/hyperf-callee
```
## 使用
### 配置
`Callee\ConfigProvider::class`服务提供者应该会自动注册。如果未自动注册，在你的 Hyperf 应用程序中启用 `Callee`，你需要在 `config/autoload/dependencies.php` 文件中注册它：
```php
return [
    Callee\ConfigProvider::class,
];
```
### 示例
[此处添加如何使用该库的具体示例]

## 贡献
我们非常欢迎任何形式的贡献！如果你发现了问题或者有改进的想法，请先查看我们的 [贡献指南](https://github.com/woweijun123/callee/blob/main/CONTRIBUTING.md)。对于新功能的提案或错误修复，请直接提交 Pull Request。

- **Issues**: <https://github.com/woweijun123/callee/issues>
- **Pull Requests**: <https://github.com/woweijun123/callee/pulls>
