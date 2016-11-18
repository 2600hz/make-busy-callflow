<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class LoginConferenceServer extends ConferenceTestCase {

    public function main($sip_uri) {
        $target = self::CONF_SERVICE_EXT .'@'. $sip_uri;
        $ch_a = self::ensureChannel( self::$devices["a"]->originate($target) );
        self::ensureEvent( $ch_a->waitPark() );
        self::expectPrompt($ch_a, "CONF-ENTER_CONF_NUMBER");
        $ch_a->sendDtmf(self::CONF_NUMBER1 . '#');
        self::expectPrompt($ch_a, "2600"); // entry-sound
        self::hangupChannels($ch_a);
    }

}
