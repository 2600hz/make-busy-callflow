<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class LoginBadPin extends ConferenceTestCase {

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
        self::hangupChannels($ch_a);
    }

}
