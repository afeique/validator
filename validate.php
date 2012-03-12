<?php

require_once 'error.php';

define('STRING_VALIDATOR', 'string');
define('ARRAY_VALIDATOR', 'array');

/**
 * the validator class is used to validate form inputs
 * 
 * it generates a validator corresponding to the type
 * of input via the ::string and ::ray methods
 * 
 * every instance method returns $this for chaining
 * 
 * not only are validations methods provided, but trivial
 * sanitation methods as well - e.g. a ::trim method
 * 
 * the class stores an internal copy of the initial
 * data provided for validation upon which it performs
 * validation and sanitation methods in the order in
 * which they are called
 * 
 * all validation errors are stored in the internal
 * ::$errors array
 * 
 * the ::errors method returns all errors as csv
 * 
 */
class validator {
  protected $type;
  protected $what;
  
  protected $errors;
  
  protected function __construct($type, $what) {
    if (!is_string($type))
      throw error::expecting_string();
    
    if ($type != STRING_VALIDATOR && $type != ARRAY_VALIDATOR)
      throw validator_error::invalid_type();
    
    if ($type == STRING_VALIDATOR && !is_string($what))
      throw error::expecting_string();
    if ($type == ARRAY_VALIDATOR && !is_array($what))
      throw error::expecting_array();
    
    $this->type = $type;
    $this->what = $what;
    
    $this->errors = array();
  }
  
  public static function string($string) {
    if (!is_string($string))
      throw error::expecting_string();
    
    $validator = new validate(STRING_VALIDATOR, $string);
    
    return $validator;
  }
  
  public static function ray(array $array) {
    $validator = new validate(ARRAY_VALIDATOR, $array);
    
    return $validator;
  }
  
  public function min_length($length) {
    if (!is_int($length) || $length < 0)
      throw error::expecting_unsigned_int();
    
    if ($this->type == STRING_VALIDATOR) {
      if (strlen($this->what) < $length)
        $this->errors[] = 'min length: '.$length;
    } elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $what) {
        if (strlen($what) < $length)
          $this->errors[] = 'each entry min length: '.$length;
      }
    }
    
    return $this;
  }
  
  public function max_length($length) {
    if (!is_int($length) || $length <= 0)
      throw error::expecting_unsigned_int_gt_zero();
    
    if ($this->type == STRING_VALIDATOR) {
      if (strlen($this->what) > $length)
        $this->errors[] = 'max length: '.$length;
    } elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $what) {
        if (strlen($what) > $length)
          $this->errors[] = 'each entry max length: '.$length;
      }
    }
    
    return $this;
  }
  
  public function trim() {
    if ($this->type == STRING_VALIDATOR)
      $this->what = trim($this->what);
    elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $what) {
        $this->what[$i] = trim($what);
        if ($this->what[$i] == '')
          unset($this->what[$i]);
      }
    }
    
    return $this;
  }
  
  public function not_empty() {
    $length = 1;
    
    if ($this->type == STRING_VALIDATOR) {
      if (strlen($this->what) < $length)
        $this->errors[] = 'cannot be empty';
    } elseif ($this->type == ARRAY_VALIDATOR) {
      if (sizeof($this->what) < $length)
        $this->errors[] = 'need at least one entry';
    }
    
    return $this;
  }
  
  public function htmlify() {
    if ($this->type == STRING_VALIDATOR)
      $this->what = htmlentities($this->what);
    elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $thing) {
        $this->what[$i] = htmlentities($thing);
      }
    }
    
    return $this;
  }
  
  public function spacify() {
    if ($this->type == STRING_VALIDATOR)
      $this->what = preg_replace('/\s{2,}/', ' ', $this->what);
    elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $thing)
        $this->what[$i] = preg_replace('/\s{2,}/', ' ', $thing);
    }
  
    return $this;
  }
  
  public function is_file($prepend, $append) {
    if ($this->type == STRING_VALIDATOR) {
      if (!is_file($prepend.$this->what.$append))
        $this->errors[] = 'not a file: '. $this->what.$append;
    } elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $thing) {
        if (!is_file($prepend.$this->what.$append))
          $this->errors[] = 'not a file: '. $this->what.$append;
      }
    }
    
    return $this;
  }
  
  public function is_dir($prepend='', $append='') {
    if ($this->type == STRING_VALIDATOR) {
      if (!is_dir($prepend.$this->what.$append))
        $this->errors[] = 'not a directory: '. $this->what.$append;
    } elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $thing) {
        if (!is_dir($prepend.$this->what.$append))
          $this->errors[] = 'not a directory: '. $this->what.$append;
      }
    }
  
    return $this;
  }
  
  public function trim_slashes() {
    if ($this->type == STRING_VALIDATOR) {
      $this->what = trim($this->what,'/');
    } elseif ($this->type == ARRAY_VALIDATOR) {
      foreach ($this->what as $i => $thing)
        $this->what[$i] = trim($thing,'/');
    }
    
    return $this;
  }
  
  public function errors() {
    return implode(', ', $this->errors);
  }
  
  public function __toString() {
    if ($this->type == STRING_VALIDATOR)
      return $this->what;
    elseif ($this->type == ARRAY_VALIDATOR)
      return implode(', ', $this->what);
  }
  
  public function shine() {
    if ($this->type != ARRAY_VALIDATOR)
      throw validator_error::array_validator_only();
    
    return $this->what;
  }
}

class validator_error extends error {
  public static function invalid_type() {
    return self::e('invalid type for validator');
  }
  public static function array_validator_only() {
    return self::e('method for array validators only');
  }
}

?>