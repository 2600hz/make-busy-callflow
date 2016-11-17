<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Common\Configuration;

class WebhookTestCase extends TestCase {
    public static $a_device;
    public static $b_device;

    const B_EXT = '7002';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $acc = new TestAccount("WebhookTest");

        self::$a_device = $acc->createDevice("auth", true);
        self::$b_device = $acc->createDevice("auth", true);
        self::$b_device->createCallflow([self::B_EXT]);

        $uri = Configuration::getWebhooksUri();

        $webhook_create  = $acc->createWebhook(['uri' => "$uri", 'hook' => 'channel_create']);
        $webhook_answer  = $acc->createWebhook(['uri' => "$uri", 'hook' => 'channel_answer']);
        $webhook_destory = $acc->createWebhook(['uri' => "$uri", 'hook' => 'channel_destroy']);

        self::sync_sofia_profile("auth", self::$a_device->isLoaded(), 2);
    }
}