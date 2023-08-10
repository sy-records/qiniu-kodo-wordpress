<?php
/*
Plugin Name: KODO Qiniu
Plugin URI: https://github.com/sy-records/qiniu-kodo-wordpress
Description: 使用七牛云海量存储系统KODO作为附件存储空间。（This is a plugin that uses Qiniu Cloud KODO for attachments remote saving.）
Version: 1.4.1
Author: 沈唁
Author URI: https://qq52o.me
License: Apache 2.0
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once 'sdk/vendor/autoload.php';

define('KODO_VERSION', '1.4.1');
define('KODO_BASEFOLDER', plugin_basename(dirname(__FILE__)));

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

if (!function_exists('get_home_path')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
}

// 初始化选项
register_activation_hook(__FILE__, 'kodo_set_options');
// 初始化选项
function kodo_set_options()
{
    $options = array(
        'bucket' => '',
        'accessKey' => '',
        'secretKey' => '',
        'nothumb' => 'false', // 是否上传缩略图
        'nolocalsaving' => 'false', // 是否保留本地备份
        'upload_url_path' => '', // URL前缀
        'image_style' => '',
        'update_file_name' => 'false', // 是否重命名文件名
    );
    add_option('kodo_options', $options, '', 'yes');
}

function kodo_get_auth()
{
    $kodo_opt = get_option('kodo_options', true);
    $accessKey = esc_attr($kodo_opt['accessKey']);
    $secretKey = esc_attr($kodo_opt['secretKey']);
    // 构建鉴权对象
    return new Auth($accessKey, $secretKey);
}

function kodo_get_auth_token()
{
    // 生成上传 Token
    return kodo_get_auth()->uploadToken(kodo_get_bucket_name());
}

function kodo_get_bucket_name()
{
    $kodo_opt = get_option('kodo_options', true);
    return esc_attr($kodo_opt['bucket']);
}

/**
 * @param $object
 * @param $file
 * @param false $no_local_file
 */
function kodo_file_upload($object, $file, $no_local_file = false)
{
    //如果文件不存在，直接返回false
    if (!@file_exists($file)) {
        return false;
    }

    // Fix fread(): Length parameter must be greater than 0
    $filesize = @filesize($file);
    if ($filesize === 0 || $filesize === false) {
        return false;
    }

    $token = kodo_get_auth_token();
    // 要上传文件的本地路径
    $filePath = $file;
    // 上传到七牛后保存的文件名
    $key = ltrim($object, '/');
    // 初始化 UploadManager 对象并进行文件的上传。
    $uploadMgr = new UploadManager();
    // 调用 UploadManager 的 putFile 方法进行文件的上传。
    $uploadMgr->putFile($token, $key, $filePath);
//    list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
//    if ($err !== null) {
//        var_dump($err);
//    } else {
//        var_dump($ret);
//    }
    if ($no_local_file) {
        kodo_delete_local_file($file);
    }
}

/**
 * 是否需要删除本地文件
 *
 * @return bool
 */
function kodo_is_delete_local_file()
{
    $kodo_options = get_option('kodo_options', true);
    return (esc_attr($kodo_options['nolocalsaving']) == 'true');
}

/**
 * 删除本地文件
 *
 * @param $file
 * @return bool
 */
function kodo_delete_local_file($file)
{
    try {
        //文件不存在
        if (!@file_exists($file)) {
            return true;
        }

        //删除文件
        if (!@unlink($file)) {
            return false;
        }

        return true;
    } catch (Exception $ex) {
        return false;
    }
}

/**
 * 删除kodo中的文件
 * @param $file
 * @return bool
 */
function kodo_delete_file($file)
{
    $bucket = kodo_get_bucket_name();
    $bucketManager = new BucketManager(kodo_get_auth());
    $err = $bucketManager->delete($bucket, $file);
//    var_dump($err);
}

/**
 * 批量删除文件
 * @param $bucket
 * @param array $files
 */
function kodo_delete_files($bucket, array $files)
{
    $bucketManager = new BucketManager(kodo_get_auth());

    $ops = $bucketManager->buildBatchDelete($bucket, $files);
    $bucketManager->batch($ops);
//    list($ret, $err) = $bucketManager->batch($ops);
//    if ($err) {
//        print_r($err);
//    } else {
//        print_r($ret);
//    }
}

function kodo_get_option($key)
{
    return esc_attr(get_option($key));
}

/**
 * 上传附件（包括图片的原图）
 *
 * @param  $metadata
 * @return array()
 */
function kodo_upload_attachments($metadata)
{
    $mime_types = get_allowed_mime_types();
    $image_mime_types = array(
        $mime_types['jpg|jpeg|jpe'],
        $mime_types['gif'],
        $mime_types['png'],
        $mime_types['bmp'],
        $mime_types['tiff|tif'],
        $mime_types['ico'],
    );

    // 例如mp4等格式 上传后根据配置选择是否删除 删除后媒体库会显示默认图片 点开内容是正常的
    // 图片在缩略图处理
    if (!in_array($metadata['type'], $image_mime_types)) {
        //生成object在kodo中的存储路径
        if (kodo_get_option('upload_path') == '.') {
            $metadata['file'] = str_replace("./", '', $metadata['file']);
        }
        $object = str_replace("\\", '/', $metadata['file']);
        $home_path = get_home_path();
        $object = str_replace($home_path, '', $object);

        //在本地的存储路径
        $file = $home_path . $object; //向上兼容，较早的WordPress版本上$metadata['file']存放的是相对路径

        //执行上传操作
        kodo_file_upload('/' . $object, $file, kodo_is_delete_local_file());
    }

    return $metadata;
}

//避免上传插件/主题时出现同步到kodo的情况
if (substr_count($_SERVER['REQUEST_URI'], '/update.php') <= 0) {
    add_filter('wp_handle_upload', 'kodo_upload_attachments', 50);
    add_filter('wp_generate_attachment_metadata', 'kodo_upload_thumbs', 100);
    add_filter('wp_save_image_editor_file', 'kodo_save_image_editor_file', 101);
}

/**
 * 上传图片的缩略图
 */
function kodo_upload_thumbs($metadata)
{
    //获取上传路径
    $wp_uploads = wp_upload_dir();
    $basedir = $wp_uploads['basedir'];
    //获取kodo插件的配置信息
    $kodo_options = get_option('kodo_options', true);
    if (!empty($metadata['file'])) {
        // Maybe there is a problem with the old version
        $file = $basedir . '/' . $metadata['file'];
        $upload_path = kodo_get_option('upload_path');
        if ($upload_path != '.') {
            $path_array = explode($upload_path, $file);
            if (count($path_array) >= 2) {
                $object = '/' . $upload_path . end($path_array);
            }
        } else {
            $object = '/' . $metadata['file'];
            $file = str_replace('./', '', $file);
        }

        kodo_file_upload($object, $file, (esc_attr($kodo_options['nolocalsaving']) == 'true'));
    }
    //上传所有缩略图
    if (!empty($metadata['sizes'])) {
        //是否需要上传缩略图
        $nothumb = (esc_attr($kodo_options['nothumb']) == 'true');
        //如果禁止上传缩略图，就不用继续执行了
        if ($nothumb) {
            return $metadata;
        }
        //得到本地文件夹和远端文件夹
        $dirname = dirname($metadata['file']);
        $file_path = $dirname != '.' ? "{$basedir}/{$dirname}/" : "{$basedir}/";
        $file_path = str_replace("\\", '/', $file_path);
        if (kodo_get_option('upload_path') == '.') {
            $file_path = str_replace('./', '', $file_path);
        }

        $object_path = str_replace(get_home_path(), '', $file_path);

        //there may be duplicated filenames,so ....
        foreach ($metadata['sizes'] as $val) {
            //生成object在kodo中的存储路径
            $object = '/' . $object_path . $val['file'];
            //生成本地存储路径
            $file = $file_path . $val['file'];

            //执行上传操作
            kodo_file_upload($object, $file, (esc_attr($kodo_options['nolocalsaving']) == 'true'));
        }
    }
    return $metadata;
}

/**
 * @param $override
 * @return mixed
 */
function kodo_save_image_editor_file($override)
{
    add_filter('wp_update_attachment_metadata', 'kodo_image_editor_file_do');
    return $override;
}

/**
 * @param $metadata
 * @return mixed
 */
function kodo_image_editor_file_do($metadata)
{
    return kodo_upload_thumbs($metadata);
}

/**
 * 删除远端文件，删除文件时触发
 * @param $post_id
 */
function kodo_delete_remote_attachment($post_id)
{
    $meta = wp_get_attachment_metadata($post_id);
    $kodo_options = get_option('kodo_options', true);

    if (!empty($meta['file'])) {
        $deleteObjects = [];
        // meta['file']的格式为 "2020/01/wp-bg.png"
        $upload_path = kodo_get_option('upload_path');
        if ($upload_path == '') {
            $upload_path = 'wp-content/uploads';
        }
        $file_path = $upload_path . '/' . $meta['file'];

        $deleteObjects[] = str_replace("\\", '/', $file_path);

        $dirname = dirname($file_path) . '/';

//        $is_nothumb = (esc_attr($kodo_options['nothumb']) == 'false');
//        if ($is_nothumb) {
            // 删除缩略图
            if (!empty($meta['sizes'])) {
                foreach ($meta['sizes'] as $val) {
                    $size_file = $dirname . $val['file'];
                    $deleteObjects[] = str_replace("\\", '/', $size_file);
                }
            }
//        }

        $backup_sizes = get_post_meta($post_id, '_wp_attachment_backup_sizes', true);
        if (is_array($backup_sizes)) {
            foreach ($backup_sizes as $size) {
                $deleteObjects[] = str_replace("\\", '/', $dirname . $size['file']);
            }
        }

        kodo_delete_files($kodo_options['bucket'], $deleteObjects);
    } else {
        // 获取链接删除
        $link = wp_get_attachment_url($post_id);
        if ($link) {
            $upload_path = kodo_get_option('upload_path');
            if ($upload_path != '.') {
                $file_info = explode($upload_path, $link);
                if (count($file_info) >= 2) {
                    kodo_delete_file(end($file_info));
                }
            } else {
                $kodo_upload_url = esc_attr($kodo_options['upload_url_path']);
                $file_info = explode($kodo_upload_url, $link);
                if (isset($file_info[1])) {
                    kodo_delete_file($file_info[1]);
                }
            }
        }
    }
}

add_action('delete_attachment', 'kodo_delete_remote_attachment');

// 当upload_path为根目录时，需要移除URL中出现的“绝对路径”
function kodo_modefiy_img_url($url, $post_id)
{
    // 移除 ./ 和 项目根路径
    $url = str_replace(array('./', get_home_path()), array('', ''), $url);
    return $url;
}

if (kodo_get_option('upload_path') == '.') {
    add_filter('wp_get_attachment_url', 'kodo_modefiy_img_url', 30, 2);
}

function kodo_sanitize_file_name($filename)
{
    $kodo_options = get_option('kodo_options');
    switch ($kodo_options['update_file_name']) {
        case 'md5':
            return  md5($filename) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        case 'time':
            return date('YmdHis', current_time('timestamp'))  . mt_rand(100, 999) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        default:
            return $filename;
    }
}

add_filter( 'sanitize_file_name', 'kodo_sanitize_file_name', 10, 1 );

function kodo_function_each(&$array)
{
    $res = array();
    $key = key($array);
    if ($key !== null) {
        next($array);
        $res[1] = $res['value'] = $array[$key];
        $res[0] = $res['key'] = $key;
    } else {
        $res = false;
    }
    return $res;
}

/**
 * @param $dir
 * @return array
 */
function kodo_read_dir_queue($dir)
{
    $dd = [];
    if (isset($dir)) {
        $files = array();
        $queue = array($dir);
        while ($data = kodo_function_each($queue)) {
            $path = $data['value'];
            if (is_dir($path) && $handle = opendir($path)) {
                while ($file = readdir($handle)) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $files[] = $real_path = $path . '/' . $file;
                    if (is_dir($real_path)) {
                        $queue[] = $real_path;
                    }
                    //echo explode(kodo_get_option('upload_path'),$path)[1];
                }
            }
            closedir($handle);
        }
        $upload_path = kodo_get_option('upload_path');
        foreach ($files as $v) {
            if (!is_dir($v)) {
                $dd[] = ['filepath' => $v, 'key' =>  '/' . $upload_path . explode($upload_path, $v)[1]];
            }
        }
    }

    return $dd;
}

// 在插件列表页添加设置按钮
function kodo_plugin_action_links($links, $file)
{
    if ($file == plugin_basename(dirname(__FILE__) . '/qiniu-kodo-wordpress.php')) {
        $links[] = '<a href="options-general.php?page=' . KODO_BASEFOLDER . '/qiniu-kodo-wordpress.php">设置</a>';
        $links[] = '<a href="https://qq52o.me/sponsor.html" target="_blank">赞赏</a>';
    }
    return $links;
}

add_filter('plugin_action_links', 'kodo_plugin_action_links', 10, 2);

add_filter('the_content', 'kodo_setting_content_style');
function kodo_setting_content_style($content)
{
    $option = get_option('kodo_options');
    if (!empty(esc_attr($option['image_style']))) {
        preg_match_all('/<img.*?(?: |\\t|\\r|\\n)?src=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>/sim', $content, $images);
        if (!empty($images) && isset($images[1])) {
            foreach ($images[1] as $item) {
                if(strpos($item, esc_attr($option['upload_url_path'])) !== false){
                    $content = str_replace($item, $item . esc_attr($option['image_style']), $content);
                }
            }
        }
    }
    return $content;
}

add_filter('post_thumbnail_html', 'kodo_setting_post_thumbnail_style', 10, 3);
function kodo_setting_post_thumbnail_style($html, $post_id, $post_image_id)
{
    $option = get_option('kodo_options');
    if (!empty(esc_attr($option['image_style'])) && has_post_thumbnail()) {
        preg_match_all('/<img.*?(?: |\\t|\\r|\\n)?src=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>/sim', $html, $images);
        if (!empty($images) && isset($images[1])) {
            foreach ($images[1] as $item) {
                if(strpos($item, esc_attr($option['upload_url_path'])) !== false){
                    $html = str_replace($item, $item . esc_attr($option['image_style']), $html);
                }
            }
        }
    }
    return $html;
}

// 在导航栏“设置”中添加条目
function kodo_add_setting_page()
{
    add_options_page('七牛云Kodo设置', '七牛云Kodo设置', 'manage_options', __FILE__, 'kodo_setting_page');
}

add_action('admin_menu', 'kodo_add_setting_page');

// 插件设置页面
function kodo_setting_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient privileges!');
    }
    $options = array();
    if (!empty($_POST) and $_POST['type'] == 'kodo_set') {
        $options['bucket'] = isset($_POST['bucket']) ? sanitize_text_field($_POST['bucket']) : '';
        $options['accessKey'] = isset($_POST['accessKey']) ? sanitize_text_field($_POST['accessKey']) : '';
        $options['secretKey'] = isset($_POST['secretKey']) ? sanitize_text_field($_POST['secretKey']) : '';
        $options['nothumb'] = isset($_POST['nothumb']) ? 'true' : 'false';
        $options['nolocalsaving'] = isset($_POST['nolocalsaving']) ? 'true' : 'false';
        //仅用于插件卸载时比较使用
        $options['upload_url_path'] = isset($_POST['upload_url_path']) ? sanitize_text_field(
            stripslashes($_POST['upload_url_path'])
        ) : '';
        $options['image_style'] = isset($_POST['image_style']) ? sanitize_text_field($_POST['image_style']) : '';
        $options['update_file_name'] = isset($_POST['update_file_name']) ? sanitize_text_field($_POST['update_file_name']) : 'false';
    }

    if (!empty($_POST) and $_POST['type'] == 'qiniu_kodo_all') {
        $sync = kodo_read_dir_queue(get_home_path() . kodo_get_option('upload_path'));
        foreach ($sync as $k) {
            kodo_file_upload($k['key'], $k['filepath']);
        }
        echo '<div class="updated"><p><strong>本次操作成功同步' . count($sync) . '个文件</strong></p></div>';
    }

    // 替换数据库链接
    if (!empty($_POST) and $_POST['type'] == 'qiniu_kodo_replace') {
        $old_url = esc_url_raw($_POST['old_url']);
        $new_url = esc_url_raw($_POST['new_url']);

        global $wpdb;
        // 文章内容
        $posts_name = $wpdb->prefix .'posts';
        $posts_result = $wpdb->query("UPDATE $posts_name SET post_content = REPLACE( post_content, '$old_url', '$new_url') ");
        // 修改题图之类的
        $postmeta_name = $wpdb->prefix .'postmeta';
        $postmeta_result = $wpdb->query("UPDATE $postmeta_name SET meta_value = REPLACE( meta_value, '$old_url', '$new_url') ");

        echo '<div class="updated"><p><strong>替换成功！共替换文章内链'.$posts_result.'条、题图链接'.$postmeta_result.'条！</strong></p></div>';
    }

    // 若$options不为空数组，则更新数据
    if ($options !== array()) {
        //更新数据库
        update_option('kodo_options', $options);

        $upload_path = sanitize_text_field(trim(stripslashes($_POST['upload_path']), '/'));
        $upload_path = ($upload_path == '') ? 'wp-content/uploads' : $upload_path;
        update_option('upload_path', $upload_path);
        $upload_url_path = sanitize_text_field(trim(stripslashes($_POST['upload_url_path']), '/'));
        update_option('upload_url_path', $upload_url_path);
        echo '<div class="updated"><p><strong>设置已保存！</strong></p></div>';
    }

    $kodo_options = get_option('kodo_options', true);

    $kodo_nothumb = esc_attr($kodo_options['nothumb']);
    $kodo_nothumb = ($kodo_nothumb == 'true');

    $kodo_nolocalsaving = esc_attr($kodo_options['nolocalsaving']);
    $kodo_nolocalsaving = ($kodo_nolocalsaving == 'true');

    $kodo_update_file_name = esc_attr($kodo_options['update_file_name']);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    ?>
    <div class="wrap" style="margin: 10px;">
        <h1>七牛云 Kodo 设置 <span style="font-size: 13px;">当前版本：<?php echo KODO_VERSION; ?></span></h1>
        <p>如果觉得此插件对你有所帮助，不妨到 <a href="https://github.com/sy-records/qiniu-kodo-wordpress" target="_blank">GitHub</a> 上点个<code>Star</code>，<code>Watch</code>关注更新；<a href="https://go.qq52o.me/qm/ccs" target="_blank">欢迎加入云存储插件交流群，QQ群号：887595381</a>；</p>
        <hr/>
        <form name="form1" method="post" action="<?php echo wp_nonce_url(
            './options-general.php?page=' . KODO_BASEFOLDER . '/qiniu-kodo-wordpress.php'
        ); ?>">
            <table class="form-table">
                <tr>
                    <th>
                        <legend>空间名称</legend>
                    </th>
                    <td>
                        <input type="text" name="bucket" value="<?php echo esc_attr($kodo_options['bucket']); ?>" size="50" placeholder="请填写空间名称"/>
                        <p>请先访问 <a href="https://portal.qiniu.com/kodo/bucket?shouldCreateBucket=true" target="_blank">七牛云控制台</a> 创建<code>存储空间</code>，再填写以上内容。</p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>accessKey</legend>
                    </th>
                    <td><input type="text" name="accessKey" value="<?php echo esc_attr($kodo_options['accessKey']); ?>" size="50" placeholder="accessKey"/></td>
                </tr>
                <tr>
                    <th>
                        <legend>secretKey</legend>
                    </th>
                    <td>
                        <input type="text" name="secretKey" value="<?php echo esc_attr($kodo_options['secretKey']); ?>" size="50" placeholder="secretKey"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>不上传缩略图</legend>
                    </th>
                    <td>
                        <input type="checkbox" name="nothumb" <?php if ($kodo_nothumb) { echo 'checked="checked"'; } ?> />
                        <p>建议不勾选</p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>不在本地保留备份</legend>
                    </th>
                    <td>
                        <input type="checkbox" name="nolocalsaving" <?php if ($kodo_nolocalsaving) { echo 'checked="checked"'; } ?> />
                        <p>建议不勾选</p>
                    </td>
                </tr>
                <tr>
                  <th>
                    <legend>自动重命名文件</legend>
                  </th>
                  <td>
                    <select name="update_file_name">
                      <option <?php if ($kodo_update_file_name == 'false') {echo 'selected="selected"';} ?> value="false">不处理</option>
                      <option <?php if ($kodo_update_file_name == 'md5') {echo 'selected="selected"';} ?> value="md5">MD5</option>
                      <option <?php if ($kodo_update_file_name == 'time') {echo 'selected="selected"';} ?> value="time">时间戳+随机数</option>
                    </select>
                  </td>
                </tr>
                <tr>
                    <th>
                        <legend>本地文件夹</legend>
                    </th>
                    <td>
                        <input type="text" name="upload_path" value="<?php echo kodo_get_option('upload_path'); ?>" size="50" placeholder="请输入上传文件夹"/>
                        <p>附件在服务器上的存储位置，例如： <code>wp-content/uploads</code> （注意不要以“/”开头和结尾），根目录请输入<code>.</code>。</p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>URL前缀</legend>
                    </th>
                    <td>
                        <input type="text" name="upload_url_path" value="<?php echo kodo_get_option('upload_url_path'); ?>" size="50" placeholder="请输入URL前缀"/>

                        <p><b>注意：</b></p>

                        <p>1）URL前缀的格式为 <code><?php echo $protocol; ?>{加速域名}/{本地文件夹}</code> ，“本地文件夹”务必与上面保持一致（结尾无
                            <code>/</code> ），或者“本地文件夹”为 <code>.</code> 时 <code><?php echo $protocol; ?>{加速域名}</code> 。
                        </p>

                        <p>2）七牛云中没有文件夹的概念，所以本地文件夹对应七牛云中的文件名。</p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>图片样式</legend>
                    </th>
                    <td>
                        <input type="text" name="image_style" value="<?php echo esc_attr($kodo_options['image_style']); ?>" size="50" placeholder="请输入图片样式，留空表示不处理"/>

                        <p><b>获取图片样式：</b></p>

                        <p>1）在 <a href="https://portal.qiniu.com/kodo/bucket/image-style?bucketName=<?php echo esc_attr($kodo_options['bucket']); ?>" target="_blank">空间管理</a> 中对应空间的 <code>图片样式</code> 处添加。</p>

                        <p>2）填写时需要将<code>分隔符</code>和对应的<code>名称</code>或 <code>处理接口</code>进行拼接，例如：</p>

                        <p><code>分隔符</code>为<code>!</code>(感叹号)，<code>名称</code>为<code>webp</code>，<code>处理接口</code>为 <code>imageView2/0/format/webp/q/75</code></p>
                        <p>则填写为 <code>!webp</code> 或 <code>?imageView2/0/format/webp/q/75</code></p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>保存/更新选项</legend>
                    </th>
                    <td><input type="submit" name="submit" class="button button-primary" value="保存更改"/></td>
                </tr>
            </table>
            <input type="hidden" name="type" value="kodo_set">
        </form>
        <form name="form2" method="post" action="<?php echo wp_nonce_url(
            './options-general.php?page=' . KODO_BASEFOLDER . '/qiniu-kodo-wordpress.php'
        ); ?>">
            <table class="form-table">
                <tr>
                    <th>
                        <legend>同步历史附件</legend>
                    </th>
                    <input type="hidden" name="type" value="qiniu_kodo_all">
                    <td>
                        <input type="submit" name="submit" class="button button-secondary" value="开始同步"/>
                        <p><b>注意：如果是首次同步，执行时间将会非常长（根据你的历史附件数量），有可能会因为执行时间过长，导致页面显示超时或者报错。<br> 所以，建议附件数量过多的用户，直接使用官方的 <a target="_blank" rel="nofollow" href="https://developer.qiniu.com/kodo/tools/6435/kodoimport">同步工具</a></b></p>
                    </td>
                </tr>
            </table>
        </form>
        <hr>
        <form name="form3" method="post" action="<?php echo wp_nonce_url(
            './options-general.php?page=' . KODO_BASEFOLDER . '/qiniu-kodo-wordpress.php'
        ); ?>">
            <table class="form-table">
                <tr>
                    <th>
                        <legend>数据库原链接替换</legend>
                    </th>
                    <td>
                        <input type="text" name="old_url" size="50" placeholder="请输入要替换的旧域名"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend></legend>
                    </th>
                    <td>
                        <input type="text" name="new_url" size="50" placeholder="请输入要替换的新域名"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend></legend>
                    </th>
                    <input type="hidden" name="type" value="qiniu_kodo_replace">
                    <td>
                        <input type="submit" name="submit" class="button button-secondary" value="开始替换"/>
                        <p><b>注意：如果是首次替换，请注意备份！此功能会替换文章以及设置的特色图片（题图）等使用的资源链接</b></p>
                    </td>
                </tr>
            </table>
        </form>
    </div>
<?php
}
?>
