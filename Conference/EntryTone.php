<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class EntryTone extends ConferenceTestCase {

    public function main($sip_uri) {
        $target = self::CONF_EXT .'@'. $sip_uri;
        $ch_a = self::login(self::$devices["a"], $target);
        $ch_b = self::ensureChannel(self::$devices["b"]->originate($target));
        self::ensureEvent( $ch_b->waitPark() );
        self::expectPrompt($ch_b, "2600");
        self::hangupChannels($ch_a, $ch_b);
    }

}
