<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class MaxParticipants extends ConferenceTestCase {

    public function setUp() {
        self::$a_conference->setMaxUsers(2);
        self::$a_conference->setMemberPin([self::MEMBERPIN1, self::MEMBERPIN2]);
    }

    public function tearDown() {
        self::$a_conference->setMaxUsers(NULL);
    }

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::loginWithPin(self::$devices["a"], $target, self::MEMBERPIN1);
        $ch_b = self::loginWithPin(self::$devices["b"], $target, self::MEMBERPIN1);
        $ch_c = self::loginWithPin(self::$devices["c"], $target, self::MEMBERPIN1);
        self::expectPrompt($ch_c, "CONF-MAX_PARTICIPANTS");
        $ch_c->waitDestroy(30);
        self::ensureTwoWayAudio($ch_a, $ch_b);
        self::hangupChannels($ch_a, $ch_b);
    }

}
