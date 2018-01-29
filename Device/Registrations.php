<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class Registrations extends DeviceTestCase {

    public function testMain($sip_uri = null) {
        $this->assertTrue(self::$register_device->getGateway()->register());
    }

}