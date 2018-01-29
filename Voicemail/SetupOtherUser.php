<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-25
class SetupOtherUser extends VoicemailTestCase {

    public function setUpTest() {
        self::$b_voicemail_box->setVoicemailboxParam("is_setup", FALSE);
    }

    public function tearDownTest() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
        $target  = self::VM_CHECK_NUMBER . '@'. $sip_uri;
        $ch = self::ensureAnswered( self::$a_device->originate($target), 30 );

        self::expectPrompt($ch, "VM-ENTER_ID");
        $ch->sendDtmf(self::VM_BOX_ID);
        self::expectPrompt($ch, "VM-ENTER_PASS");
        $ch->sendDtmf(self::DEFAULT_PIN);
        self::expectPrompt($ch, "VM-SETUP_INTRO");
        self::expectPrompt($ch, "VM-ENTER_NEW_PIN");
        $ch->sendDtmf(self::CHANGE_PIN);
        self::expectPrompt($ch, "VM-ENTER_NEW_PIN_CONFIRM");
        $ch->sendDtmf(self::CHANGE_PIN);
        self::expectPrompt($ch, "VM-PIN_SET");
        self::expectPrompt($ch, "VM-SETUP_REC_GREETING");
        self::expectPrompt($ch, "VM-RECORD_GREETING");
        $ch->playTone(self::$message_tones["VM-SAMPLE-GREETING-1"], 2000);
        $ch->sendDtmf("1");
        self::expectPrompt($ch, "VM-REVIEW_RECORDING");
        $ch->sendDtmf("2");
        self::expectPrompt($ch, "VM-SAMPLE-GREETING-1");
        self::expectPrompt($ch, "VM-REVIEW_RECORDING");
        $ch->sendDtmf("1");
        self::expectPrompt($ch, "VM-SAVED");
        self::expectPrompt($ch, "VM-SETUP_COMPLETE");
        self::expectPrompt($ch, "VM-NO_MESSAGES");
        self::expectPrompt($ch, "VM-MAIN_MENU");
        $ch->hangup();
        $ch->waitHangup();

        $this->assertEquals(self::$b_voicemail_box->getVoicemailboxParam("pin"), self::CHANGE_PIN);
        $this->assertTrue(self::$b_voicemail_box->getVoicemailboxParam("is_setup"));
        $this->assertNotEmpty(self::$b_voicemail_box->getVoicemailboxParam("media")->unavailable);
    }

}