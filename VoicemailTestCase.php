<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use MakeBusy\Kazoo\Applications\Callflow\FeatureCodes;

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

    protected static $message_tones = [
    		"VM-SAMPLE-MESSAGE-1" => 3500,
    		"VM-SAMPLE-MESSAGE-2" => 3550,
    		"VM-SAMPLE-GREETING-1" => 3600
    ];

    public static function setUpCase() {
        self::$b_voicemail_box = self::$account->createVm(self::VM_BOX_ID);
        self::$b_user          = self::$account->createUser();
        self::$a_device        = self::$account->createDevice("auth");
        self::$b_device        = self::$b_user->createDevice("auth", TRUE);

        self::$b_voicemail_box->createCallflow([self::VM_ACCESS_NUMBER]);
        self::$b_voicemail_box->createUserVmCallflow([self::B_USER_NUMBER], self::$b_user->getId());
        self::$b_voicemail_box->createCheckCallflow([self::VM_CHECK_NUMBER]);
        FeatureCodes::createVmCompose(self::$account);

        //set defaults, box should be setup when entering a test, we can force setup by setting is_setup=FALSE later.
        self::$b_voicemail_box->setVoicemailboxParam('owner_id', self::$b_user->getId());
        self::$b_voicemail_box->setVoicemailboxParam('is_setup', TRUE);
    }

    public static function onChannelReady($ch) {
    	$ch->startToneDetection("VOICEMAIL");
    }

    public static function onChannelAnswer($ch) {
    	$ch->startToneDetection("VOICEMAIL");
    }

    static function leaveMessage($device, $target, $msg1, $msg2 = null){
        $ch = self::ensureAnswered($device->originate($target), 30);

        // TODO: speed-up redirect call to voicemail
        self::expectPrompt($ch, "VM-PERSON", 30);
        self::expectPrompt($ch, "VM-NOT_AVAILABLE");
        self::expectPrompt($ch, "VM-RECORD_MESSAGE");

        $ch->playTone(self::$message_tones[$msg1]);

        $ch->sendDtmf("1");

        self::expectPrompt($ch, "VM-REVIEW_RECORDING", 20);

        if ($msg2){
            $ch->sendDtmf("3");
            $ch->playTone(self::$message_tones[$msg2], 2000);
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
