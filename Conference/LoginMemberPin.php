<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class LoginMemberPin extends ConferenceTestCase {

    public function setUp() {
        self::$a_conference->setMemberPin([self::MEMBERPIN1, self::MEMBERPIN2]);
    }

    public function tearDown() {
        // TODO: reset Conference
    }

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::loginWithPin(self::$devices["a"], $target, self::MEMBERPIN1 );
        $ch_b = self::loginWithPin(self::$devices["b"], $target, self::MEMBERPIN2 );
        self::ensureTwoWayAudio($ch_a, $ch_b);
        self::hangupChannels($ch_a);
    }

}
