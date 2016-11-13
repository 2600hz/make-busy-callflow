<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallerIdEmergency extends DeviceTestCase {

    public function main($sip_uri) {
        $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$emergency_resource->waitForInbound(self::EMERGENCY_NUMBER) );
        self::assertEquals(
            self::$a_device->getCidParam("emergency")->number,
            urldecode($ch_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($ch_a, $ch_b);
        self::hangupBridged($ch_a, $ch_b);
    }

}