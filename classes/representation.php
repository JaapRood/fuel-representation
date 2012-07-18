<?php
/**
 * Representation
 *
 * Managing your resource representations in FuelPHP
 *
 * @package		Representation
 * @version		0.1
 * @author		Jaap Rood
 * @license		MIT License
 * @link		http://github.com/JaapRood/fuel-representation
 */


namespace Representation;

class Representation {
    
    /**
     * @var mixed   data to be used in the representation
     */
    protected $data = array();
	
	/**
	 * @var  string  The view's filename
	 */
	protected $file_name;
	
	/**
	 * @var  string  The representation file extension
	 */
	protected $extension = 'php';
	
    
    /**
     * Returns an instance of the Representation object
     * Prefered over constructing a 
     *
     * @param	string	$file	representation filename
     * @param	mixed	$data	data to be available in the representation
     */
    public static function forge($file, $data = null) {
        return new static($file, $data);
    }
    
    /**
     * constructor
     *
     * call the static forge method to create new objects
     *
     * @param	string	$file	representation filename
     * @param	mixed	$data	data to be structurized
     */
    public function __construct($file, $data = null) {
		\Config::load('representation', true); // load this so we're sure we have it available
		
		if ($data && !is_object($data) && !is_array($data)) {
			throw new \InvalidArgumentException('The data parameter only accepts objects and arrays.');
		}
		
		if ($data !== null) {
			$this->data = $data;
		}
		
		$this->set_filename($file);
        
		return $this;
    }
    
	/**
	 * Sets the representation filename.
	 *
	 * @param   string  representation filename
	 * @return  Representation
	 * @throws  FuelException
	 */
	public function set_filename($file) {

		$folder = \Config::get('representation.representations_folder');
		
		// locate the representation file
		
		if (($path = \Finder::search($folder, $file, '.'.$this->extension, false, false)) === false) {
			throw new \FuelException('The requested representation could not be found: '.\Fuel::clean_path($file));
		}

		// Store the file path locally
		$this->file_name = $path;

		return $this;
	}
	
	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $representation->foo;
	 *
	 * @param   string  variable name
	 * @return  mixed
	 * @throws  OutOfBoundsException
	 */
	public function & __get($key) {
		return $this->get($key);
	}

	/**
	 * Magic method, calls [static::set] with the same parameters.
	 *
	 *     $representation->foo = 'something';
	 *
	 * @param   string  variable name
	 * @param   mixed   value
	 * @return  void
	 */
	public function __set($key, $value) {
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($representation->foo);
	 *
	 * [!!] `null` variables are not considered to be set by [isset](http://php.net/isset).
	 *
	 * @param   string  variable name
	 * @return  boolean
	 */
	public function __isset($key) {
		return (isset($this->data[$key]) or isset(static::$global_data[$key]));
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($representation->foo);
	 *
	 * @param   string  variable name
	 * @return  void
	 */
	public function __unset($key) {
		unset($this->data[$key], static::$global_data[$key]);
	}
	
	/**
	 * Searches for the given variable and returns its value.
	 *
	 *     $value = $representation->get('foo', 'bar');
	 *
	 * If a default parameter is not given and the variable does not
	 * exist, it will throw an OutOfBoundsException.
	 *
	 * @param   string  The variable name
	 * @param   mixed   The default value to return
	 * @return  mixed
	 * @throws  OutOfBoundsException
	 */
	public function &get($key, $default = null) {
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}

		if (is_null($default) and func_num_args() === 1) {
			throw new \OutOfBoundsException('Representation variable is not set: '.$key);
		} else {
			return \Fuel::value($default);
		}
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the view file:
	 *
	 *     // This value can be accessed as $foo within the representation
	 *     $representation->set('foo', 'my value');
	 *
	 * You can also use an array to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the representation
	 *     $rep->set(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @return  $this
	 */
	public function set($key, $value = null) {
		if (is_array($key)) {
			foreach ($key as $name => $value) {
				$this->data[$name] = $value;
			}
			
		} else {
			$this->data[$key] = $value;
		}

		return $this;
	}
	
	/**
	 * Capture the formatted representation when the representation file is included. The data will be
	 * extracted into the local scope of a closure, to prevent scope resolution.
	 *
	 * @return 	array	the representation of the resource
	 */
	protected function process_file() {
		$clean_room = function($__file_name, array $__data) {
			extract($__data, EXTR_REFS);

			try {
				// Load the view within the current scope
				return include $__file_name;
			}
			catch (\Exception $e)
			{
				throw new \FuelException($e->getMessage());

			}
		};
		
		return $clean_room($this->file_name, $this->data);
	}
	
	/**
	 * Formats the representation and returns it
	 *
	 * @return	mixed	the representation as defined in the respresentation file
	 */
	public function output() {
		if (empty($this->file_name)) {
			throw new \FuelException('You must set the file to use within your representation before outputting');
		}
		
		return $this->process_file();
	}
	
	/**
	 * convert ORM models to arrays and unindex their arrays in order to create well formed
	 * native JSON
	 *
	 * @param	mixed	$data	data that needs Orm Models converted to arrays
	 */
	public static function models_to_array($data) {
		if (is_array($data)) { // if it's an array
			if (array_shift(array_values($data)) instanceof \Orm\Model) { // if the first element is a Orm Model
				$new_data = array();
				
				foreach ($data as $model) {
					
					$converted_properties = array();
					foreach($model as $key => $property) { // for each property
						$converted_properties[$key] = static::models_to_array($property); // could be an array of models
					}
					
					$converted_model = $model->to_array();
					
					foreach ($converted_properties as $key => $property) {
						$converted_model[$key] = $property;
					}
					
					$new_data[] = $converted_model;
				}
				
				$data = $new_data;
			}
		} elseif ($data instanceof \Orm\Model) {
			$converted_properties = array();
			foreach($data as $key => $property) { // for each property
				$converted_properties[$key] = static::models_to_array($property); // could be an array of models
			}
			
			$converted_model = $data->to_array();
			
			foreach ($converted_properties as $key => $property) {
				$converted_model[$key] = $property;
			}
			
			$data = $converted_model;
		}
		
		return $data;
	}
}