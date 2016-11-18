<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class MuteParticipant extends ConferenceTestCase {

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::login(self::$devices["a"], $target);
        $ch_b = self::login(self::$devices["b"], $target);
        $ch_c = self::login(self::$devices["c"], $target);
        sleep(5); // conference prompt && entry tone

        $ch_b->sendDtmf(SELF::ASTERISK2);
        self::expectPrompt($ch_b, "CONF-MUTED");

        self::ensureNotTalking($ch_b, $ch_a);
        self::ensureTalking($ch_a, $ch_b);

        $ch_b->sendDtmf(SELF::ASTERISK3);
        self::expectPrompt($ch_b, "CONF-UNMUTED");

        self::ensureTalking($ch_b, $ch_a);
        self::ensureTalking($ch_a, $ch_b);

        $ch_b->sendDtmf(SELF::ASTERISK1);
        self::expectPrompt($ch_b, "CONF-MUTED");

        self::ensureNotTalking($ch_b, $ch_a);
        self::ensureTalking($ch_a, $ch_b);

        $ch_b->sendDtmf(SELF::ASTERISK1);
        self::expectPrompt($ch_b, "CONF-UNMUTED");

        self::ensureTalking($ch_b, $ch_a);
        self::ensureTalking($ch_a, $ch_b);
    }

}
