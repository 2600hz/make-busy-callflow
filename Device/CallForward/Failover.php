<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class FailoverTest extends DeviceTestCase {

    public function setUp() {
        self::$no_device->resetCfParams(self::C_EXT);
        self::$no_device->setCfParam("failover", TRUE);
        self::$no_device->setCfParam("enabled", FALSE);
    }

    public function tearDown() {
        self::$no_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::NO_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_c = self::ensureChannel( self::$c_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }

}