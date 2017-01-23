<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36 - Cf enabled by feature code
// b_device_1 should not have forwarding enabled, b_user should have forwarding enabled to C
class CallForwardEnable extends UserTestCase {

    public function tearDownTest() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::CALL_FWD_ENABLE . '@' . $sip_uri;
        $b_ch = self::ensureChannel( self::$b_device_1->originate($target) );
        $b_ch->waitAnswer();
        $b_ch->sendDtmf(self::C_NUMBER . '#');
        self::ensureEvent($b_ch->waitDestroy(20)); // TODO: wait till Kazoo completes? 
        self::assertNull(self::$b_device_1->getDeviceParam("call_forward"));
        self::assertTrue(self::$b_user->getCfParam("enabled"));
        self::assertEquals(self::$b_user->getCfParam("number"), self::C_NUMBER);
    }

}
