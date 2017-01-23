<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-26
class OwnerChangePin extends VoicemailTestCase {

    public function setUpTest() {
    }

    public function tearDownTest() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
        $target  = self::B_USER_NUMBER . '@'. $sip_uri;
        $ch = self::ensureChannel( self::$a_device->originate($target) );

        // TODO: speed-up redirect call to voicemail
        self::expectPrompt($ch, "VM-PERSON", 30);

        $ch->sendDtmf("*");

        self::expectPrompt($ch, "VM-ENTER_PASS");

        $ch->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($ch, "VM-NO_MESSAGES");

        $ch->hangup();
        $ch->waitHangup();
    }

}