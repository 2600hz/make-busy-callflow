<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeafParticipant extends ConferenceTestCase {

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::login(self::$devices["a"], $target);
        $ch_b = self::login(self::$devices["b"], $target);
        $ch_c = self::login(self::$devices["c"], $target);
        sleep(5); // conference prompt && entry tone

        $ch_b->sendDtmf(SELF::ASTERISK5);
        self::expectPrompt($ch_b, "CONF-DEAF");

        self::ensureNotTalking($ch_a, $ch_b);
        self::ensureTalking($ch_b, $ch_a);

        $ch_b->sendDtmf(SELF::ASTERISK6);
        self::expectPrompt($ch_b, "CONF-UNDEAF");

        self::ensureTalking($ch_b, $ch_a);
        self::ensureTalking($ch_a, $ch_b);

        $ch_b->sendDtmf(SELF::ASTERISK4);
        self::expectPrompt($ch_b, "CONF-DEAF");

        self::ensureNotTalking($ch_a, $ch_b);
        self::ensureTalking($ch_b, $ch_a);

        $ch_b->sendDtmf(SELF::ASTERISK4);
        self::expectPrompt($ch_b, "CONF-UNDEAF");

        self::ensureTalking($ch_b, $ch_a);
        self::ensureTalking($ch_a, $ch_b);


    }

}
