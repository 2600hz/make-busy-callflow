<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-36 Cf disabled by feature code
// b_user should have no cf enabled after activating feature code
class CallForwardDisable extends UserTestCase {

    public function setUp() {
        self::$b_user->resetCfParams(self::C_NUMBER);
    }

    public function tearDown() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::CALL_FWD_DISABLE . '@' . $sip_uri;
        $b_ch = self::ensureChannel( self::$b_device_1->originate($target) );
        $b_ch->waitDestroy();
        $this->assertFalse( self::$b_user->getCfParam("enabled") );
    }

}