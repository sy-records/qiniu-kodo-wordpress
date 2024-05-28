=== KODO Qiniu ===
Contributors: shenyanzhi
Donate link: https://qq52o.me/sponsor.html
Tags: KODO, 七牛云, qiniu, 对象存储, 海量存储
Requires at least: 4.6
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 1.5.2
License: Apache2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0.html

使用七牛云海量存储系统 KODO 作为附件存储空间。（This is a plugin that uses Qiniu Cloud KODO for attachments remote saving.）

== Description ==

使用七牛云海量存储系统 KODO 作为附件存储空间。（This is a plugin that uses Qiniu Cloud KODO for attachments remote saving.）

- 依赖七牛云海量存储系统 KODO 服务：https://www.qiniu.com/products/kodo
- 使用说明：https://developer.qiniu.com/kodo?ref=www.qq52o.me

## 插件特点

1. 可配置是否上传缩略图和是否保留本地备份
2. 本地删除可同步删除七牛云海量存储系统 KODO 中的文件
3. 支持七牛云海量存储系统 KODO 绑定的个性域名
4. 支持替换数据库中旧的资源链接地址
5. 支持七牛云海量存储系统 KODO 完整地域使用
6. 支持同步历史附件到七牛云海量存储系统 KODO
7. 支持七牛云图片样式
8. 支持七牛云原图保护
9. 支持媒体库编辑
10. 支持上传文件自动重命名
11. 插件更多详细介绍和安装：[https://github.com/sy-records/qiniu-kodo-wordpress](https://github.com/sy-records/qiniu-kodo-wordpress)

## 其他插件

腾讯云 COS：[GitHub](https://github.com/sy-records/sync-qcloud-cos)，[WordPress Plugins](https://wordpress.org/plugins/sync-qcloud-cos)
华为云 OBS：[GitHub](https://github.com/sy-records/huaweicloud-obs-wordpress)，[WordPress Plugins](https://wordpress.org/plugins/obs-huaweicloud)
阿里云 OSS：[GitHub](https://github.com/sy-records/aliyun-oss-wordpress)，[WordPress Plugins](https://wordpress.org/plugins/oss-aliyun)
又拍云 USS：[GitHub](https://github.com/sy-records/upyun-uss-wordpress)，[WordPress Plugins](https://wordpress.org/plugins/uss-upyun)

## 作者博客

[沈唁志](https://qq52o.me "沈唁志")

QQ 交流群：887595381

== Installation ==

1. Upload the folder `qiniu-kodo-wordpress` or `kodo-qiniu` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's all

== Screenshots ==

1. 设置页面
2. 数据库替换

== Frequently Asked Questions ==

= 怎么替换文章中之前的旧资源地址链接 =

这个插件已经加上了替换数据库中之前的旧资源地址链接功能，只需要填好对应的链接即可

== Changelog ==

= 1.5.2 =

- Optimize wpdb query

= 1.5.1 =

- Fix CSRF error

= 1.5.0 =

- 支持原图保护

= 1.4.6 =

- 修复图片处理参数重复追加

= 1.4.5 =

- 修复 `upload_url_path` 设置为 `.` 时删除失败
- 优化图片处理参数追加

= 1.4.4 =

- 修复上传 PDF 等文件格式时的错误

= 1.4.3 =

- 修复超大图片上传原图丢失问题
- 优化同步上传逻辑代码

= 1.4.2 =

- 修复 webp、heic 格式图片缩略图未上传问题

= 1.4.1 =

- 更新依赖 https://github.com/qiniu/php-sdk/releases/tag/v7.10.1

= 1.4.0 =

- 更新依赖
- 支持 WordPress 6.3 版本

= 1.3.2 =

- 支持媒体库编辑
- 支持上传文件自动重命名
- 移除 esc_html

= 1.3.0 =

- 修复 XSS
- 优化 isset 判断
- 优化访问权限
- 修复存在同名 path 时截取错误
- 修复禁用年/月目录格式时上传缩略图错误

= 1.2.5 =

- 修正版本号

= 1.2.4 =

- 添加 get_home_path 方法判断
- 支持 WordPress 5.7 版本

= 1.2.3 =

- 优化远端文件删除
- 修复同步文件上传报错`fread(): Length parameter must be greater than 0`

= 1.2.2 =

- 修复缩略图删除获取配置错误
- 升级 SDK
- 增加图片样式处理

= 1.2.1 =

- 优化缩略图删除
- 支持 WordPress 5.6

= 1.2.0 =

- 优化同步上传路径获取
- 修复多站点上传原图失败，缩略图正常问题
- 优化上传路径获取
- 增加数据库题图链接替换

= 1.1.0 =

- 优化删除文件为批量删除
- 修复勾选不在本地保存图片后媒体库显示默认图片问题
- 修复本地文件夹为根目录时路径错误

= 1.0.1 =

- 修复勾选不在本地保存图片后媒体库显示默认图片问题

= 1.0 =

- First version
