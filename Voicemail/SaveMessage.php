<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-34
class SaveMessage extends VoicemailTestCase {

    public function setUp() {
        self::$b_user->setUserParam("vm_to_email_enabled",FALSE);
    }

    public function tearDown() {
        self::$b_voicemail_box->resetVoicemailBox();
        self::$b_user->setUserParam("vm_to_email_enabled", TRUE);
    }

    public function main($sip_uri) {
        Log::debug("trying target %s", $sip_uri);
        $target = self::B_USER_NUMBER . '@' . $sip_uri;
        self::leaveMessage(self::$a_device, $target, "600");

        $ch_b = self::ensureChannel( self::$b_device->originate($target) );
        $ch_b->waitAnswer();

        self::expectPrompt($ch_b, "VM-ENTER_PASS");
        $ch_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($ch_b, "VM-YOU_HAVE", 60);
        self::expectPrompt($ch_b, "VM-NEW_MESSAGE");
        self::expectPrompt($ch_b, "VM-MAIN_MENU");

        $ch_b->sendDtmf('1');

        self::expectPrompt($ch_b, "VM-MESSAGE_NUMBER");
        self::expectPrompt($ch_b, "600");
        self::expectPrompt($ch_b, "VM-RECEIVED");
        self::expectPrompt($ch_b, "VM-MESSAGE_MENU");

        $ch_b->sendDtmf('1');
        self::expectPrompt($ch_b, "VM-SAVED");

        $ch_b->hangup();
        $ch_b->waitHangup();

        $ch_b = self::ensureChannel( self::$b_device->originate($target) );
        $ch_b->waitAnswer();

        self::expectPrompt($ch_b, "VM-ENTER_PASS");
        $ch_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($ch_b, "VM-YOU_HAVE");
        self::expectPrompt($ch_b, "VM-SAVED_MESSAGE");
        self::expectPrompt($ch_b, "VM-MAIN_MENU");

        $ch_b->sendDtmf('2');

        self::expectPrompt($ch_b, "VM-MESSAGE_NUMBER");
        self::expectPrompt($ch_b, "600");
        self::expectPrompt($ch_b, "VM-RECEIVED");
        self::expectPrompt($ch_b, "VM-MESSAGE_MENU");

        $ch_b->sendDtmf('7');
        self::expectPrompt($ch_b, "VM-DELETED");

        $ch_b->hangup();
        $ch_b->waitHangup();
    }

}