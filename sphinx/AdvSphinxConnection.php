<?php

/**
 * Created by PhpStorm.
 * User: bleid3
 * Date: 01.12.15
 * Time: 11:15
 *
 * $sphinx = Yii::app()->componentName->createCommand();
 * $sphinx->select('*');
 * $sphinx->fromIndex('*');
 * $sphinx->match($search, true);
 * $sphinx->where('post=4083 & group=78236317');
 * $sphinx->addFilter('range', array(1,2));
 * $sphinx->addFilter('category', 1);
 * $sphinx->addFilterRange('er_post', 0.2, 3);
 * $sphinx->offset(1);
 * $sphinx->limit(8);
 * $sphinx->order('group', 'desc');
 * $sphinx->group('group');
 * $result = $sphinx->query(true);
 */
class AdvSphinxConnection extends CApplicationComponent
{
    /**
     * @var string
     */
    public $server = 'localhost';
    /**
     * @var int
     */
    public $port = 9312;
    /**
     * DEPRECATED: Do not set this property or, even better, use SphinxQL instead of an API
     */
    private $matchMode;
    /**
     * @var int
     */
    public $maxQueryTime = 0;

    /** @var SphinxClient $_client */
    private $_client;

    /**
     * Connect to sphinx
     */
    public function init()
    {
        if (!class_exists('SphinxClient', false)) {
            include_once(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'SphinxClient.php');
        }

        $this->_client = new SphinxClient;
        $this->_client->setServer($this->server, $this->port);
        $this->_client->setMaxQueryTime($this->maxQueryTime);
        $this->resetClient();
    }

    /**
     * Reset settings sphinx
     */
    public function resetClient()
    {
        $this->_client->resetFilters();
        $this->_client->resetGroupBy();
        $this->_client->setArrayResult(false);
        //DEPRECATED: Do not call this method or, even better, use SphinxQL instead of an API
        //$this->_client->setMatchMode(SPH_MATCH_EXTENDED2);
        $this->_client->setLimits(0, 20, 1000, 0);
        $this->_client->setFieldWeights(array());
        $this->_client->setSortMode(SPH_SORT_RELEVANCE, '');
        $this->_client->_error = '';
        $this->_client->_warning = '';
    }

    /**
     * @return SphinxClient
     */
    public function getSphinxClient()
    {
        return $this->_client;
    }

    /**
     * @return AdvSphinxCommand
     */
    public function createCommand()
    {
        if (!class_exists('AdvSphinxCommand', false)) {
            include_once(__DIR__ . DIRECTORY_SEPARATOR . 'AdvSphinxCommand.php');
        }
        $this->resetClient();
        return new AdvSphinxCommand($this);
    }

}
