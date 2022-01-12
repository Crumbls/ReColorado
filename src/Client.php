<?php

namespace Crumbls\ReColorado;

use Crumbls\ReColorado\Models\Search\Results;
use Crumbls\ReColorado\Repositories\ReColorado as Repository;
use Gufy\PdfToHtml\Config;

class Client
{
    private bool $loggedIn = false;
    private $session = null;

    public function __construct()
    {

        $this->result = 0;
    }

    /**
     * Get our session.
     * @return Session
     */
    protected function getSession() : Session {
        if ($this->session) {
            return $this->session;
        }
        $this->session = new Session($this->getConfiguration());
        return $this->session;
    }

    /**
     * Load our configuration.
     * @return Configuration
     */
    protected function getConfiguration() {
        // setup your configuration
        $config = new Configuration();
        $config->setLoginUrl(\Config::get('recolorado.login_url'))
            ->setUsername(\Config::get('recolorado.username'))
            ->setPassword(\Config::get('recolorado.password'));
        return $config;
    }

    /**
     * Authenticate the client.  Does not need to be called directly.
     * @return bool
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MissingConfiguration
     */
    public function login() : bool {
        if ($this->loggedIn) {
            return true;
        }
        // setup your configuration
        $session = $this->getSession();
        $session->login();
        $this->loggedIn = true;

        /**
         * This doesn't get called.  We will have to figure out why eventually.
         */
        register_shutdown_function(function() {
            \ReColorado::logout();
        });

        return true;
    }

    /**
     * Get an authorized client.
     * @return Session
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MissingConfiguration
     */
    public function getClient() : Session {
        $this->login();
        return $this->session;
    }

    /**
     * Log a client out.
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MissingConfiguration
     */
    public function logout() {
        if (!$this->loggedIn) {
            return;
        }
        $this->getClient()->logout();
    }

    public function getLatestResidential(int $limit = 10) {
        $data = $this->getLatest('RESI', $limit);
        return $data;
    }

    public function getLatestCommercial(int $limit = 10) {
        $data = $this->getLatest('COML', $limit);
        return $data;
    }

    /**
     * Get latest results by type.
     * @param string $type
     * @param int $limit
     * @return Results
     * @throws Exceptions\CapabilityUnavailable
     * @throws Exceptions\MissingConfiguration
     */
    public function getLatest(string $type, int $limit) : Results {
        $types = $this->getPropertyTypes();
        if (!array_key_exists($type, $types)) {
            throw new \Exception('Invalid property type requested');
        }
        $timestamp_field = 'ModificationTimestamp';
        $query = "({$timestamp_field}=".now()->addDays(-360)->format('Y-m-d')."T00:00:00+) AND (PropertyType=$type)";
        $data = $this->getClient()->Search('Property', 'Property', $query, ['Limit' => $limit]);//, ['Limit' => $limit]);
        return $data;
    }

    /**
     * An easy way to keep tabs on what property types are valid.
     * @return string[]
     */
    public function getPropertyTypes() : array {
        return [
            'BUSO' => 'Business Opportunity',
            'COML' => 'Commercial Lease',
            'COMS' => 'Commercial Sale',
            'FARM' => 'Farm',
            'LAND' => 'Land',
            'MPRK' => 'Manufactured In Park',
            'RESI' => 'Residential',
            'RINC' => 'Residential Income',
            'RLSE' => 'Residential Lease'
        ];
    }

    /**
     * Attempt to get an agent by MLS ID
     * @param $agentId
     * @return null
     */
    public function getAgentByMlsId($agentId) {
        $rets = \ReColorado::getClient();
        $query = "(MemberMlsId=$agentId)";
        $data = $rets->Search('Member', 'Member', $query);//, ['Limit' => $limit]);//, ['Limit' => $limit]);
        return $data->count() ? $data->first() : null;
    }

    /**
     * Attempt to get an office by MLS ID
     * @param $agentId
     * @return null
     */
    public function getOfficeByMlsId($agentId) {
        $rets = \ReColorado::getClient();
        $query = "(OfficeMlsId=$agentId)";
        $data = $rets->Search('Office', 'Office', $query, ['Limit' => 1]);
        return $data->count() ? $data->first() : null;
    }

    /**
     * Attempt to get an office by name
     * @param $agentId
     * @return null
     */
    public function getOfficeByName($name) {
        $rets = \ReColorado::getClient();
        $query = "(OfficeName=$name)";
        $data = $rets->Search('Office', 'Office', $query, ['Limit' => 1]);
        return $data->count() ? $data->first() : null;
    }

    /**
     * Load all agents in an office.
     * @param $mlsId
     * @return null
     */
    public function getAgentByOfficeByMlsId($mlsId) {
        $rets = \ReColorado::getClient();
        $query = "(OfficeMlsId=$mlsId)";
        $data = $rets->Search('Member', 'Member', $query);
        return $data->count() ? $data : null;
    }
}