<?php

class URLShortener
{
    // All characters allowed in shortened URL codes
    public static $allowedCharacters = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    );
    // Allowed characters that should not be included in randomly-generated codes
    public static $randExcludedCharacters = array(
        'C', 'I', 'K', 'M', 'O', 'S', 'U', 'V', 'W', 'X', 'Z',
        'c', 'k', 'l', 'm', 'o', 's', 'u', 'v', 'w', 'x', 'z',
        '0', '1', '5',
    );

    public static $minLength = 4;  // minimum allowable code length
    public static $randLength = 5;  // length of randomly-generated codes
    public static $maxLength = 20;  // maximum allowable code length

    /**
     * Get the characters that the random generator uses.
     * @return array
     */
    public static function getRandCharacters() {
        return array_diff( self::$allowedCharacters, self::$randExcludedCharacters );
    }

    /**
     * Get a database handle.
     * @return PDO
     */
    public static function getDB() {
        // Ensure we have our database credentials
        require_once 'config.php';
        global $dbHost, $dbName, $dbUser, $dbPass;

        // Create the database handle and set it to use exceptions for errors
        $db = new PDO( "mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass );
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

        return $db;
    }

    /**
     * Is the specified URL path unique (not yet used)?
     * @param string $code The URL path being checked
     * @return bool
     */
    public static function isUnique( $code ) {
        try {
            $db = self::getDB();
            $st = $db->prepare( 'SELECT COUNT(*) FROM redirect WHERE code = :code' );
            $st->execute( array( 'code' => $code ) );
            return $st->fetch()[0] == 0;
        } catch ( PDOException $e ) {
            return false;
        }
    }

    public static function isValid( $code ) {
        $length = strlen( $code );
        if ( $length < self::$minLength || $length > self::$maxLength ) {
            return false;
        }

        for ( $i = 0; $i < $length; $i++ ) {
            if ( !in_array( $code[$i], self::$allowedCharacters ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generates a random unique code.
     * @return string
     */
    public static function generateCode() {
        $characters = self::getRandCharacters();
        while ( true ) {
            $keys = array_rand( $characters, self::$randLength );
            $code = '';
            for ( $i = 0; $i < self::$randLength; $i++ ) {
                $code .= $characters[$keys[$i]];
            }

            if ( self::isUnique( $code ) ) {
                return $code;
            }
        }
    }

    /**
     * Create a (or retrieve an existing) redirect code for a specified target URL.
     * @param string $code The redirect code to be used (or an empty string to have one generated).
     * @param string $target The destination URL.
     * @return null|string
     */
    public static function createCode( $code, $target ) {
        // Sanitize and validate $target
        $target = filter_var( $target, FILTER_SANITIZE_URL );
        if ( !filter_var( $target, FILTER_VALIDATE_URL ) ) {
            return null;
        }

        // Generate or validate $code
        if ( $code === '' ) {
            if ( $previous = self::getCode( $target ) ) {
                // TODO: possibly ensure the characters in $previous don't include any in self::$randExcludedCharacters
                return $previous;
            } else {
                $code = self::generateCode();
            }
        } else {
            if ( !self::isValid( $code ) ) {
                return null;
            }
        }

        // Add new code to db
        try {
            $db = self::getDB();
            $st = $db->prepare( 'INSERT INTO redirect (`code`, `target`) VALUES (:code, :target)' );
            $st->execute( array( 'code' => $code, 'target' => $target ) );
            return $code;
        } catch ( PDOException $e ) {
            return null;
        }
    }

    /**
     * Gets a (one of possibly many) redirect code for a target/destination URL.
     * @param $target
     * @return null|string
     */
    public static function getCode( $target ) {
        try {
            $db = self::getDB();
            $st = $db->prepare( 'SELECT code FROM redirect WHERE target = :target' );
            $st->execute( array( 'target' => $target ) );
            if ( $result = $st->fetch() ) {
                return $result['code'];
            } else {
                return null;
            }
        } catch ( PDOException $e ) {
            return null;
        }
    }

    /**
     * Gets the target/destination URL of a redirect code.
     * @param $code
     * @return null|string
     */
    public static function getTarget( $code ) {
        try {
            $db = self::getDB();
            $st = $db->prepare( 'SELECT target FROM redirect WHERE code = :code' );
            $st->execute( array( 'code' => $code ) );
            if ( $result = $st->fetch() ) {
                return $result['target'];
            } else {
                return null;
            }
        } catch ( PDOException $e ) {
            return null;
        }
    }
}