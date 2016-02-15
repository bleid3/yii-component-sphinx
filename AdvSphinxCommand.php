<?php

/**
 * Created by PhpStorm.
 * User: bleid3
 * Date: 01.12.15
 * Time: 11:15
 */
class AdvSphinxCommand
{
    /**
     * array parts query
     * @var
     */
    private $_query;

    /**
     * Object have connection to sphinx
     * @var AdvSphinxConnection
     */
    private $_connection;

    /**
     * @var
     */
    private $_error;

    /**
     * @var
     */
    private $_warning;

    /**
     * AdvSphinxCommand constructor.
     * @param AdvSphinxConnection $connection
     */
    public function __construct(AdvSphinxConnection $connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Executes the query
     * @param $prepareOutput
     * @return mixed
     * @throws CException
     */
    public function query($prepareOutput)
    {
        return $this->execute($prepareOutput);
    }

    /**
     * to select fields from index
     * @param $select
     * @return $this
     */
    public function select($select)
    {
        $this->_query['select'] = $select;
        return $this;
    }

    /**
     * choose index for query
     * @param $index
     * @return $this
     */
    public function fromIndex($index)
    {
        $this->_query['index'] = $index;
        return $this;
    }

    /**
     * @param $query
     * @param bool $escape
     * @return $this
     * @throws CDbException
     */
    public function match($query, $escape = false)
    {
        $query = trim($query);
        if ($escape) {
            $query = $this->escapeString($query);
        }

        $this->processCondition($query, 'match');
        return $this;
    }

    /**
     * @param $field
     * @param $query
     * @param string $operator
     * @param bool $escape
     * @return $this
     * @throws CDbException
     */
    public function fieldMatch($field, $query, $operator = '&', $escape = false)
    {
        $query = trim($query);
        if ($escape) {
            $query = $this->escapeString($query);
        }

        $query = '@' . $field . ' (' . $query . ')';

        $this->processCondition($query, 'match', true, $operator);
        return $this;
    }

    /**
     * @param $query
     * @param bool $escape
     * @return $this
     * @throws CDbException
     */
    public function andMatch($query, $escape = false)
    {
        $query = trim($query);
        if ($escape) {
            $query = $this->escapeString($query);
        }

        $this->processCondition($query, 'match', true, '&');
        return $this;
    }

    /**
     * @param $query
     * @param bool $escape
     * @return $this
     * @throws CDbException
     */
    public function orMatch($query, $escape = false)
    {
        $query = trim($query);
        if ($escape) {
            $query = $this->escapeString($query);
        }

        $this->processCondition($query, 'match', true, '|');
        return $this;
    }

    /**
     * @param $where
     * @return $this
     * @throws CDbException
     */
    public function where($where)
    {
        $this->processCondition($where, 'where');
        return $this;
    }

    /**
     * @param $where
     * @throws CDbException
     */
    public function andWhere($where)
    {
        $this->processCondition($where, 'where', true, '&');
    }

    /**
     * @param $where
     * @throws CDbException
     */
    public function orWhere($where)
    {
        $this->processCondition($where, 'where', true, '|');
    }

    /**
     * @param $condition
     * @param $keyInQuery
     * @param bool $addToQuery
     * @param string $rawOperator
     * @return mixed
     * @throws CDbException
     */
    private function processCondition($condition, $keyInQuery, $addToQuery = false, $rawOperator = '&')
    {
        $operator = str_ireplace(array('AND', 'OR'), array('&', '|'), $rawOperator);
        if ($operator !== '|' && $operator !== '&') {
            throw new CDbException(Yii::t('yii', 'Unknown operator "{operator}".', array('{operator}' => $rawOperator)));
        }

        $isDataInQuery = isset($this->_query[$keyInQuery]) && $this->_query[$keyInQuery];
        $this->_query[$keyInQuery] = $addToQuery && $isDataInQuery ? '(' . $this->_query[$keyInQuery] . ')' . $operator . '(' . $condition . ')' : $condition;
        return $this->_query[$keyInQuery];
    }

    /**
     * @param $string
     * @return mixed
     */
    public function escapeString($string)
    {
        $string = $this->_connection->getSphinxClient()->EscapeString($string);
        return $string;
    }

    /**
     * @param $comment
     * @return $this
     */
    public function comment($comment)
    {
        $this->_query['comment'] = $comment;
        return $this;
    }

    /**
     * @param $weights
     * @return $this
     */
    public function setFieldWeights($weights)
    {
        $this->_connection->getSphinxClient()->SetFieldWeights($weights);
        return $this;
    }

    /**
     * @param $attribute
     * @param $value
     * @param bool $exclude
     * @return $this
     */
    public function addFilter($attribute, $value, $exclude = false)
    {
        if (is_string($value)) {
            $this->_connection->getSphinxClient()->SetFilterString($attribute, $value, $exclude);
        } elseif (is_array($value)) {
            $this->_connection->getSphinxClient()->SetFilter($attribute, $value, $exclude);
        } else {
            $this->_connection->getSphinxClient()->SetFilter($attribute, array($value), $exclude);
        }

        return $this;
    }

    /**
     * @param $attribute
     * @param $min
     * @param $max
     * @param bool $exclude
     * @return $this
     */
    public function addFilterRange($attribute, $min, $max, $exclude = false)
    {
        if (is_float($min) || is_float($max)) {
            $this->_connection->getSphinxClient()->SetFilterFloatRange($attribute, floatval($min), floatval($max), $exclude);
        } elseif (is_numeric($min) && is_numeric($max)) {
            $this->_connection->getSphinxClient()->SetFilterRange($attribute, $min, $max, $exclude);
        }

        return $this;
    }

    /**
     * @param $attribute
     * @param int $mode
     * @param string $order
     * @return $this
     */
    public function group($attribute, $mode = SPH_GROUPBY_ATTR, $order = '@group desc')
    {
        $this->_connection->getSphinxClient()->setGroupBy($attribute, $mode, $order);
        return $this;
    }


    /**
     * @param $attribute
     * @param string $mode
     * @return $this
     */
    public function order($attribute, $mode = 'asc')
    {
        $resultMode = strtolower($mode) == 'asc' ? SPH_SORT_ATTR_ASC : SPH_SORT_ATTR_DESC;
        $this->_connection->getSphinxClient()->SetSortMode($resultMode, $attribute);

        return $this;
    }

    /**
     * @param $limit
     * @param int $maxMatches
     * @param int $cutoff
     * @return $this
     */
    public function limit($limit, $maxMatches = 0, $cutoff = 0)
    {
        $this->_query['limit'] = $limit;
        $this->_query['maxMatches'] = $maxMatches;
        $this->_query['cutoff'] = $cutoff;
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->_query['offset'] = $offset;
        return $this;
    }

    /**
     * @return string
     */
    private function getLastError()
    {
        return $this->_connection->getSphinxClient()->getLastError();
    }

    /**
     * @return string
     */
    private function getLastWarning()
    {
        return $this->_connection->getSphinxClient()->getLastWarning();
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return mixed
     */
    public function getWarning()
    {
        return $this->_warning;
    }

    /**
     * @param bool $prepareOutput
     * @return mixed
     * @throws CException
     */
    protected function execute($prepareOutput = true)
    {
        $query = isset($this->_query['match']) ? $this->_query['match'] : null;
        $index = isset($this->_query['index']) ? $this->_query['index'] : null;
        $comment = isset($this->_query['comment']) ? $this->_query['comment'] : '';
        if ($query === null) {
            throw new CException('Query not set! Set in AdvSphinxCommand::match()');
        }

        if (!$index) {
            throw new CException('Index not set! Set in AdvSphinxCommand::fromIndex()');
        }


        $offset = isset($this->_query['offset']) ? $this->_query['offset'] : 0;
        $limit = isset($this->_query['limit']) ? $this->_query['limit'] : false;
        $maxMatches = isset($this->_query['maxMatches']) ? $this->_query['maxMatches'] : 0;
        $cutoff = isset($this->_query['cutoff']) ? $this->_query['cutoff'] : 0;

        if ($select = $this->getSelect()) {
            $this->_connection->getSphinxClient()->SetSelect($select);
        }

        if ($limit) {
            $this->_connection->getSphinxClient()->setLimits($offset, $limit, $maxMatches, $cutoff);
        }

        $result = $this->_connection->getSphinxClient()->query($query, $index, $comment);

        if (!isset($result['matches'])) {
            $result['matches'] = array();
        }

        $this->_error = $this->getLastError();
        $this->_warning = $this->getLastWarning();

        if ($prepareOutput) {
            $listResult = $this->prepareResult($result);
            $finishResult['error'] = $this->getError();
            $finishResult['warning'] = $this->getWarning();
            $finishResult['list'] = $listResult;
            $finishResult['total'] = isset($result['total']) ? $result['total'] : 0;
            $finishResult['totalFound'] = isset($result['total_found']) ? $result['total_found'] : 0;
            $finishResult['totalReallyFound'] = count($listResult);
            $finishResult['words'] = isset($result['words']) ? $result['words'] : array();;
        } else {
            $finishResult = $result;
        }

        $this->_connection->resetClient();
        return $finishResult;
    }

    /**
     * @return null|string
     */
    public function getSelect()
    {
        $select = null;
        if (isset($this->_query['where']) && !empty($this->_query['where'])) {
            $tempNameFilter = 'temp_name_' . time();
            if (isset($this->_query['select']) && !empty($this->_query['select'])) {
                $select = $this->_query['select'] . ',' . 'IF(' . $this->_query['where'] . ', 1, 0) AS ' . $tempNameFilter;
            } else {
                $select = '*,' . 'IF(' . $this->_query['where'] . ', 1, 0) AS ' . $tempNameFilter;
            }
            $this->addFilter($tempNameFilter, 1);
        } else {
            $select = isset($this->_query['select']) && !empty($this->_query['select']) ? $this->_query['select'] : '*';
        }

        return $select;
    }

    /**
     * @param $data
     * @return array
     */
    public function prepareResult($data)
    {
        $list = array();

        foreach ($data['matches'] as $id => $data) {
            $resData = array();
            $resData['doc_id'] = $id;
            foreach ($data['attrs'] as $key => $value) {
                $resData[$key] = $value;
            }

            $list[$id] = $resData;
        }

        return $list;
    }

}