<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-26
class LeaveMessage extends VoicemailTestCase {

    public function tearDown() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
        $target = self::B_USER_NUMBER . '@' . $sip_uri;
        self::leaveMessage(self::$a_device, $target, "600");

        $messages = self::$b_voicemail_box->getMessages();
        self::assertNotNull($messages[0]->media_id);

        $ch_b = self::ensureChannel(self::$b_device->originate($target));

        self::expectPrompt($ch_b, "VM-ENTER_PASS");
        $ch_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($ch_b, "VM-YOU_HAVE", 60);
        self::expectPrompt($ch_b, "VM-NEW_MESSAGE");
        self::expectPrompt($ch_b, "VM-MAIN_MENU");

        $ch_b->sendDtmf('1');

        self::expectPrompt($ch_b, "VM-MESSAGE_NUMBER");

        self::expectPrompt($ch_b, "600", 60);

        self::expectPrompt($ch_b, "VM-RECEIVED");
        self::expectPrompt($ch_b, "VM-MESSAGE_MENU");

        $ch_b->sendDtmf('7');

        self::expectPrompt($ch_b, "VM-DELETED");

        $ch_b->hangup();
        $ch_b->waitHangup();

        self::$b_voicemail_box->resetVoicemailBox();
        self::$b_voicemail_box->resetVoicemailBoxParam("media");
    }

}