<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

//MKBUSY-23 - CF substitute set to false
// should result in all lines ringing
class NoSubstitute extends UserTestCase {

    public function setUp() {
        self::$b_user->enableUserCF(['enabled'=> TRUE, 'substitute'=> FALSE, 'number'=> self::C_NUMBER]);
    }

    public function tearDown() {
        self::$b_user->resetCfParams();
    }

    public function main($sip_uri) {
        $target  = self::B_NUMBER .'@'. $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device_1->originate($target) );
        $ch_b_1 = self::ensureChannel( self::$b_device_1->waitForInbound() );
        $ch_b_2 = self::ensureChannel( self::$b_device_2->waitForInbound() );
        $ch_c_1 = self::ensureChannel( self::$c_device_1->waitForInbound() );
        $ch_c_2 = self::ensureChannel( self::$c_device_2->waitForInbound() );

        self::hangupChannels($ch_b_1, $ch_b_2, $ch_c_1, $ch_c_2);
    }

}