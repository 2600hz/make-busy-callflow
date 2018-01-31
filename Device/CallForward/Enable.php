<?php
namespace KazooTests\Applications\Callflow\Device\CallForward;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class CallForwardEnableTest extends DeviceTestCase {

    public function setUpTest() {
        self::$b_device->resetCfParams(self::C_EXT);
    }

    public function tearDownTest() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::CALL_FWD_ENABLE . '@' . $sip_uri;
        $b_ch = self::ensureChannel( self::$b_device->originate($target) );
        $b_ch->waitAnswer();
        $b_ch->sendDtmf(self::C_EXT);
        $b_ch->hangup(); // is it a bug? why doesn't it hanged up channel after command is successful?
        self::ensureEvent( $b_ch->waitDestroy() );
        self::assertTrue(self::$b_device->getCfParam("enabled"));
        self::assertEquals(self::$b_device->getCfParam("number"), self::C_EXT);
    }

}
