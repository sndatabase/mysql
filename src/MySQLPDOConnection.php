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
use SNDatabase\PDO\PDOConnection;

/**
 * Description of MySQLPDOConnection
 *
 * @author Samy Naamani <samy@namani.net>
 */
class MySQLPDOConnection extends PDOConnection {
    protected function getDSN() {
       static $keymatching = array(
           'host' => PHP_URL_HOST,
           'port' => PHP_URL_PORT,
           'unix_socket' => PHP_URL_PATH,
           'dbname' => PHP_URL_FRAGMENT
       );
       $dsn = array();
       foreach($keymatching as $match => $key) {
           if(!is_null($a = parse_url($this->connectionString, $key))) $dsn[$match] = $a;
       }
       if(!is_null($a = $this->getAttribute('charset'))) $dsn['charset'] = $a;
       return sprintf('mysql:%s', implode(';', function($key, $var) {return "$key=$var";}, array_keys($dsn), array_values($dsn)));
    }

    public function countLastAffectedRows() {
        return $this->query('SELECT AFFECTED_ROWS();')->fetchColumn();
    }

    public function startTransaction($name = null) {
        return new MySQLTransaction($this, $name);
    }

}
