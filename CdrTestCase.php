<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Kazoo\AbstractTestAccount;

class CdrTestCase extends TestCase
{
    protected static $a_device;
    protected static $b_device;

    const A_NUMBER          = '5552221001';
    const A_EXT             = '1001';
    const B_NUMBER          = '5552221002';
    const B_EXT             = '1002';

    public function tearDownTest() {
        $this->setStale(false);
        $this->setRefreshThreshold(0);
        $this->setRefreshTimeout(60);
        AbstractTestAccount::nukeTestAccounts();
    }

    public static function setUpCase() {
        self::$a_device = self::$account->createDevice("auth");
        self::$a_device->createCallflow([self::A_EXT, self::A_NUMBER]);

        self::$b_device = self::$account->createDevice("auth");
        self::$b_device->createCallflow([self::B_EXT, self::B_NUMBER]);
    }

    public function makeRecord($sip_uri, $delay = 5) {
        $target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        sleep($delay);
        self::hangupBridged($channel_a, $channel_b);
    }

    public function setStale($value) {
        $cfg = self::$account->getAccount()->SystemConfig("crossbar");
        $cfg->default->cdr_stale_view = $value;
        $cfg->update();
    }

    public function setRefreshTimeout($value) {
        $cfg = self::$account->getAccount()->SystemConfig("cdr");
        $cfg->default->refresh_timeout = $value;
        $cfg->update();
    }
    
    public function setRefreshThreshold($value) {
        $cfg = self::$account->getAccount()->SystemConfig("cdr");
        $cfg->default->refresh_view_threshold = $value;
        $cfg->update();
    }

}
