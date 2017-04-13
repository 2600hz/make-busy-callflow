<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

// MKBUSY-34
class SaveMessage extends VoicemailTestCase {

    public function setUpTest() {
        self::$b_user->setUserParam("vm_to_email_enabled",FALSE);
    }

    public function tearDownTest() {
        self::$b_voicemail_box->resetVoicemailBox();
        self::$b_user->setUserParam("vm_to_email_enabled", TRUE);
    }

    public function main($sip_uri) {
        Log::debug("trying target %s", $sip_uri);
        $target = self::B_USER_NUMBER . '@' . $sip_uri;
        self::leaveMessage(self::$a_device, $target, "600");

        $channel_b = self::ensureChannel( self::$b_device->originate($target) );
        $channel_b->waitAnswer();

        self::expectPrompt($channel_b, "VM-ENTER_PASS");
        $channel_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($channel_b, "VM-YOU_HAVE", 60);
        self::expectPrompt($channel_b, "VM-NEW_MESSAGE");
        self::expectPrompt($channel_b, "VM-MAIN_MENU");

        $channel_b->sendDtmf('1');

        self::expectPrompt($channel_b, "VM-MESSAGE_NUMBER");
        self::expectPrompt($channel_b, "600");
        self::expectPrompt($channel_b, "VM-RECEIVED");
        self::expectPrompt($channel_b, "VM-MESSAGE_MENU", 20);

        $channel_b->sendDtmf('1');
        self::expectPrompt($channel_b, "VM-SAVED");

        $channel_b->hangup();
        $channel_b->waitHangup();

        $channel_b = self::ensureChannel( self::$b_device->originate($target) );
        $channel_b->waitAnswer();

        self::expectPrompt($channel_b, "VM-ENTER_PASS");
        $channel_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($channel_b, "VM-YOU_HAVE");
        self::expectPrompt($channel_b, "VM-SAVED_MESSAGE");
        self::expectPrompt($channel_b, "VM-MAIN_MENU");

        $channel_b->sendDtmf('2');

        self::expectPrompt($channel_b, "VM-MESSAGE_NUMBER");
        self::expectPrompt($channel_b, "600");
        self::expectPrompt($channel_b, "VM-RECEIVED");
        self::expectPrompt($channel_b, "VM-MESSAGE_MENU", 20);

        $channel_b->sendDtmf('7');
        self::expectPrompt($channel_b, "VM-DELETED");

        $channel_b->hangup();
        $channel_b->waitHangup();
    }

}