<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-27
class LeaveMessageRerecord extends VoicemailTestCase {

    public function setUp() {
        self::$b_voicemail_box->getVoicemailbox();
        $this->count  = count(self::$b_voicemail_box->getMessages());
    }

    public function tearDown() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
        $target = self::B_USER_NUMBER . '@' . $sip_uri;
        
        self::leaveMessage(self::$a_device, $target, "600", "1600");

        $messages = self::$b_voicemail_box->getMessages();

        self::assertNotNull($messages[$this->count]->media_id);

        $messages = self::$b_voicemail_box->getMessages();

        self::assertNotNull($messages[0]->media_id);

        $channel_b = self::ensureChannel( self::$b_device->originate($target) );

        self::expectPrompt($channel_b, "VM-ENTER_PASS");
        $channel_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($channel_b, "VM-YOU_HAVE", 60);
        self::expectPrompt($channel_b, "VM-NEW_MESSAGE");
        self::expectPrompt($channel_b, "VM-MAIN_MENU");

        $channel_b->sendDtmf('1');

        self::expectPrompt($channel_b, "VM-MESSAGE_NUMBER");
        self::expectPrompt($channel_b, "1600");
        self::expectPrompt($channel_b, "VM-RECEIVED");
        self::expectPrompt($channel_b, "VM-MESSAGE_MENU");

        $channel_b->sendDtmf('7');
        self::expectPrompt($channel_b, "VM-DELETED");

        $channel_b->hangup();
        $channel_b->waitHangup();
    }

}