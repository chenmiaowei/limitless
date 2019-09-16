<?php

namespace orangins\lib\infrastructure\ssh;

use Exception;
use orangins\modules\people\models\PhabricatorUser;
use PhutilArgumentWorkflow;
use PhutilChannel;
use PhutilIPAddress;

/**
 * Class PhabricatorSSHWorkflow
 * @package orangins\lib\infrastructure\ssh
 * @author 陈妙威
 */
abstract class PhabricatorSSHWorkflow
    extends PhutilArgumentWorkflow
{

    // NOTE: We are explicitly extending "PhutilArgumentWorkflow", not
    // "PhabricatorManagementWorkflow". We want to avoid inheriting "getViewer()"
    // and other methods which assume workflows are administrative commands
    // like `bin/storage`.

    /**
     * @var
     */
    private $sshUser;
    /**
     * @var
     */
    private $iochannel;
    /**
     * @var
     */
    private $errorChannel;
    /**
     * @var
     */
    private $isClusterRequest;
    /**
     * @var
     */
    private $originalArguments;
    /**
     * @var
     */
    private $requestIdentifier;

    /**
     * @return bool
     * @author 陈妙威
     */
    public function isExecutable()
    {
        return false;
    }

    /**
     * @param PhutilChannel $error_channel
     * @return $this
     * @author 陈妙威
     */
    public function setErrorChannel(PhutilChannel $error_channel)
    {
        $this->errorChannel = $error_channel;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getErrorChannel()
    {
        return $this->errorChannel;
    }

    /**
     * @param PhabricatorUser $ssh_user
     * @return $this
     * @author 陈妙威
     */
    public function setSSHUser(PhabricatorUser $ssh_user)
    {
        $this->sshUser = $ssh_user;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getSSHUser()
    {
        return $this->sshUser;
    }

    /**
     * @param PhutilChannel $channel
     * @return $this
     * @author 陈妙威
     */
    public function setIOChannel(PhutilChannel $channel)
    {
        $this->iochannel = $channel;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIOChannel()
    {
        return $this->iochannel;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function readAllInput()
    {
        $channel = $this->getIOChannel();
        while ($channel->update()) {
            PhutilChannel::waitForAny(array($channel));
            if (!$channel->isOpenForReading()) {
                break;
            }
        }
        return $channel->read();
    }

    /**
     * @param $data
     * @return $this
     * @author 陈妙威
     */
    public function writeIO($data)
    {
        $this->getIOChannel()->write($data);
        return $this;
    }

    /**
     * @param $data
     * @return $this
     * @author 陈妙威
     */
    public function writeErrorIO($data)
    {
        $this->getErrorChannel()->write($data);
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    protected function newPassthruCommand()
    {
        return id(new PhabricatorSSHPassthruCommand())
            ->setErrorChannel($this->getErrorChannel());
    }

    /**
     * @param $is_cluster_request
     * @return $this
     * @author 陈妙威
     */
    public function setIsClusterRequest($is_cluster_request)
    {
        $this->isClusterRequest = $is_cluster_request;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getIsClusterRequest()
    {
        return $this->isClusterRequest;
    }

    /**
     * @param array $original_arguments
     * @return $this
     * @author 陈妙威
     */
    public function setOriginalArguments(array $original_arguments)
    {
        $this->originalArguments = $original_arguments;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getOriginalArguments()
    {
        return $this->originalArguments;
    }

    /**
     * @param $request_identifier
     * @return $this
     * @author 陈妙威
     */
    public function setRequestIdentifier($request_identifier)
    {
        $this->requestIdentifier = $request_identifier;
        return $this;
    }

    /**
     * @return mixed
     * @author 陈妙威
     */
    public function getRequestIdentifier()
    {
        return $this->requestIdentifier;
    }

    /**
     * @return null|string
     * @author 陈妙威
     */
    public function getSSHRemoteAddress()
    {
        $ssh_client = getenv('SSH_CLIENT');
        if (!strlen($ssh_client)) {
            return null;
        }

        // TODO: When commands are proxied, the original remote address should
        // also be proxied.

        // This has the format "<ip> <remote-port> <local-port>". Grab the IP.
        $remote_address = head(explode(' ', $ssh_client));

        try {
            $address = PhutilIPAddress::newAddress($remote_address);
        } catch (Exception $ex) {
            return null;
        }

        return $address->getAddress();
    }

}
