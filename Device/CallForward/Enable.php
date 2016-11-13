<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallForwardEnableTest extends DeviceTestCase {

    public function setUp() {
        self::$b_device->resetCfParams(self::C_EXT);
    }

    public function tearDown() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::CALL_FWD_ENABLE . '@' . $sip_uri;
        $b_ch = self::ensureChannel( self::$b_device->originate($target) );
        $b_ch->sendDtmf(self::C_EXT);
        $b_ch->waitDestroy();
        self::assertTrue(self::$b_device->getCfParam("enabled"));
        self::assertEquals(self::$b_device->getCfParam("number"), self::C_EXT);
    }

}
