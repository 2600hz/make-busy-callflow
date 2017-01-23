<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-26
class OwnerChangePin extends VoicemailTestCase {

    public function tearDownTest() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
        $target  = self::B_USER_NUMBER . '@'. $sip_uri;
        $ch = self::ensureChannel( self::$b_device->originate($target) );

        self::expectPrompt($ch, "VM-ENTER_PASS");

        $ch->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($ch, "VM-NO_MESSAGES");
        self::expectPrompt($ch, "VM-MAIN_MENU");

        $ch->sendDtmf("5");

        self::expectPrompt($ch, "VM-SETTINGS_MENU");

        $ch->sendDtmf("3");

        self::expectPrompt($ch, "VM-ENTER_NEW_PIN");

        $ch->sendDtmf(self::CHANGE_PIN);

        self::expectPrompt($ch, "VM-ENTER_NEW_PIN_CONFIRM");

        $ch->sendDtmf(self::CHANGE_PIN);

        self::expectPrompt($ch, "VM-PIN_SET");

        $ch->hangup();
        $ch->waitHangup();

        $this->assertEquals(self::$b_voicemail_box->getVoicemailboxParam("pin"), self::CHANGE_PIN);
    }

}