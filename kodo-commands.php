<?php

if (!class_exists('WP_CLI')) {
    return;
}

class KODO_CLI_Commands
{
    /**
     * 同步文件夹到 KODO
     *
     * ## OPTIONS
     *
     * <path>
     * : 要同步的文件夹
     *
     * ## EXAMPLES
     *
     *     wp kodo upload wp-content/uploads
     *
     * @when after_wp_load
     */
    public function upload($args, $assoc_args)
    {
        [$path] = $args;
        $dir = ABSPATH . $path;
        if (!is_dir($dir)) {
            WP_CLI::error("Directory not found: [{$dir}]");
        }

        WP_CLI::line("Uploading files from [{$dir}] to KODO...");

        $files = kodo_read_dir_queue(ABSPATH, $path);
        if (empty($files)) {
            WP_CLI::success('No files to upload.');
            return;
        }

        foreach ($files as $file) {
            $status = kodo_file_upload($file['key'], $file['filepath']);
            if ($status) {
                WP_CLI::line("Uploaded: {$file['key']}");
            } else {
                WP_CLI::line("Failed: {$file['key']}");
            }
        }

        $total = count($files);
        WP_CLI::success("Uploaded {$total} files.");
    }

    /**
     * 同步文件到 KODO
     *
     * ## OPTIONS
     *
     * <path>
     * : 要同步的文件
     *
     * [--delete]
     * : 如果设置，上传后会删除本地文件
     * [--key=<key>]
     * : 指定上传到 KODO 的 key，默认和文件路径一致
     *
     * ## EXAMPLES
     *
     *     wp kodo upload-file wp-content/uploads/2021/01/1.jpg
     *     wp kodo upload-file wp-content/uploads/2021/01/1.jpg --delete
     *     wp kodo upload-file wp-content/uploads/2021/01/1.jpg --key=2021/01/1.jpg
     *
     * @when after_wp_load
     * @subcommand upload-file
     */
    public function upload_file($args, $assoc_args)
    {
        [$path] = $args;
        $file = ABSPATH . $path;
        if (!is_file($file)) {
            WP_CLI::error("File not found: {$file}");
        }

        $delete = false;
        if (isset($assoc_args['delete'])) {
            $delete = true;
        }

        $key = isset($assoc_args['key']) ? $assoc_args['key'] : $path;

        WP_CLI::line("Uploading file [{$file}] to KODO with key [$key]...");

        $status = kodo_file_upload("/{$key}", $file, $delete);
        if ($status) {
            WP_CLI::success("Uploaded: {$path}");
        } else {
            WP_CLI::error("Failed: {$path}");
        }
    }

    /**
     * 删除 KODO 中的文件
     *
     * ## OPTIONS
     *
     * <key>
     * : 需要删除 KODO 中的文件 key
     *
     * ## EXAMPLES
     *
     *     wp kodo delete-file 2021/01/1.jpg
     *
     * @when after_wp_load
     * @subcommand delete-file
     */
    public function delete_file($args, $assoc_args)
    {
        [$key] = $args;
        WP_CLI::line("Deleting file [{$key}] from KODO...");

        kodo_delete_file($key);
    }
}

WP_CLI::add_command('kodo', 'KODO_CLI_Commands', ['shortdesc' => 'Commands used to operate KODO.']);
