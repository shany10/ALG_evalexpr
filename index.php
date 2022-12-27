<?php

class Eval_expr
{
    public $str;
    public $arr_parenthese = [];
    public $arr_number = [];
    public $arr_signe = [];

    function __construct($str)
    {
        $this->str = $str;
    }

    public function examine_str()
    {
        preg_match('/\(([0-9+-{*\/%}]+)*\)/', $this->str, $this->arr_parenthese);
        if (count($this->arr_parenthese) != 0) {
            $this->examine($this->arr_parenthese[1]);
            $this->str = str_replace($this->arr_parenthese[0], $this->arr_number[0], $this->str);
            $this->arr_number = [];
            $this->arr_signe = [];
            $this->examine_str();
            return;
        }

        $multiplication = stripos($this->str, "*");
        $division = stripos($this->str, "/");
        $modulo = stripos($this->str, "%");
        if ($multiplication != false || $division != false || $modulo != false) {
            $this->examine($this->str);
            $this->str = str_replace($this->str, $this->arr_number[0], $this->str);
            $this->arr_number = [];
            $this->arr_signe = [];
            $this->examine_str();
            return;
        }
        $this->buffer($this->str);
        $this->str = $this->arr_number[0];
        return $this->str;
    }

    public function examine($value)
    {
        $occurence = $this->occurence_and_signe($value)[0];
        $signe = $this->occurence_and_signe($value)[1];

        if ($occurence != false && $signe == "*" || $signe == "/" || $signe == "%") {
            $str_left = substr($value, 0, $occurence);
            $taille = strlen($str_left);
            for ($i = $taille - 1; $i >= 0; $i--) {
                if ($this->control_value($i, $str_left, $taille) == false) {
                    $i = -1;
                } else {
                    $value_left = substr($str_left, $i, $taille);
                }
            }
            $str_right = substr($value, $occurence + 1);
            $taille = strlen($str_right);
            for ($i = 0; $i < $taille; $i++) {
                if ($this->control_value($i, $str_right, $taille) == false) {
                    $i = $taille;
                } else {
                    $value_right = substr($str_right, 0, $i + 1);
                }
            }
            if ($signe == "*") {
                $result = $this->multiplication($value_left, $value_right);
                $value = str_replace("$value_left*$value_right", $result, $value);
            }
            if ($signe == "/") {
                $result = $this->division($value_left, $value_right);
                $value = str_replace("$value_left/$value_right", $result, $value);
            }
            if ($signe == "%") {
                $result = $this->modulo($value_left, $value_right);
                $value = str_replace("$value_left%$value_right", $result, $value);
            }
            $this->examine($value);
            return;
        }

        $this->buffer($value);
    }

    public function occurence_and_signe($value)
    {
        $arr = [false];
        $occurence = false;
        if (stripos($value, '*')) {
            $occurence = stripos($value, '*');
            $arr = [$occurence, '*'];
        }
        if (stripos($value, '/')) {
            if ($occurence != false) {
                if (stripos($value, '/') < $occurence) {
                    $occurence = stripos($value, '/');
                    $arr = [$occurence, '/'];
                }
            } else {
                $occurence = stripos($value, '/');
                $arr = [$occurence, '/'];
            }
        }
        if (stripos($value, '%')) {
            if ($occurence != false) {
                if (stripos($value, '%') < $occurence) {
                    $occurence = stripos($value, '%');
                    $arr = [$occurence, '%'];
                }
            } else {
                $occurence = stripos($value, '%');
                $arr = [$occurence, '%'];
            }
        }
        if (count($arr) > 1) {
            return $arr;
        }
        $arr = [false, "none"];
        return $arr;
    }

    public function control_value($index, $value, $taille)
    {
        $arr = ["*", "/", "%", "+"];
        if ($value[$index] == "-" && $index != $taille - 1 && $index != 0) {
            return false;
        } elseif (in_array($value[$index], $arr)) {
            return false;
        }
        return true;
    }

    public function buffer($value)
    {
        $arr_compare = ["-", "+", "*", "/", "%", "!"];
        $str = $value . "!";
        $taille  = strlen($str);
        $value = "";
        $count = 0;
        for ($i = 0; $i < $taille; $i++) {
            if (!in_array($str[$i], $arr_compare)) {
                $value = $value . $str[$i];
                $count = 0;
            } elseif ($i == 0 || $count > 0) {
                array_push($this->arr_number, '0' . $value);
                array_push($this->arr_signe, $str[$i]);
                $count++;
                $value = "";
            } else {
                array_push($this->arr_number, $value);
                array_push($this->arr_signe, $str[$i]);
                $count++;
                $value = "";
            }
        }
        array_pop($this->arr_signe);
        if (count($this->arr_number) > 1) {
            $this->calculator();
        }
    }
    function calculator()
    {
        if ($this->arr_signe[0] == "-") {
            $value = $this->arr_number[0] - $this->arr_number[1];
            array_shift($this->arr_signe);
            array_shift($this->arr_number);
            $this->arr_number[0] = $value;
        } elseif ($this->arr_signe[0] == "+") {

            $value = $this->arr_number[0] + $this->arr_number[1];
            array_shift($this->arr_signe);
            array_shift($this->arr_number);
            $this->arr_number[0] = $value;
        }
        if (count($this->arr_number) > 1) {
            $this->calculator();
            return;
        }
    }

    public function multiplication($value_left, $value_right)
    {
        $value = $value_left * $value_right;
        return $value;
    }

    public function division($value_left, $value_right)
    {
        $value = $value_left / $value_right;
        return $value;
    }
    public function modulo($value_left, $value_right)
    {
        $value = $value_left % $value_right;
        return $value;
    }
    public function eval()
    {
        return $this->str;
    }
}

function eval_expr($expr)
{
    if (isset($expr) && is_string($expr) || is_float($expr)) {
        $eval = new Eval_expr($expr);
        $eval->examine_str();
        echo $eval->eval();
    }
}
