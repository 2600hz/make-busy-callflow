<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class SubstituteTest extends DeviceTestCase {

    public function setUp() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("substitute", TRUE);
    }

    public function tearDown() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target  = self::B_EXT .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::assertNull( self::$b_device->waitForInbound() );
        $channel_c = self::ensureChannel( self::$c_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }

}