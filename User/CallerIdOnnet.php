<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-35
// Ensure same account calls use users Internal caller ID
class CallerIdOnnet extends UserTestCase {

    public function main($sip_uri) {
        $target = self::B_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device_1->waitForInbound() );
        self::assertEquals(
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Number")),
            self::$a_user->getCidParam("internal")->number
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}