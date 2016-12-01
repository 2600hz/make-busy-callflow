<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-24
// If device has emergency CID set, use device level, not user
class CallerIdEmergency extends UserTestCase {

    public function main($sip_uri) {
        $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$emergency_resource->waitForInbound(self::EMERGENCY_NUMBER) );
        self::assertEquals(
            self::$a_device_1->getCidParam("emergency")->number,
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}