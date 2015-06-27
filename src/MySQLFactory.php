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
use SNDatabase\Factory;
use SNDatabase\ConnectionFailedException;
use SNDatabase\DriverException;
use PDO;
use PDOException;

/**
 * Description of MySQLFactory
 *
 * @author Samy Naamani <samy@namani.net>
 */
class MySQLFactory extends Factory {
    protected static function __constructStatic() {
        if(parent::__constructStatic()) return true;
        self::setDriver('MySQL');
        return false;
    }
    public function getConnection($cnxString) {
        if(class_exists('\\PDO') and in_array('mysql', PDO::getAvailableDrivers())) {
            return new MySQLPDOConnection($cnxString);
        }
        elseif(class_exists('\\mysqli')) {
            return new Impl\MySQLiConnection($cnxString);
        }
        elseif(function_exists('\\mysql_connect')) {
            return new Impl\OldConnection($cnxString);
        }
        else throw new DriverException('No MySQL compatible extension available');
    }

}
