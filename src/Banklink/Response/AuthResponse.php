<?php

namespace Banklink\Response;

class AuthResponse extends Response
{
    protected $personal_code;
    protected $firstname;
    protected $lastname;

    public function setPersonalCode($personal_code)
    {
        $this->personal_code = $personal_code;
    }
    
    public function getPersonalCode()
    {
        return $this->personal_code;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }
    
    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }
    
    public function getLastname()
    {
        return $this->lastname;
    }

}