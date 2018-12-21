<?php

namespace Logeecom\Infrastructure\ORM\QueryFilter;

use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;

/**
 * Class QueryFilter
 * @package Logeecom\Infrastructure\ORM
 */
class QueryFilter
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * @var QueryCondition[]
     */
    private $conditions = array();

    /**
     * @var string
     */
    private $orderByColumn;

    /**
     * @var string
     */
    private $orderDirection = 'ASC';

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     *
     * @return QueryFilter
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     *
     * @return QueryFilter
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Sets order by column and direction
     *
     * @param string $column
     * @param string $direction
     *
     * @return QueryFilter
     * @throws QueryFilterInvalidParamException
     */
    public function orderBy($column, $direction = self::ORDER_ASC)
    {
        if (!is_string($column) || !\in_array($direction, array(self::ORDER_ASC, self::ORDER_DESC), false)) {
            throw new QueryFilterInvalidParamException('Column value must be string type and direction must be ASC or DESC');
        }

        $this->orderByColumn = $column;
        $this->orderDirection = $direction;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderByColumn()
    {
        return $this->orderByColumn;
    }

    /**
     * @return string
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }

    /**
     * @return QueryCondition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Sets where condition, if chained AND operator will be used
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     *
     * @return QueryFilter
     * @throws QueryFilterInvalidParamException
     */
    public function where($column, $operator, $value = null)
    {
        $this->validateConditionParameters($column, $operator, $value);

        $this->conditions[] = new QueryCondition('AND', $column, $operator, $value);

        return $this;
    }

    /**
     * Sets where condition, if chained OR operator will be used
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     *
     * @return QueryFilter
     * @throws QueryFilterInvalidParamException
     */
    public function orWhere($column, $operator, $value = null)
    {
        $this->validateConditionParameters($column, $operator, $value);

        $this->conditions[] = new QueryCondition('OR', $column, $operator, $value);

        return $this;
    }

    /**
     * Validates condition parameters
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     *
     * @throws QueryFilterInvalidParamException
     */
    private function validateConditionParameters($column, $operator, $value)
    {
        if (!is_string($column) || !is_string($operator)) {
            throw new QueryFilterInvalidParamException('Column and operator values must be string types');
        }

        $operator = strtoupper($operator);
        if (!\in_array($operator, Conditions::$AVAILABLE_OPERATORS, true)) {
            throw new QueryFilterInvalidParamException("Operator $operator is not supported");
        }

        $valueType = gettype($value);
        if ($valueType === 'object' && $value instanceof \DateTime) {
            $valueType = 'dateTime';
        }

        if (!array_key_exists($valueType, Conditions::$TYPE_OPERATORS)) {
            throw new QueryFilterInvalidParamException('Value type is not supported');
        }

        if (!\in_array($operator, Conditions::$TYPE_OPERATORS[$valueType], true)) {
            throw new QueryFilterInvalidParamException("Operator $operator is not supported for $valueType type");
        }
    }
}
