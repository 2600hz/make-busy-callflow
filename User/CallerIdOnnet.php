<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-35
// Ensure same account calls use users Internal caller ID
class CallerIdOnnet extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device_1->waitForInbound() );
        self::assertEquals(
            urldecode($ch_b->getEvent()->getHeader("Caller-Caller-ID-Number")),
            self::$a_user->getCidParam("internal")->number
        );
        self::ensureAnswer($ch_a, $ch_b);
        self::hangupBridged($ch_a, $ch_b);
    }

}