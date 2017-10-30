<?php

namespace core\translate;

interface TranslateSourceInterface {

    public function translate($message, $language, $params = []);

}
