<?php
/**
 * 无人售货机本地守护面板 - 全局配置
 */
return [
    // 面板监听（本机使用）
    'app' => [
        'name'    => '售货机守护面板',
        'machine' => 'VM-A12-0317',
        // 默认登录账号（生产环境部署后请立即修改）
        'username' => 'admin',
        // 默认密码 admin123 的 bcrypt 哈希；部署后可通过 www/tools_change_password.php 修改
        'password_hash' => '$2y$10$PbuNo/u4jGT08rOWhtFiIeUBt37Hcijmz6I/G0Ddpgw4zb0NlXgo2',
        // 登录失败 N 次后锁定
        'max_failures' => 5,
        'lock_seconds' => 300,
        'session_lifetime' => 1800,
    ],

    // 路径（全部相对项目根目录，即 dirname(__DIR__)）
    'paths' => [
        'root'     => dirname(__DIR__),
        'www'      => dirname(__DIR__) . '/www',
        'bin'      => dirname(__DIR__) . '/bin/vmctl',
        'run'      => dirname(__DIR__) . '/var/run',
        'logs'     => dirname(__DIR__) . '/var/logs',
        'audit'    => dirname(__DIR__) . '/var/audit/audit.log',
        'services' => dirname(__DIR__) . '/services',
    ],

    // 受管服务定义：key 即服务标识，只允许下列白名单操作
    'services' => [
        'pay-listener' => [
            'name'   => '支付监听',
            'desc'   => '监听支付平台回调，驱动出货指令',
            'script' => 'pay_listener.sh',
            'log'    => 'pay-listener.log',
        ],
        'stock-reporter' => [
            'name'   => '库存上报',
            'desc'   => '定时采集货道余量并上报运营平台',
            'script' => 'stock_reporter.sh',
            'log'    => 'stock-reporter.log',
        ],
        'temp-collector' => [
            'name'   => '温控采集',
            'desc'   => '采集柜内温度并控制压缩机',
            'script' => 'temp_collector.sh',
            'log'    => 'temp-collector.log',
        ],
        'ad-player' => [
            'name'   => '广告播放',
            'desc'   => '轮播本地广告物料并回传播放回执',
            'script' => 'ad_player.sh',
            'log'    => 'ad-player.log',
        ],
    ],

    // 状态自动刷新间隔（毫秒）
    'refresh_ms' => 5000,
    // 日志默认拉取行数
    'log_lines' => 200,
];
