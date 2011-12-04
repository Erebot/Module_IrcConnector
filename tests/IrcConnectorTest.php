<?php
/*
    This file is part of Erebot.

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

class       TestURIFactory
implements  Erebot_Interface_URI
{
    public function __construct($uri)
    {
    }

    public function toURI($raw = FALSE, $credentials = TRUE)
    {
    }

    public function __toString()
    {
    }

    public function getScheme($raw = FALSE)
    {
    }

    public function setScheme($scheme)
    {
    }

    public function getUserInfo($raw = FALSE)
    {
    }

    public function setUserInfo($userinfo)
    {
    }

    public function getHost($raw = FALSE)
    {
        return '0.0.0.0';
    }

    public function setHost($host)
    {
    }

    public function getPort($raw = FALSE)
    {
    }

    public function setPort($port)
    {
    }

    public function getPath($raw = FALSE)
    {
    }

    public function setPath($path)
    {
    }

    public function getQuery($raw = FALSE)
    {
    }

    public function setQuery($query)
    {
    }

    public function getFragment($raw = FALSE)
    {
    }

    public function setFragment($fragment)
    {
    }

    public function asParsedURL($component = -1)
    {
    }

    public function relative($reference)
    {
    }

    static public function fromAbsPath($abspath, $strict = TRUE)
    {
    }
}

class   IrcConnectorTest
extends ErebotModuleTestCase
{
    public function _getMock()
    {
        $event = $this->getMock(
            'Erebot_Interface_Event_Logon',
            array(), array(), '', FALSE, FALSE
        );

        $event
            ->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->_connection));
        return $event;
    }

    public function setUp()
    {
        parent::setUp();

        $this->_serverConfig
            ->expects($this->any())
            ->method('getConnectionURI')
            ->will($this->returnValue(array('ircs://0.0.0.0/')));

        $this->_module = new Erebot_Module_IrcConnector(NULL);
        $this->_module->setURIFactory('TestURIFactory');
        $this->_module->reload($this->_connection, 0);
    }

    public function tearDown()
    {
        unset($this->_module);
        parent::tearDown();
    }

    public function testRegistrationWithoutPassword()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->onConsecutiveCalls(
                '',         // no password
                'Erebot',   // nickname
                'identity', // identity
                'hostname', // hostname
                'realname'  // realname
            ));

        $this->_module->handleLogon($this->_eventHandler, $this->_getMock());
        $this->assertEquals(2, count($this->_outputBuffer));
        $this->assertEquals(
            'NICK Erebot',
            $this->_outputBuffer[0]
        );
        $this->assertEquals(
            'USER identity hostname 0.0.0.0 :realname',
            $this->_outputBuffer[1]
        );
    }

    public function testRegistrationWithSomePassword()
    {
        $this->_serverConfig
            ->expects($this->any())
            ->method('parseString')
            ->will($this->onConsecutiveCalls(
                'password', // password
                'Erebot',   // nickname
                'identity', // identity
                'hostname', // hostname
                'realname'  // realname
            ));

        $this->_module->handleLogon($this->_eventHandler, $this->_getMock());
        $this->assertEquals(3, count($this->_outputBuffer));
        $this->assertEquals(
            'PASS password',
            $this->_outputBuffer[0]
        );
        $this->assertEquals(
            'NICK Erebot',
            $this->_outputBuffer[1]
        );
        $this->assertEquals(
            'USER identity hostname 0.0.0.0 :realname',
            $this->_outputBuffer[2]
        );
    }
}

