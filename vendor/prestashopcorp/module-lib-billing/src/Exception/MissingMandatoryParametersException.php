<?php

namespace PrestaShopCorp\Billing\Exception;

class MissingMandatoryParametersException extends \Exception
{
    private $missingParameters = [];

    /**
     * @param string[] $missingParameters
     * @param int $code
     */
    public function __construct($missingParameters = null, $code = 0, $previous = null)
    {
        $this->missingParameters = array_flip($missingParameters);
        $message = sprintf('Some mandatory parameters are missing ("%s")', implode('", "', $this->missingParameters));

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getMissingParameters()
    {
        return $this->missingParameters;
    }
}
