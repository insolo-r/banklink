<?php

namespace Banklink\Response;

class AuthResponse extends Response
{
    protected $personal_code;

    public function setPersonalCode($personal_code)
    {
        $this->personal_code = $personal_code;
    }
    
    public function getPersonalCode()
    {
        return $this->personal_code;
    }

}