<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class MemberJoinDeaf extends ConferenceTestCase {

    public function setUp() {
        self::$a_conference->setModeratorPin([self::MODERATORPIN1]);
        self::$a_conference->setMemberPin([self::MEMBERPIN1]);
    }

    public function tearDown() {
        self::$a_conference->reset();
    }

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;

        $ch_a = self::loginWithPin(self::$devices["a"], $target, self::MODERATORPIN1);
        $ch_b = self::loginWithPin(self::$devices["b"], $target, self::MEMBERPIN1);

        self::$a_conference->setMemberOption("join_deaf", true);
        $ch_c = self::loginWithPin(self::$devices["c"], $target, self::MEMBERPIN1);

        self::ensureNotTalking($ch_a, $ch_c);
        self::ensureTalking($ch_c, $ch_a);

        self::ensureTwoWayAudio($ch_a, $ch_b);
        self::hangupChannels($ch_a, $ch_b, $ch_c);
    }

}
