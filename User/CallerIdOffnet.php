<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-35
// Ensure calls to offnet destinations use users external CID
class CallerIdOffnet extends UserTestCase {

    public function main($sip_uri) {
        $target  = self::OFFNET_NUMBER .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $channel_b = self::ensureChannel( self::$offnet_resource->waitForInbound(self::OFFNET_NUMBER) );
        self::assertEquals(
            urldecode($channel_b->getEvent()->getHeader("Caller-Caller-ID-Number")),
            self::$a_user->getCidParam("external")->number
        );
        self::ensureAnswer($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}