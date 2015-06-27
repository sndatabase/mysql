<?php

/*
 * The MIT License
 *
 * Copyright 2015 Samy Naamani <samy@namani.net>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SNDatabase\MySQL;
use SNDatabase\PreparedStatement;
use SNDatabase\Result;
use SNDatabase\InvalidParameterNumberException;

/**
 * Description of MySQLPreparedStatement
 *
 * @author Samy Naamani <samy@namani.net>
 */
class MySQLPreparedStatement extends PreparedStatement {
    /**
     *
     * @var Result|null
     */
    private $result = null;

    /**
     *
     * @var int
     */
    private $affectedRows = 0;

    /**
     *
     * @var string
     */
    private $query;
    /**
     *
     * @var array
     */
    private $tags = array();

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var array
     */
    private $values;

    public function __construct(MySQLConnection $cnx, $query) {
        parent::__construct($cnx);
        $this->query = $query;
        $parsed = self::$parser->parse($query);
        foreach($parsed as &$elem) $this->walk1 ($elem);
        $this->name = uniqid('stmt_');
        $this->connection->exec(sprintf('PREPARE %s FROM \'%s\';', $this->name, self::$creator->create($parsed)));
    }
    private function walk1(&$elem) {
        if (is_array($elem)) {
            if (array_key_exists('expr_type', $elem)) {
                if ($elem['expr_type'] == 'colref') {
                    if ($elem['base_expr'] == '?'  or preg_match('#^:[a-z][a-z0-9]*$#i', $elem['base_expr'])) {
                        $this->tags[] = $elem['base_expr'];
                        $elem = array(
                            'expr_type' => 'colref',
                            'base_expr' => '?'
                        );
                    }
                }
            } else
                $this->walk($elem);
        }
    }
    protected function doBind() {
        $params = $this->getParameters();
        $tags = $this->tags;
        $values = array();
        foreach($params as $tag => $param) {
            if(is_int($tag) or ctype_digit($tag)) continue;
            $key = array_search($tag, $tags);
            if($key === false) throw new InvalidParameterNumberException;
            $values[$key] = $this->connection->quote($param['param'], $param['type']);
            unset($params[$tag]);
            unset($tags[$key]);
        }
        foreach($params as $param) {
            $key = array_search('?', $tags);
            if($key === false) throw new InvalidParameterNumberException;
            $values[$key] = $this->connection->quote($param['param'], $param['type']);
            unset($tags[$key]);
        }
        $this->values = $values;
    }

    protected function doExecute() {
        $values = $this->values;
        $query1 = implode("\n", array_map(function($key, $value) { return sprintf('SET @param%s = %s;', $key, $value);}, array_keys($values), array_values($values)));
        $query2 = sprintf('EXECUTE %s USING %s;', $this->name, implode(', ', array_map(function($key) {return sprintf('@param%s');}, array_keys($values))));
        $result = $this->connection->query("$query1\n$query2");
        $this->result = ($result instanceof Result) ? $result : null;
        $this->affectedRows = $this->connection->countLastAffectedRows();
        return true;
    }

    protected function getAffectedRows() {
        return $this->affectedRows;
    }

    public function getResultset() {
        return $this->result;
    }

    public function __destruct() {
        $this->connection->exec(sprintf('DEALLOCATE PREPARE %s;', $this->name));
    }

}
