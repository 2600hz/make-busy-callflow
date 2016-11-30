<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class ResetMailbox extends VoicemailTestCase {

    public function main($sip_uri) {
        self::$b_voicemail_box->resetVoicemailBox();
    }

}