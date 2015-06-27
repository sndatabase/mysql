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

namespace SNDatabase\MySQL\Impl;
use SNDatabase\MySQL\MySQLConnection;
use SNDatabase\ConnectionFailedException;
use SNDatabase\DBException;
use SNDatabase\MySQL\MySQLPreparedStatement;

/**
 * Description of MySQLiConnection
 *
 * @author Samy Naamani <samy@namani.net>
 */
class MySQLiConnection extends MySQLConnection {
    /**
     *
     * @var \mysqli
     */
    private $cnx;
    protected function escapeString($string) {
        return sprintf("'%s'", $this->cnx->real_escape_string($string));
    }

    public function connect() {
        try {
            mysqli_report(MYSQLI_REPORT_STRICT);
            $this->cnx = new \mysqli(parse_url($this->connectionString, PHP_URL_HOST), parse_url($this->connectionString, PHP_URL_USER), parse_url($this->connectionString, PHP_URL_PASS), parse_url($this->connectionString, PHP_URL_FRAGMENT), parse_url($this->connectionString, PHP_URL_PORT), parse_url($this->connectionString, PHP_URL_PATH));
        } catch (\mysqli_sql_exception $ex) {
            throw new ConnectionFailedException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    public function lastInsertId() {
        return $this->cnx->insert_id;
    }

    public function prepare($statement) {
        return new MySQLPreparedStatement($this, $statement);
    }

    public function query($statement) {
        try {
            $result = $this->cnx->query($statement);
            return ($result instanceof \mysqli_result) ? new MySQLiResult($this, $result) : $result;
        } catch (\mysqli_sql_exception $ex) {
            throw new DBException($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    public function countLastAffectedRows() {
        return $this->cnx->affected_rows;
    }

}
