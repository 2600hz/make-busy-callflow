<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MAKEBUSY-34 Cf basic call
// both devices on C should ring.
class BasicTest extends UserTestCase {

    public function setUpTest() {
        self::$b_user->resetCfParams(self::C_NUMBER);
    }

    public function tearDownTest() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $channel_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_c_1);
        self::hangupBridged($channel_a, $channel_c_1);
    }

}