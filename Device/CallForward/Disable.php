<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class CallForwardDisableTest extends CallflowTestCase {

    public function testMain() {
        self::$b_device->resetCfParams(self::C_EXT);
        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::CALL_FWD_DISABLE . '@' . $sip_uri;
            $b_ch = self::ensureChannel( self::$b_device->originate($target) );
            $b_ch->waitDestroy();
            $this->assertFalse( self::$b_device->getCfParam("enabled") );
        }
    }

}