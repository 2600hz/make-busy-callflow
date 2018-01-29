<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-27
class LeaveMessageRerecord extends VoicemailTestCase {

	private $count;
	
    public function setUpTest() {
        self::$b_voicemail_box->getVoicemailbox();
        $this->count  = count(self::$b_voicemail_box->getMessages());
    }

    public function tearDownTest() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
        $target = self::B_USER_NUMBER . '@' . $sip_uri;
        
        self::leaveMessage(self::$a_device, self::VM_COMPOSE_B_CODE, "VM-SAMPLE-MESSAGE-1", "VM-SAMPLE-MESSAGE-2");

        $messages = self::$b_voicemail_box->getMessages();

        self::assertNotNull($messages[$this->count]->media_id);

        $messages = self::$b_voicemail_box->getMessages();

        self::assertNotNull($messages[0]->media_id);

        $channel_b = self::ensureAnswered( self::$b_device->originate($target), 30 );
        
        self::expectPrompt($channel_b, "VM-ENTER_PASS");
        $channel_b->sendDtmf(self::DEFAULT_PIN);

        self::expectPrompt($channel_b, "VM-YOU_HAVE", 60);
        self::expectPrompt($channel_b, "VM-NEW_MESSAGE");
        self::expectPrompt($channel_b, "VM-MAIN_MENU");

        $channel_b->sendDtmf('1');

        self::expectPrompt($channel_b, "VM-MESSAGE_NUMBER");
        self::expectPrompt($channel_b, "VM-SAMPLE-MESSAGE-2");
        self::expectPrompt($channel_b, "VM-RECEIVED");
        self::expectPrompt($channel_b, "VM-MESSAGE_MENU", 20);

        $channel_b->sendDtmf('7');
        self::expectPrompt($channel_b, "VM-DELETED");

        $channel_b->hangup();
        $channel_b->waitHangup();
    }

}