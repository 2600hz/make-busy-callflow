<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallForwardDisable extends DeviceTestCase {

    public function setUp() {
        self::$b_device->resetCfParams(self::C_EXT);
    }

    public function tearDown() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::CALL_FWD_DISABLE . '@' . $sip_uri;
        $b_ch = self::ensureChannel( self::$b_device->originate($target) );
        $b_ch->waitAnswer();
        self::ensureEvent($b_ch->waitDestroy(30));
        self::assertFalse( self::$b_device->getCfParam("enabled") );
    }

}