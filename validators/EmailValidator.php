<?php


namespace core\validators;


use core\web\App;

class EmailValidator extends Validator implements ValidatorInterface
{

    public $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';

    public $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';

    public $allowName = false;

    public $checkDNS = false;

    public $enableIDN = false;

    /**
     * @inheritdoc
     */
    public function init(){
        if ($this->enableIDN && !function_exists('idn_to_ascii')) {
            throw new \Exception('In order to use IDN validation intl extension must be installed and enabled.');
        }
        if ($this->message === null) {
            $this->message = App::$instance->translate('crl', '{attribute} is not a valid email address');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validate($value)
    {
        if (!is_string($value)) {
            $valid = false;
        } elseif (!preg_match('/^(?P<name>(?:"?([^"]*)"?\s)?)(?:\s+)?(?:(?P<open><?)((?P<local>.+)@(?P<domain>[^>]+))(?P<close>>?))$/i', $value, $matches)) {
            $valid = false;
        } else {
            if ($this->enableIDN) {
                $matches['local'] = $this->idnToAscii($matches['local']);
                $matches['domain'] = $this->idnToAscii($matches['domain']);
                $value = $matches['name'] . $matches['open'] . $matches['local'] . '@' . $matches['domain'] . $matches['close'];
            }
            if (strlen($matches['local']) > 64) {
                // The maximum total length of a user name or other local-part is 64 octets. RFC 5322 section 4.5.3.1.1
                // http://tools.ietf.org/html/rfc5321#section-4.5.3.1.1
                $valid = false;
            } elseif (strlen($matches['local'] . '@' . $matches['domain']) > 254) {
                // There is a restriction in RFC 2821 on the length of an address in MAIL and RCPT commands
                // of 254 characters. Since addresses that do not fit in those fields are not normally useful, the
                // upper limit on address lengths should normally be considered to be 254.
                //
                // Dominic Sayers, RFC 3696 erratum 1690
                // http://www.rfc-editor.org/errata_search.php?eid=1690
                $valid = false;
            } else {
                $valid = preg_match($this->pattern, $value) || $this->allowName && preg_match($this->fullPattern, $value);
                if ($valid && $this->checkDNS) {
                    $valid = checkdnsrr($matches['domain'] . '.', 'MX') || checkdnsrr($matches['domain'] . '.', 'A');
                }
            }
        }
        return $valid ? true : $this->message;
    }

    private function idnToAscii($idn)
    {
        if (PHP_VERSION_ID < 50600) {
            return idn_to_ascii($idn);
        }
        return idn_to_ascii($idn, 0, INTL_IDNA_VARIANT_UTS46);
    }
}