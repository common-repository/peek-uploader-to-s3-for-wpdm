<?php

// Your models for S3-Uploader plugin

class Option extends S3UKeeper
{
    public function __construct($field)
    {
        parent::__construct($field);
    }

    protected function Fields()
    {
        return array(
            'acceleration' => 'boolean', // fixed field name => type of value
            'cloudfront' => 'boolean',
        );
    }
}

class Acceleration extends S3UKeeper
{
    public function __construct($field)
    {
        parent::__construct($field);
    }

    protected function Fields()
    {
        return array(
            'custom' => array( // custom field names
                'string' => 'boolean', // type of name => type of value
            )
        );
    }
}

class Distribution extends S3UKeeper
{
    public function __construct($field)
    {
        parent::__construct($field);
    }

    protected function Fields()
    {
        return array(
            'custom' => array( // custom field names
                'string' => array( // type of name => value (array type)
                    'id' => 'string',
                    'domain' => 'string',
                    'enabled' => 'boolean',
                    'status' => 'string',
                )
            ),
        );
    }
}

//

class S3UResult
{
    protected $model;
    protected $field;

    protected $valid_result;
    protected $update_result;
    protected $remove_result;

    protected $errors=array();

    public function is_valid()
    {
        return $this->valid_result;
    }
    public function was_updated()
    {
        return $this->update_result;
    }
    public function was_removed()
    {
        return $this->remove_result;
    }

    public function errors()
    {
        return $this->errors;
    }
}

// Validator for S3-Uploader plugin

class S3UValidator extends S3UResult
{
    private $req=array();

    public function __construct()
    {
        $this->check();
    }

    private function check()
    {
        $this->req=$this->model;
        $res=true;
//        $this->errors[]=$this->field['value']==='true';

        if (array_key_exists('custom', $this->model)) {
            foreach ($this->req['custom'] as $c_field => $c_value) {
                if ($this->valid('name', $c_field)) {
                    $this->req[$this->field['name']] = $c_value;
                }
            }
            unset($this->req['custom']);
        }


        if (!array_key_exists($this->field['name'], $this->req)) {
            $res=false;
            $this->errors[]='Error 01! Validation problem: undefined field "'.$this->field['name'].'"';
        } elseif (!$this->valid('value', $this->req[$this->field['name']])) {
            $res=false;
            $this->errors[]='Error 02! Validation problem: wrong type "'.$this->req[$this->field['name']].'" for "'.$this->field['name'].'":"'.$this->field['value'].'"';
        }

        $this->valid_result=$res;
    }

    private function valid($target='value', $type, $target_key=null)
    {
        if (!empty($target_key)) {
            $patient=$this->field[$target][$target_key];
        } else {
            $patient=$this->field[$target];
        }

        if (is_array($type)) {
            foreach (array_keys($type) as $key) {
                if (!array_key_exists($key, $this->field[$target])) {
                    $this->errors[]='Error 03! Validation problem: undefined field "'.$key.'"';
                    return false;
                }
                return $this->valid($target, $type[$key], $key);
            }
        }

        if ($type=='boolean') {
            if ($patient === true || $patient === false) {
                $string_bool = $patient ? 'true' : 'false';
                if (empty($target_key)) {
                    $this->field[$target] = $string_bool;
                } else {
                    $this->field[$target][$target_key] = $string_bool;
                }
            }
            $patient = empty($target_key) ? json_decode($this->field[$target]) : json_decode($this->field[$target][$target_key]);
        }
        return gettype($patient) == $type;
    }
}

// Updating and removing data for S3-Uploader plugin

abstract class S3UKeeper extends S3UValidator
{
    private $option_name;
    private $id;
    public $last_result;

    public function __construct($field)
    {
        $this->model=$this->Fields();

        if (!is_array($field)) {
            $this->field=array('name' => $field, 'value' => null);
        } else {
            $this->field=$field;
        }

        $this->id=strtolower(static::class);

        parent::__construct();

        $this->option_name='s3u-'.$this->id.'-'.$this->field['name'];
    }

    public function update()
    {
        $value=$this->field['value'];

        if ($this->is_valid()) {
            if (update_option($this->option_name, $value)) {
                $this->update_result=true;
            } else {
                $update_check=get_option($this->option_name);
                if ($update_check == false || $update_check != $value) {
                    $this->update_result = false;
                    $this->errors[] = 'Error 11! Update problem: cannot update "' . $this->option_name . '", value: "' . $value . '"';
                } else {
                    $this->update_result=true;
                }
            }
        } else {
            $this->update_result=false;
        }
    }

    public function remove()
    {
        if (!delete_option($this->option_name)) {
            $this->errors[] = 'Error 31! Removing data problem: "'.$this->option_name.'" cannot be deleted';
        }
    }

    public function get()
    {
        $result=get_option($this->option_name);

        if ($result === 'true' || $result === 'false') {
            $result = $result == 'true' ? true : false;
        }

//        if(is_array($result))
//            foreach(array_keys($result) as $field)
//                $result[$field] = $result[$field] == 'true' ? true : false;

        if (!$result) {
            $this->errors[] = 'Error 21! Getting data problem: cannot get value of "'.$this->field['name'].'" from "'.$this->option_name.'"';
        }

        $this->last_result=$result;
        return $result;
    }

    abstract protected function Fields();
}
