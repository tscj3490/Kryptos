<?php

class Mac_Model_ValidatorsStatic
{

    static public function idValid($objToValid, $min = 1, $max = 11, $greater = 0)
    {
        if (isset($objToValid)) {
            $idObjFilter = new Zend_Filter();
            $idObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities())
                ->addFilter(new Zend_Filter_Digits());
            $objToValid = $idObjFilter->filter($objToValid);
            $idObjValid = new Zend_Validate();
            $idObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Digits())
                ->addValidator(new Zend_Validate_GreaterThan($greater))
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$idObjValid->isValid($objToValid)) {
                return $idObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    static public function varcharValid($objToValid, $min = 3, $max = 150, $NotEmpty = true)
    {
        if (isset($objToValid)) {
            $varcharObjFilter = new Zend_Filter();
            $varcharObjFilter->addFilter(new Zend_Filter_StringTrim());
            $objToValid = $varcharObjFilter->filter($objToValid);
            $varcharObjValid = new Zend_Validate();
            if ($NotEmpty) {
                $varcharObjValid->addValidator(new Zend_Validate_NotEmpty());
            }
            $varcharObjValid->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$varcharObjValid->isValid($objToValid)) {
                return $varcharObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    static public function varcharAlnumValid($objToValid, $min = 3, $max = 150)
    {
        if (isset($objToValid)) {
            $varcharObjFilter = new Zend_Filter();
            $varcharObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_Alnum())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $varcharObjFilter->filter($objToValid);
            $varcharObjValid = new Zend_Validate();
            $varcharObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Alnum())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$varcharObjValid->isValid($objToValid)) {
                return $varcharObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    static public function passwordValid($objToValid, $min = 6)
    {
        $oPassword = new Jr_Crypt_Password_Bcrypt();

        if (isset($objToValid)) {
            $passwordObjFilter = new Zend_Filter();
            $passwordObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $passwordObjFilter->filter($objToValid);
            $passwordObjValid = new Zend_Validate();
            $passwordObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min)));
            if (!$passwordObjValid->isValid($objToValid)) {
                return $passwordObjValid->getMessages();
            } else {
                $oPassword->setSalt(sha1(time()));
                $objReturm = new \stdClass();
                $objReturm->password = $oPassword->create($objToValid);
                $objReturm->salt = $oPassword->getSalt();
                return $objReturm;
            }
        }
    }

    static public function isIdenticalValid($objToValid, $min = 6, $token)
    {
        if (isset($objToValid)) {
            $passwordObjFilter = new Zend_Filter();
            $passwordObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $passwordObjFilter->filter($objToValid);
            $passwordObjValid = new Zend_Validate();
            $passwordObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min)))
                ->addValidator(new Zend_Validate_Identical(array('token' => $token)));
            if (!$passwordObjValid->isValid($objToValid)) {
                return $passwordObjValid->getMessages();
            } else {
                return true;
            }
        }
    }

    static public function mailValid($objToValid, $dbTableName, $dbField, $update = false)
    {
        if (isset($objToValid)) {
            $mailObjFilter = new Zend_Filter();
            $mailObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $mailObjFilter->filter($objToValid);
            $mailObjValid = new Zend_Validate();
            $mailObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_EmailAddress());
            if (!$update) {
                $mailObjValid->addValidator(new Zend_Validate_Db_NoRecordExists(
                    array(
                        'table' => $dbTableName,
                        'field' => $dbField
                    )
                ));
            }
            if (!$mailObjValid->isValid($objToValid)) {
                return $mailObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    static public function portIntValid($objToValid, $min = 2, $max = 6)
    {
        if (isset($objToValid)) {
            $objToValid = (int)$objToValid;
            $portIntObjFilter = new Zend_Filter();
            $portIntObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_Digits())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $portIntObjFilter->filter($objToValid);
            $portIntObjValid = new Zend_Validate();
            $portIntObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Digits())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$portIntObjValid->isValid($objToValid)) {
                return $portIntObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    /**
     * @param $objToValid
     * @return mixed
     */
    static function zipValid($objToValid)
    {
        if (isset($objToValid)) {
            $zipcObjFilter = new Zend_Filter();
            $zipcObjFilter->addFilter(new Zend_Filter_StringTrim());
            $objToValid = $zipcObjFilter->filter($objToValid);
            $zipcObjValid = new Zend_Validate();
            $zipcObjValid->addValidator(new Zend_Validate_NotEmpty())
                         ->addValidator(new Zend_Validate_PostCode());
            ;
            if (!$zipcObjValid->isValid($objToValid)) {
                return $zipcObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    /**
     * @param $objToValid
     * @return bool|string
     */
    static public function dateValid($objToValid)
    {
        if (isset($objToValid)) {
            $dateObjFilter = new Zend_Filter();
            $dateObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $dateObjFilter->filter($objToValid);
            $dateObjValid = new Zend_Validate();
            $dateObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Date('Y-m-d H:i:s'));
            if (!$dateObjValid->isValid($objToValid)) {
                return $dateObjValid->getMessages();
            } else {
                return $objToValid;
            }
        } else {
            return date("Y-m-d H:i:s");
        }
    }

    /**
     * @param $objToValid
     * @return bool|string
     */
    static public function timeValid($objToValid)
    {
        if (isset($objToValid)) {
            $dateObjFilter = new Zend_Filter();
            $dateObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $dateObjFilter->filter($objToValid);
            $dateObjValid = new Zend_Validate();
            $dateObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Date('H:i'));
            if (!$dateObjValid->isValid($objToValid)) {
                return $dateObjValid->getMessages();
            } else {
                return $objToValid;
            }
        } else {
            return date("H:i");
        }
    }

    /**
     * @param $objToValid
     * @return bool|string
     */
    static public function dateDayValid($objToValid)
    {
        if (isset($objToValid)) {
            $dateObjFilter = new Zend_Filter();
            $dateObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $dateObjFilter->filter($objToValid);
            $dateObjValid = new Zend_Validate();
            $dateObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Date('Y-m-d'));
            if (!$dateObjValid->isValid($objToValid)) {
                return $dateObjValid->getMessages();
            } else {
                return $objToValid;
            }
        } else {
            return date("Y-m-d");
        }
    }

    /**
     * @param $objToValid
     * @return int
     */
    static public function stateValid($objToValid, $greater = 0)
    {
        if (isset($objToValid)) {
            $objToValid = (int)$objToValid;
            $stateObjFilter = new Zend_Filter();
            $stateObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $stateObjFilter->filter($objToValid);
            $stateObjValid = new Zend_Validate();
            $stateObjValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_GreaterThan($greater));
            if (!$stateObjValid->isValid((int)$objToValid)) {
                return $stateObjValid->getMessages();
            } else {
                return $objToValid;
            }
        } else {
            return 1;
        }
    }

    /**
     * @param $objToValid
     * @return mixed
     */
    static public function textValid($objToValid, $min = 10, $NotEmpty = true, $html = false)
    {
        if (isset($objToValid)) {
            $textObjFilter = new Zend_Filter();
            $textObjFilter->addFilter(new Zend_Filter_StringTrim());
            if ($html) {
                $textObjFilter ->addFilter(new Zend_Filter_HtmlEntities());
            }
            $objToValid = $textObjFilter->filter($objToValid);
            $textObjValid = new Zend_Validate();
            if ($NotEmpty) {
                $textObjValid->addValidator(new Zend_Validate_NotEmpty());
            }
            $textObjValid->addValidator(new Zend_Validate_StringLength(array('min' => $min)));
            if (!$textObjValid->isValid($objToValid)) {
                return $textObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    /**
     * @param $objToValid
     * @return mixed
     */
    static public function nipValid($objToValid, $min = 10, $max = 14)
    {
        if (isset($objToValid)) {
            $nipObjFilter = new Zend_Filter();
            $nipObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_Alnum())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $nipObjFilter->filter($objToValid);
            $nipObjValid = new Zend_Validate();
            $nipObjValid
//                ->addValidator(new Zend_Validate_NotEmpty())
                    ->addValidator(new Mac_Validate_Nip())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$nipObjValid->isValid($objToValid)) {
                return $nipObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    /**
     * @param $objToValid
     */
    static public function regonValid($objToValid, $min = 9, $max = 15)
    {
        if (isset($objToValid)) {
            $regonObjFilter = new Zend_Filter();
            $regonObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $regonObjFilter->filter($objToValid);
            $regonObjValid = new Zend_Validate();
            $regonObjValid
//                ->addValidator(new Zend_Validate_NotEmpty())
                    ->addValidator(new Mac_Validate_Regon())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$regonObjValid->isValid($objToValid)) {
                return $regonObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    /**
     * @param $objToValid
     */
    static public function krsValid($objToValid, $min = 9, $max = 15)
    {
        if (isset($objToValid)) {
            $krsObjFilter = new Zend_Filter();
            $krsObjFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $krsObjFilter->filter($objToValid);
            $krsObjValid = new Zend_Validate();
            $krsObjValid
//                ->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_Digits())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$krsObjValid->isValid($objToValid)) {
                return $krsObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    /**
     * @param $objToValid
     */
    static public function phoneValid($objToValid, $min = 9, $max = 12)
    {
        if (isset($objToValid)) {
            $phoneFilter = new Zend_Filter();
            $phoneFilter->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = $phoneFilter->filter($objToValid);
            $phoneValid = new Zend_Validate();
            $phoneValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Mac_Validate_Phone())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$phoneValid->isValid($objToValid)) {
                return $phoneValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    static public function varcharToUpper($objToValid, $min = 1, $max = 100)
    {
        if (isset($objToValid)) {
            $cityFilter = new Zend_Filter();
            $cityFilter->addFilter(new Zend_Filter_StringTrim())
//                    ->addFilter(new Zend_Filter_HtmlEntities())
                ->addFilter(new Zend_Filter_StringToUpper(array('encoding' => 'UTF-8')));
            $objToValid = $cityFilter->filter($objToValid);
            $cityValid = new Zend_Validate();
            $cityValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)));
            if (!$cityValid->isValid($objToValid)) {
                return $cityValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    static public function varcharToLower($objToValid, $min = 1, $max = 100)
    {
        if (isset($objToValid)) {
            $cityFilter = new Zend_Filter();
            $cityFilter->addFilter(new Zend_Filter_StringTrim())
//                    ->addFilter(new Zend_Filter_HtmlEntities())
                ->addFilter(new Zend_Filter_StringToLower(array('encoding' => 'UTF-8')));
            $objToValid = $cityFilter->filter($objToValid);
            $cityValid = new Zend_Validate();
            $cityValid->addValidator(new Zend_Validate_NotEmpty())
                ->addValidator(new Zend_Validate_StringLength(array('min' => 1, 'max' => 100)));
            if (!$cityValid->isValid($objToValid)) {
                return $cityValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

    public static function floatValid($objToValid, $min = 3, $max = 7)
    {
        if (isset($objToValid)) {
            $portIntObjFilter = new Zend_Filter();
            $portIntObjFilter
                ->addFilter(new Zend_Filter_StringTrim())
                ->addFilter(new Zend_Filter_PregReplace(array('match' => '/,/', 'replace' => '.')))
                ->addFilter(new Zend_Filter_HtmlEntities());
            $objToValid = (float)$portIntObjFilter->filter($objToValid);
            $portIntObjValid = new Zend_Validate();
            if ($objToValid === (float)0) { //validator nie przepuszcza jeżeli liczba = 0
                $portIntObjValid->addValidator(new Zend_Validate_Float());
            } else {
                $portIntObjValid->addValidator(new Zend_Validate_NotEmpty())
                    ->addValidator(new Zend_Validate_Float())//->addValidator(new Zend_Validate_StringLength(array('min' => $min, 'max' => $max)))
                ;
            }
            if (!$portIntObjValid->isValid($objToValid)) {
                return $portIntObjValid->getMessages();
            } else {
                return $objToValid;
            }
        }
    }

}