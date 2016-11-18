<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class ExitTone extends ConferenceTestCase {

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::login(self::$devices["a"], $target);
        $ch_b = self::login(self::$devices["b"], $target);
        sleep(3); // length of welcome message?
        $ch_b->sendDtmf('#');
        self::expectPrompt($ch_a, "3000");
        self::hangupChannels($ch_a, $ch_b);
    }

}
