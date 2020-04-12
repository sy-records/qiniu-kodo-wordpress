=== KODO Qiniu ===
Contributors: shenyanzhi
Donate link: https://qq52o.me/sponsor.html
Tags: KODO, 七牛云, qiniu, 对象存储, 海量存储
Requires at least: 4.2
Tested up to: 5.4
Requires PHP: 5.6.0
Stable tag: 1.1.0
License: Apache 2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0.html

使用七牛云海量存储系统KODO作为附件存储空间。（This is a plugin that uses Qiniu Cloud KODO for attachments remote saving.）

== Description ==

使用七牛云海量存储系统KODO作为附件存储空间。（This is a plugin that uses Qiniu Cloud KODO for attachments remote saving.）

* 依赖七牛云海量存储系统KODO服务：https://www.qiniu.com/products/kodo
* 使用说明：https://developer.qiniu.com/kodo?ref=www.qq52o.me

## 插件特点

1. 可配置是否上传缩略图和是否保留本地备份
2. 本地删除可同步删除七牛云海量存储系统KODO中的文件
3. 支持七牛云海量存储系统KODO绑定的个性域名
4. 支持替换数据库中旧的资源链接地址
5. 支持七牛云海量存储系统KODO完整地域使用
6. 支持同步历史附件到七牛云海量存储系统KODO
7. 插件更多详细介绍和安装：[https://github.com/sy-records/qiniu-kodo-wordpress](https://github.com/sy-records/qiniu-kodo-wordpress)

## 其他插件

腾讯云COS：[GitHub](https://github.com/sy-records/wordpress-qcloud-cos)，[WordPress Plugins](https://wordpress.org/plugins/sync-qcloud-cos)
华为云OBS：[GitHub](https://github.com/sy-records/huaweicloud-obs-wordpress)，[WordPress Plugins](https://wordpress.org/plugins/obs-huaweicloud)
阿里云OSS：[GitHub](https://github.com/sy-records/aliyun-oss-wordpress)，[WordPress Plugins](https://wordpress.org/plugins/oss-aliyun)
又拍云USS：[GitHub](https://github.com/sy-records/upyun-uss-wordpress)，[WordPress Plugins](https://wordpress.org/plugins/uss-upyun)

## 作者博客

[沈唁志](https://qq52o.me "沈唁志")

QQ交流群：887595381

== Installation ==

1. Upload the folder `qiniu-kodo-wordpress` or `kodo-qiniu` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's all

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png

== Frequently Asked Questions ==

= 怎么替换文章中之前的旧资源地址链接 =

这个插件已经加上了替换数据库中之前的旧资源地址链接功能，只需要填好对应的链接即可

== Changelog ==

= 1.1.0 =
* 优化删除文件为批量删除
* 修复勾选不在本地保存图片后媒体库显示默认图片问题
* 修复本地文件夹为根目录时路径错误

= 1.0.1 =
* 修复勾选不在本地保存图片后媒体库显示默认图片问题

= 1.0 =
* First version
