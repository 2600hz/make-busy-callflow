<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-26
class DeleteAfterNotify extends VoicemailTestCase {

    public function setUpTest() {
        self::$b_voicemail_box->setVoicemailBoxParam('owner_id', self::$b_user->getId());
        self::$b_voicemail_box->setVoicemailBoxParam('delete_after_notify',TRUE);
        $this->source = self::$b_voicemail_box->getMessages();
    }

    public function tearDownTest() {
        self::$b_voicemail_box->resetVoicemailBox();
        self::$b_voicemail_box->setVoicemailBoxParam('delete_after_notify',FALSE);
    }

    public function main($sip_uri) {
        $target = self::B_USER_NUMBER . '@' . $sip_uri;
        self::leaveMessage(self::$a_device, $target, "600");
        self::assertEquals(self::$b_voicemail_box->getMessages(), $this->source);
    }

}