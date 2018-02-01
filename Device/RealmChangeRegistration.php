<?php
namespace KazooTests\Applications\Callflow\Device;

use KazooTests\Applications\Callflow\DeviceTestCase;
use \MakeBusy\Common\Log;

class RealmChangeRegistration extends DeviceTestCase {

    public function tearDownTest() {
//        self::getTestAccount()->setAccountRealm(self::$realm);
//        self::$a_device->getGateway()->setParam('realm', self::$realm);
//        self::$a_device->getGateway()->setParam('from-domain', self::$realm);

//        self::$a_device->getGateway()->kill();
//        self::getProfile("auth")->rescan();
//    	  self::$a_device->getGateway()->register();
    }

    public function testMain($sip_uri = null) {
    	Log::info("KAZOO-5814: registration should be flushed");
        $this->markTestIncomplete("");
//         self::getTestAccount()->setAccountRealm('blah.com');
//         $this->assertFalse( self::$a_device->getGateway()->register() );

//         // TODO: this should fail: it sets only in-memory gateway parameters (no way to pass it to freeswitch as it gets gateways from kazoo only)
//         self::$a_device->getGateway()->setParam('realm', 'blah.com');
//         self::$a_device->getGateway()->setParam('from-domain', 'blah.com');

//         self::$a_device->getGateway()->kill();
//         self::getProfile("auth")->rescan(); 

//         $this->assertTrue( self::$a_device->getGateway()->register() );
    }

}