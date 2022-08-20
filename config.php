<?php
class DB
{
	const VERSION = 'local';
    const DB_CONNECT_IP = '127.0.0.1';
    const DB_CONNECT_DB = 'vertical_dtf';
    const DB_CONNECT_USER = 'vertical_dtf';
    const DB_CONNECT_PASS = 'hMty5RHlkL0lbABT';

    static function getDatabaseInfo() 
    {
        return [
			'ip' => self::DB_CONNECT_IP,
			'db' => self::DB_CONNECT_DB,
			'user' => self::DB_CONNECT_USER,
			'pass' => self::DB_CONNECT_PASS,
		];
    }
}

?>