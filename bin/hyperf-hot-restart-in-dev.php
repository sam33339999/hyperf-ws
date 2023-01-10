<?php

declare(strict_types=1);

/**
 * 使用方式：
 * 1. 把文件放置到项目的 bin 目录中，更名为 dev.php
 * 2. 开发中启动项目时，使用 dev.php 进行启动，而不是使用 hyperf.php 进行启动
 */

use Swoole\Process as SwooleProcess;

class WatchProcess
{
    public $process;

    public $pipes;
}

function startHyperf()
{
    $process = new SwooleProcess(function (SwooleProcess $worker) {
        echo "\n\n👉 ", date('Y-m-d H:i:s'), " about to start hyperf with pid {$worker->pid}\n\n";
        include __DIR__ . '/hyperf.php';
    }, false);
    return $process->start();
}

function getFswatchPath()
{
    $fswatch_paths = [
        '/usr/local/bin/fswatch',
        '/usr/bin/fswatch',
        '/bin/fswatch',
        '/sbin/fswatch',
        '/opt/homebrew/bin/fswatch',
    ];
    $fswatch_path = null;
    foreach ($fswatch_paths as $fswatch_path) {
        if (is_executable($fswatch_path)) {
            return $fswatch_path;
        }
    }
}

function startWatch(): WatchProcess
{
    $fswatch_path = getFswatchPath();
    if (empty($fswatch_path)) {
        echo "\n\n";
        echo "‼️  you need to install fswatch first\n";
        echo "‼️  for OSX use command: brew install fswatch\n";
        echo "\n\n";
        exit();
    }
    $descriptors = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],   // stderr
    ];
    $watch_process = new WatchProcess();
    $watch_path = dirname(__DIR__);
    $watch_command = "fswatch -Ee '\\.json|\\.lock|.idea|.git|vendor|runtime' --event IsFile '{$watch_path}'";
    $watch_process->process = proc_open('exec ' . $watch_command, $descriptors, $watch_process->pipes);
    stream_set_blocking($watch_process->pipes[1], false);
    return $watch_process;
}

function checkWatch(WatchProcess $watch_process)
{
    $stdout = stream_get_contents($watch_process->pipes[1]);
    if (empty($stdout)) {
        return false;
    }
    return true;
}

$watch_process = startWatch();
$hyperf_pid = startHyperf();
$is_killing_process = false;

while (true) {
    usleep(1000 * 100);
    if (checkWatch($watch_process)) {
        echo "\n\n👉 ", date('Y-m-d H:i:s'), " about to stop hyperf with pid {$hyperf_pid}\n\n";
        SwooleProcess::kill($hyperf_pid);
        $is_killing_process = true;
    }
    $wait_result = SwooleProcess::wait(false);
    if ($wait_result === false) {
        // 因为前面 kill 一次无法完全杀死进程，这里进行重复 kill
        if ($is_killing_process) {
            SwooleProcess::kill($hyperf_pid);
        }
        continue;
    }
    if ($wait_result['pid'] == $hyperf_pid) {
        $is_killing_process = false;
        $hyperf_pid = startHyperf();
    }
}