<?php

namespace PrestaShopCorp\Billing\Exception;

class QueryParamsException extends \Exception
{
    private $notAllowedParameters = [];

    /**
     * @param string[] $notAllowedParameters
     * @param int $code
     */
    public function __construct($notAllowedParameters = null, $possibleQueryParameters = null, $code = 0, $previous = null)
    {
        $this->notAllowedParameters = array_flip($notAllowedParameters);
        $message = sprintf(
            'Some parameters aren\'t allowed ("%s"), only ("%s") are allowed',
            implode('", "', $this->notAllowedParameters),
            implode('", "', $possibleQueryParameters)
        );

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getNotAllowedParameters()
    {
        return $this->notAllowedParameters;
    }
}
