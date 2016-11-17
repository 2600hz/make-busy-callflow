<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class Basic extends WebhookTestCase {

   public function main($sip_uri) {
        $target = self::B_EXT. '@' . $sip_uri;
        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        $ch_b->answer();
        $ch_b->waitAnswer();
        $ch_a->waitPark();

        $a_leg = $ch_a->getUuid();
        $b_leg = $ch_b->getUuid();

        $this->hangupBridged($ch_a, $ch_b);

        $a_leg_create  = "/tmp/$a_leg" . "_inbound_create.log";
        $a_leg_answer  = "/tmp/$a_leg" . "_inbound_answer.log";
        $a_leg_destroy = "/tmp/$a_leg" . "_inbound_destroy.log";

        $this->assertTrue(file_exists($a_leg_create));
        $this->assertTrue(file_exists($a_leg_answer));
        $this->assertTrue(file_exists($a_leg_destroy));

        $b_leg_create  = "/tmp/$b_leg" . "_outbound_create.log";
        $b_leg_answer  = "/tmp/$b_leg" . "_outbound_answer.log";
        $b_leg_destroy = "/tmp/$b_leg" . "_outbound_destroy.log";

        $this->assertTrue(file_exists($b_leg_create));
        $this->assertTrue(file_exists($b_leg_answer));
        $this->assertTrue(file_exists($b_leg_destroy));

        unlink(realpath($a_leg_create));
        unlink(realpath($a_leg_answer));
        unlink(realpath($a_leg_destroy));

        unlink(realpath($b_leg_create));
        unlink(realpath($b_leg_answer));
        unlink(realpath($b_leg_destroy));
    }
}

