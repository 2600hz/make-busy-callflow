<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallerIdOnnetTest extends CallflowTestCase {

    public function testMain() {
        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            self::assertEquals(
                self::$a_device->getCidParam("internal")->number,
                urldecode($ch_b->getEvent()->getHeader("Caller-Caller-ID-Number"))
            );
            self::ensureAnswer($ch_a, $ch_b);
            self::ensureTwoWayAudio($ch_a, $ch_b);
            self::hangupBridged($ch_a, $ch_b);
        }
    }

}