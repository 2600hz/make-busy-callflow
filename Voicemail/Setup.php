<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Setup extends VoicemailTestCase {

    public function tearDown() {
        self::$b_voicemail_box->resetVoicemailBox();
    }

    public function main($sip_uri) {
    }
}