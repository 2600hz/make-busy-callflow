<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class CallerIdOnnet extends DeviceTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_b = self::ensureChannel( self::$b_device->waitForInbound() );
        self::assertEquals(
            self::$a_device->getCidParam("internal")->number,
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}