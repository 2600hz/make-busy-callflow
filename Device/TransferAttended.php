<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class TransferAttended extends DeviceTestCase {

    public function main($sip_uri) {
        $target = self::B_EXT . '@' . $sip_uri;
        $referred_by = sprintf("<sip:%s@%s:5060;transport=udp>", self::$b_device->getSipUsername(), self::getEsl("auth")->getIpAddress());
        $transferee = self::C_EXT . '@' . $sip_uri;

        $ch_a = self::ensureChannel( self::$a_device->originate($target) );
        $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );

        self::ensureAnswer($ch_a, $ch_b);
        self::ensureEvent($ch_a->waitPark());
        self::ensureTwoWayAudio($ch_a, $ch_b);

        self::assertEquals($ch_b->getChannelCallState(), "ACTIVE");
        $ch_b->onHold();
        self::assertEquals($ch_b->getChannelCallState(), "HELD");

        $ch_b_2 = self::ensureChannel( self::$b_device->originate($transferee) );
        $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

        $ch_c->answer();
        $ch_c->waitAnswer();
        $event = $ch_b_2->waitAnswer();
        sleep(1);
        self::ensureTwoWayAudio($ch_b_2, $ch_c);

        $to_tag = $event->getHeader('variable_sip_to_tag');
        $from_tag = $event->getHeader('variable_sip_from_tag');
        $sip_uri = urldecode($event->getHeader('variable_sip_req_uri'));
        $call_uuid = $event->getHeader('variable_call_uuid');

        $refer_to =     '<sip:' . $sip_uri
                 . '?Replaces=' . $call_uuid
               . '%3Bto-tag%3D' . $to_tag
             . '%3Bfrom-tag%3D' . $from_tag
             . '>';

        $ch_b->setVariables('sip_h_refer-to', $refer_to);
        $ch_b->setVariables('sip_h_referred-by', $referred_by);
        $ch_b->deflect($refer_to);
        $ch_b->waitDestroy();

        self::ensureTwoWayAudio($ch_a, $ch_c);
        self::hangupBridged($ch_a, $ch_c);
    }
}