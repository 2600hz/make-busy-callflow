<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class LoginMemberPin extends ConferenceTestCase {

    public function setUp() {
        self::$a_conference->setModeratorPin([self::MODERATORPIN1]);
        self::$a_conference->setMemberPin([self::MEMBERPIN1]);
    }

    public function tearDown() {
        // TODO: reset conference
    }

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::loginWithPin(self::$devices["a"], $target, self::MEMBERPIN1 );
        $ch_b = self::loginWithPin(self::$devices["b"], $target, self::MODERATORPIN1 );
        self::ensureTwoWayAudio($ch_a, $ch_b);
        self::hangupChannels($ch_a, $ch_b);
    }

}
