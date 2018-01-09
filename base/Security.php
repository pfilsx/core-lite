<?php


namespace core\base;


use core\helpers\StringHelper;

class Security extends BaseObject
{
    public $macHash = 'sha256';

    public $passwordHashCost = 13;

    private $_useLibreSSL;
    private $_randomFile;

    public function hashData($data, $key, $rawHash = false)
    {
        $hash = hash_hmac($this->macHash, $data, $key, $rawHash);
        if (!$hash) {
            throw new \Exception('Failed to generate HMAC with hash algorithm: ' . $this->macHash);
        }
        return $hash . $data;
    }

    public function validateData($data, $key, $rawHash = false)
    {
        $test = @hash_hmac($this->macHash, '', '', $rawHash);
        if (!$test) {
            throw new \Exception('Failed to generate HMAC with hash algorithm: ' . $this->macHash);
        }
        $hashLength = StringHelper::byteLength($test);
        if (StringHelper::byteLength($data) >= $hashLength) {
            $hash = StringHelper::byteSubstr($data, 0, $hashLength);
            $pureData = StringHelper::byteSubstr($data, $hashLength, null);
            $calculatedHash = hash_hmac($this->macHash, $pureData, $key, $rawHash);
            if ($this->compareString($hash, $calculatedHash)) {
                return $pureData;
            }
        }
        return false;
    }

    public function compareString($expected, $actual)
    {
        $expected .= "\0";
        $actual .= "\0";
        $expectedLength = StringHelper::byteLength($expected);
        $actualLength = StringHelper::byteLength($actual);
        $diff = $expectedLength - $actualLength;
        for ($i = 0; $i < $actualLength; $i++) {
            $diff |= (ord($actual[$i]) ^ ord($expected[$i % $expectedLength]));
        }
        return $diff === 0;
    }

    public function generatePasswordHash($password, $cost = null)
    {
        if ($cost === null) {
            $cost = $this->passwordHashCost;
        }
        if (function_exists('password_hash')) {
            /* @noinspection PhpUndefinedConstantInspection */
            return password_hash($password, PASSWORD_DEFAULT, ['cost' => $cost]);
        }
        $salt = $this->generateSalt($cost);
        $hash = crypt($password, $salt);
        // strlen() is safe since crypt() returns only ascii
        if (!is_string($hash) || strlen($hash) !== 60) {
            throw new \Exception('Unknown error occurred while generating hash.');
        }
        return $hash;
    }

    public function validatePassword($password, $hash)
    {
        if (!is_string($password) || $password === '') {
            throw new \Exception('Password must be a string and cannot be empty.');
        }
        if (!preg_match('/^\$2[axy]\$(\d\d)\$[\.\/0-9A-Za-z]{22}/', $hash, $matches)
            || $matches[1] < 4
            || $matches[1] > 30
        ) {
            throw new \Exception('Hash is invalid.');
        }
        if (function_exists('password_verify')) {
            return password_verify($password, $hash);
        }
        $test = crypt($password, $hash);
        $n = strlen($test);
        if ($n !== 60) {
            return false;
        }
        return $this->compareString($test, $hash);
    }

    protected function generateSalt($cost = 13)
    {
        $cost = (int) $cost;
        if ($cost < 4 || $cost > 31) {
            throw new \Exception('Cost must be between 4 and 31.');
        }
        // Get a 20-byte random string
        $rand = $this->generateRandomKey(20);
        // Form the prefix that specifies Blowfish (bcrypt) algorithm and cost parameter.
        $salt = sprintf('$2y$%02d$', $cost);
        // Append the random salt data in the required base64 format.
        $salt .= str_replace('+', '.', substr(base64_encode($rand), 0, 22));
        return $salt;
    }

    public function generateRandomKey($length = 32)
    {
        if (!is_int($length)) {
            throw new \Exception('First parameter ($length) must be an integer');
        }
        if ($length < 1) {
            throw new \Exception('First parameter ($length) must be greater than 0');
        }
        // always use random_bytes() if it is available
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        // The recent LibreSSL RNGs are faster and likely better than /dev/urandom.
        // Parse OPENSSL_VERSION_TEXT because OPENSSL_VERSION_NUMBER is no use for LibreSSL.
        // https://bugs.php.net/bug.php?id=71143
        if ($this->_useLibreSSL === null) {
            $this->_useLibreSSL = defined('OPENSSL_VERSION_TEXT')
                && preg_match('{^LibreSSL (\d\d?)\.(\d\d?)\.(\d\d?)$}', OPENSSL_VERSION_TEXT, $matches)
                && (10000 * $matches[1]) + (100 * $matches[2]) + $matches[3] >= 20105;
        }
        // Since 5.4.0, openssl_random_pseudo_bytes() reads from CryptGenRandom on Windows instead
        // of using OpenSSL library. LibreSSL is OK everywhere but don't use OpenSSL on non-Windows.
        if ($this->_useLibreSSL
            || (
                DIRECTORY_SEPARATOR !== '/'
                && substr_compare(PHP_OS, 'win', 0, 3, true) === 0
                && function_exists('openssl_random_pseudo_bytes')
            )
        ) {
            $key = openssl_random_pseudo_bytes($length, $cryptoStrong);
            if ($cryptoStrong === false) {
                throw new \Exception(
                    'openssl_random_pseudo_bytes() set $crypto_strong false. Your PHP setup is insecure.'
                );
            }
            if ($key !== false && StringHelper::byteLength($key) === $length) {
                return $key;
            }
        }
        // mcrypt_create_iv() does not use libmcrypt. Since PHP 5.3.7 it directly reads
        // CryptGenRandom on Windows. Elsewhere it directly reads /dev/urandom.
        if (function_exists('mcrypt_create_iv')) {
            $key = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            if (StringHelper::byteLength($key) === $length) {
                return $key;
            }
        }
        // If not on Windows, try to open a random device.
        if ($this->_randomFile === null && DIRECTORY_SEPARATOR === '/') {
            // urandom is a symlink to random on FreeBSD.
            $device = PHP_OS === 'FreeBSD' ? '/dev/random' : '/dev/urandom';
            // Check random device for special character device protection mode. Use lstat()
            // instead of stat() in case an attacker arranges a symlink to a fake device.
            $lstat = @lstat($device);
            if ($lstat !== false && ($lstat['mode'] & 0170000) === 020000) {
                $this->_randomFile = fopen($device, 'rb') ?: null;
                if (is_resource($this->_randomFile)) {
                    // Reduce PHP stream buffer from default 8192 bytes to optimize data
                    // transfer from the random device for smaller values of $length.
                    // This also helps to keep future randoms out of user memory space.
                    $bufferSize = 8;
                    if (function_exists('stream_set_read_buffer')) {
                        stream_set_read_buffer($this->_randomFile, $bufferSize);
                    }
                    // stream_set_read_buffer() isn't implemented on HHVM
                    if (function_exists('stream_set_chunk_size')) {
                        stream_set_chunk_size($this->_randomFile, $bufferSize);
                    }
                }
            }
        }
        if (is_resource($this->_randomFile)) {
            $buffer = '';
            $stillNeed = $length;
            while ($stillNeed > 0) {
                $someBytes = fread($this->_randomFile, $stillNeed);
                if ($someBytes === false) {
                    break;
                }
                $buffer .= $someBytes;
                $stillNeed -= StringHelper::byteLength($someBytes);
                if ($stillNeed === 0) {
                    // Leaving file pointer open in order to make next generation faster by reusing it.
                    return $buffer;
                }
            }
            fclose($this->_randomFile);
            $this->_randomFile = null;
        }
        throw new \Exception('Unable to generate a random key');
    }

    public function generateRandomString($length = 32)
    {
        if (!is_int($length)) {
            throw new \Exception('First parameter ($length) must be an integer');
        }
        if ($length < 1) {
            throw new \Exception('First parameter ($length) must be greater than 0');
        }
        $bytes = $this->generateRandomKey($length);
        return substr(StringHelper::base64UrlEncode($bytes), 0, $length);
    }

    /**
     * Masks a token to make it uncompressible.
     * Applies a random mask to the token and prepends the mask used to the result making the string always unique.
     * Used to mitigate BREACH attack by randomizing how token is outputted on each request.
     * @param string $token An unmasked token.
     * @return string A masked token.
     */
    public function maskToken($token)
    {
        // The number of bytes in a mask is always equal to the number of bytes in a token.
        $mask = $this->generateRandomKey(StringHelper::byteLength($token));
        return StringHelper::base64UrlEncode($mask . ($mask ^ $token));
    }
    /**
     * Unmasks a token previously masked by `maskToken`.
     * @param string $maskedToken A masked token.
     * @return string An unmasked token, or an empty string in case of token format is invalid.
     */
    public function unmaskToken($maskedToken)
    {
        $decoded = StringHelper::base64UrlDecode($maskedToken);
        $length = StringHelper::byteLength($decoded) / 2;
        // Check if the masked token has an even length.
        if (!is_int($length)) {
            return '';
        }
        return StringHelper::byteSubstr($decoded, $length, $length) ^ StringHelper::byteSubstr($decoded, 0, $length);
    }

}