<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MAKEBUSY-34 Cf basic call
// both devices on C should ring.
class BasicTest extends UserTestCase {

    public function setUp() {
        self::$b_user->resetCfParams(self::C_NUMBER);
    }

    public function tearDown() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $ch_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_c_1);
        self::hangupBridged($ch_a, $ch_c_1);
    }

}