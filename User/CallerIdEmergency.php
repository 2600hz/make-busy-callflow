<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-24
// If device has emergency CID set, use device level, not user
class CallerIdEmergency extends UserTestCase {

    public function main($sip_uri) {
        $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_b = self::ensureChannel( self::$emergency_resource->waitForInbound(self::EMERGENCY_NUMBER) );
        self::assertEquals(
            self::$a_device_1->getCidParam("emergency")->number,
            urldecode($ch_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($ch_a, $ch_b);
        self::hangupBridged($ch_a, $ch_b);
    }

}