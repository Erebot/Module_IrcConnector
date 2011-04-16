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

/**
 * A module which can send the proper sequence of commands
 * to an IRC server to proceed to the "registered" phase.
 *
 * This module also supports security upgrades (upgrading
 * from a plain-text connection to a TLS encrypted one)
 * using the STARTTLS extension.
 *
 * \see
 *      See http://wiki.inspircd.org/STARTTLS_Documentation
 *      for more information on the STARTTLS extension.
 */
class   Erebot_Module_IrcConnector
extends Erebot_Module_Base
{
    protected $_password;
    protected $_nickname;
    protected $_identity;
    protected $_hostname;
    protected $_realname;

    public function _reload($flags)
    {
        if ($flags & self::RELOAD_HANDLERS) {
            $handler = new Erebot_EventHandler(
                array($this, 'handleLogon'),
                new Erebot_Event_Match_InstanceOf('Erebot_Interface_Event_Logon')
            );
            $this->_connection->addEventHandler($handler);
        }
    }

    protected function _unload()
    {
    }

    /**
     * This method sends IRC credentials over the connection.
     * It is called automatically by this module once the
     * connection has been established and the optional
     * security upgrade has been applied to it.
     *
     * \post
     *      This method sends the proper sequence of
     *      PASS (if a password has been configured),
     *      NICK and USER commands to the underlying
     *      connection.
     *      After that, the bot is marked as "registered"
     *      from a protocol-oriented point of view.
     *
     * \note
     *      Even though the underlying connection has been
     *      established when this method is called, you
     *      SHOULD NOT assume that the bot is connected until
     *      an Erebot_Event_Connect event is dispatched.
     */
    protected function sendCredentials()
    {
        $this->_password = $this->parseString('password', '');
        $this->_nickname = $this->parseString('nickname');
        $this->_identity = $this->parseString('identity', 'Erebot');
        $this->_hostname = $this->parseString('hostname', 'Erebot');
        $this->_realname = $this->parseString('realname', 'Erebot');

        $config = $this->_connection->getConfig(NULL);
        $URIs   = $config->getConnectionURI();
        $URI    = new Erebot_URI($URIs[count($URIs) - 1]);

        if ($this->_password != '')
            $this->sendCommand('PASS '.$this->_password);
        $this->sendCommand('NICK '.$this->_nickname);
        $this->sendCommand('USER '.$this->_identity.' '.$this->_hostname.
                            ' '.$URI->getHost().' :'.$this->_realname);
    }

    public function handleLogon(Erebot_Interface_Event_Logon $event)
    {
        $config = $this->_connection->getConfig(NULL);
        $URIs   = $config->getConnectionURI();
        $URI    = new Erebot_URI($URIs[count($URIs) - 1]);

        // If no upgrade should be performed or
        // if the connection is already encrypted.
        if (!$this->parseBool('upgrade', FALSE) || $URI->getScheme() == 'ircs')
            $this->sendCredentials();
        // Otherwise, start a TLS negociation.
        else {
            $handler = new Erebot_RawHandler(
                array($this, 'handleSTARTTLSSuccess'),
                Erebot_Interface_Event_Raw::RPL_STARTTLSOK
            );
            $this->_connection->addRawHandler($handler);
            $handler = new Erebot_RawHandler(
                array($this, 'handleSTARTTLSFailure'),
                Erebot_Interface_Event_Raw::ERR_STARTTLSFAIL
            );
            $this->_connection->addRawHandler($handler);
            $this->sendCommand('STARTTLS');
        }
    }

    public function handleSTARTTLSSuccess(Erebot_Interface_Event_Raw $raw)
    {
        try {
            stream_socket_enable_crypto(
                $this->_connection->getSocket(),
                TRUE,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            );
        }
        catch (Erebot_ErrorReportingException $e) {
            $this->_connection->disconnect(NULL, TRUE);
        }
        $this->sendCredentials();
    }

    public function handleSTARTTLSFailure(Erebot_Interface_Event_Raw $raw)
    {
        $this->_connection->disconnect(NULL, TRUE);
    }

    /**
     * Returns the password used to connect to this IRC network.
     *
     * \retval string
     *      The password used to connect to this IRC network.
     *
     * \note
     *      This method will return an empty string if no password
     *      was used to contact this IRC network.
     */
    public function getNetPassword()
    {
        return $this->_password;
    }

    /**
     * Returns the bot's nickname.
     *
     * \retval string
     *      The bot's nickname.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current nickname
     *      if it was changed at a later time.
     */
    public function getBotNickname()
    {
        return $this->_nickname;
    }

    /**
     * Returns the bot's identity.
     *
     * \retval string
     *      The bot's ident.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current identity
     *      if it was changed at a later time.
     */
    public function getBotIdentity()
    {
        return $this->_identity;
    }

    /**
     * Returns the bot's hostname.
     *
     * \retval string
     *      The bot's hostname.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current hostname
     *      if it was changed at a later time.
     *
     * \note
     *      Most IRC servers ignore the hostname announced
     *      by clients and do a reverse DNS query on their
     *      IP address instead (this is a security measure).
     *      The value returned by this method will generally
     *      be different from the value used by the server.
     */
    public function getBotHostname()
    {
        return $this->_hostname;
    }

    /**
     * Returns the bot's real name (or GECOS information).
     *
     * \retval string
     *      The bot's real name.
     *
     * \note
     *      This is the value defined in the configuration file.
     *      It is not updated once the bot is connected, hence,
     *      it may be different from the bot's current real name
     *      if it was changed at a later time.
     */
    public function getBotRealname()
    {
        return $this->_realname;
    }
}

