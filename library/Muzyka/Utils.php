<?php

class Muzyka_Utils
{
    public function validateRoles($osobyDoRole, $roleDoz)
    {
        foreach ($osobyDoRole as $osobaRola) {
            foreach ($roleDoz as $rolaDozw) {
                if ($rolaDozw['rola_id'] == $osobaRola['role_id']) {
                    return 1;
                }
            }
        }
        return 0;
    }
}
