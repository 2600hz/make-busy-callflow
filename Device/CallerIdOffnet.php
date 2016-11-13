<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallerIdOffnetTest extends DeviceTestCase {

    public function main($sip_uri) {
        $target  = '1' . self::OFFNET_NUMBER .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$offnet_resource->waitForInbound('1' . self::OFFNET_NUMBER) );
        self::assertEquals(
            self::$a_device->getCidParam("external")->number,
            urldecode($ch_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
        );
        self::ensureAnswer($ch_a, $ch_b);
        self::ensureTwoWayAudio($ch_a, $ch_b);
        self::hangupBridged($ch_a, $ch_b);
    }

}