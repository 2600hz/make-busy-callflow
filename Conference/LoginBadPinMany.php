<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class LoginBadPinMany extends ConferenceTestCase {

    public function setUp() {
        self::$a_conference->setMemberPin([self::MEMBERPIN1, self::MEMBERPIN2]);
    }

    public function tearDown() {
        // TODO: reset conference
    }

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::loginWithPin(self::$devices["a"], $target, self::WRONGPIN );
        self::expectPrompt($ch_a, "CONF-BAD_PIN");
        self::expectPrompt($ch_a, "CONF-ENTER_CONF_PIN");
        $ch_a->sendDtmf("" . '#');
        self::expectPrompt($ch_a, "CONF-BAD_PIN");
        self::expectPrompt($ch_a, "CONF-ENTER_CONF_PIN");
        $ch_a->sendDtmf(self::WRONGPIN . '#');
        self::expectPrompt($ch_a, "CONF-TOO_MANY_ATTEMPTS");
        self::ensureEvent( $ch_a->waitDestroy() );
    }

}
