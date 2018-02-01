<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class CallerIdEmergency extends DeviceTestCase {

    public function main($sip_uri) {
        $target  = self::EMERGENCY_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$emergency_resource->waitForInbound(self::EMERGENCY_NUMBER) );
        self::assertEquals(
            self::$a_device->getCidParam("emergency")->number,
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}