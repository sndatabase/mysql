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
use SNDatabase\DB;
use SNDatabase\DBException;
use SNDatabase\MySQL\MySQLPreparedStatement;

/**
 * Description of OldConnection
 *
 * @author Samy Naamani <samy@namani.net>
 */
class OldConnection extends MySQLConnection {
    /**
     *
     * @var resource
     */
    private $resource;
    protected function escapeString($string) {
        return sprintf("'%s'", @mysql_real_escape_string($string, $this->resource));
    }

    public function connect() {
        if(is_null($a = parse_url($this->connectionString, PHP_URL_PATH))) {
            $server = parse_url($this->connectionString, PHP_URL_HOST);
            if(!is_null($a = parse_url($this->connectionString, PHP_URL_PORT))) $server .= ":$a";
        } else $server = $a;
        $link = @mysql_connect($server, parse_url($this->connectionString, PHP_URL_USER), parse_url($this->connectionString, PHP_URL_PASS));
        if($link === false) throw new ConnectionFailedException(@mysql_error(), @mysql_errno());
        $this->resource = $link;
        if(!is_null($db = parse_url($this->connectionString, PHP_URL_FRAGMENT))) {
            if(!@mysql_select_db($db, $this->resource)) throw new ConnectionFailedException(@mysql_error($this->resource), @mysql_errno ($this->resource));
        }
        if(!is_null($a = $this->getAttribute(DB::ATTR_CHARSET))) {
            if(!@mysql_set_charset($a, $this->resource)) throw new ConnectionFailedException(@mysql_error($this->resource), @mysql_errno ($this->resource));
        }
    }

    public function lastInsertId() {
        return @mysql_insert_id($this->resource);
    }

    public function prepare($statement) {
        return new MySQLPreparedStatement($this, $statement);
    }

    public function query($statement) {
        $result = @mysql_query($statement, $this->resource);
        if(is_resource($result)) return new OldResult ($this, $result);
        elseif($result === false) throw new DBException(@mysql_error ($this->resource), @mysql_errno ($this->resource));
        else return $result;
    }

}
