<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

class VoicemailTestCase extends TestCase
{
    public static $a_device;
    public static $b_user;
    public static $b_device;
    public static $b_voicemail_box;
    
    const VM_CHECK_NUMBER   = '3001';
    const VM_ACCESS_NUMBER  = '3002';
    const VM_BOX_ID         = '3000';
    const VM_CHECK_CODE     = '*97';
    const VM_COMPOSE_B_CODE = '**3000';
    const B_USER_NUMBER     = '3000';

    const DEFAULT_PIN = '0000';
    const CHANGE_PIN  = '1111';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $acc = new TestAccount("VoicemailTestCase");

        self::$b_voicemail_box = $acc->createVm(self::VM_BOX_ID);
        self::$b_user          = $acc->createUser();
        self::$a_device        = $acc->createDevice("auth");
        self::$b_device        = $acc->createDevice("auth", TRUE, ['owner_id' => self::$b_user->getId()]);

        self::$b_voicemail_box->createCallflow([self::VM_ACCESS_NUMBER]);
        self::$b_voicemail_box->createUserVmCallflow([self::B_USER_NUMBER], self::$b_user->getId());
        self::$b_voicemail_box->createCheckCallflow([self::VM_CHECK_NUMBER]);

        //set defaults, box should be setup when entering a test, we can force setup by setting is_setup=FALSE later.
        self::$b_voicemail_box->setVoicemailboxParam('owner_id', self::$b_user->getId());
        self::$b_voicemail_box->setVoicemailboxParam('is_setup', TRUE);

        self::syncSofiaProfile("auth", self::$a_device->isLoaded(), 1);
    }

    static function leaveMessage($device, $target, $freq, $refreq = null){
        $ch = self::ensureChannel( $device->originate($target) );
        $ch->waitAnswer();

        // TODO: speed-up redirect call to voicemail
        self::expectPrompt($ch, "VM-PERSON", 30);
        self::expectPrompt($ch, "VM-NOT_AVAILABLE");
        self::expectPrompt($ch, "VM-RECORD_MESSAGE");

        $ch->playTone($freq);

        $ch->sendDtmf("1");

        self::expectPrompt($ch, "VM-REVIEW_RECORDING", 20);

        if ($refreq){
            $ch->sendDtmf("3");
            $ch->playTone($refreq, 2000);
            $ch->sendDtmf("1");
            self::expectPrompt($ch, "VM-REVIEW_RECORDING", 20);
        }

        $ch->sendDtmf("1");

        // TODO: sync with Kazoo Event, it takes some time between message being recorded and being available
        self::expectPrompt($ch, "VM-SAVED", 60);
        self::expectPrompt($ch, "VM-THANK_YOU", 60);

        $ch->waitHangup();
    }

}
