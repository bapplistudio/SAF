<?php
namespace SAF\Framework;

/**
 * List settings : all that can be customized into a list view
 */
class List_Settings
{
	use Custom_Settings { current as private pCurrent; load as private pLoad; }

	//---------------------------------------------------------------------------------------- $title
	/**
	 * The title that will be displayed on the top of the list
	 *
	 * @var string
	 */
	public $title;

	//------------------------------------------------------------------------------ $properties_path
	/**
	 * Properties path
	 *
	 * @var string[] key is the column number (0..n)
	 */
	public $properties_path;

	//----------------------------------------------------------------------------- $properties_title
	/**
	 * Properties title
	 *
	 * @var string[] key is the property path
	 */
	public $properties_title = array();

	//--------------------------------------------------------------------------------------- $search
	/**
	 * Search criterion
	 *
	 * @var string[] key is the property path, value is the value or search expression
	 */
	public $search = array();

	//----------------------------------------------------------------------------------------- $sort
	/**
	 * Sort option (sort properties and reverse)
	 *
	 * @var Dao_Sort_Option
	 */
	public $sort;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class_name string
	 * @param $setting    Setting
	 */
	public function __construct($class_name = null, Setting $setting = null)
	{
		if (isset($class_name)) {
			$this->class_name = $class_name;
		}
		if (isset($setting)) {
			$this->setting = $setting;
		}
		if (!isset($this->sort)) {
			$this->sort = new Dao_Sort_Option($class_name);
		}
		if (!isset($this->properties_path) && isset($this->class_name)) {
			$this->properties_path = Reflection_Class::getInstanceOf($this->class_name)
				->getListAnnotation("representative")->values();
		}
	}

	//----------------------------------------------------------------------------------- addProperty
	/**
	 * @param $property_path       string
	 * @param $where               string "after", "before" or null
	 * @param $where_property_path string reference property path for $where
	 */
	public function addProperty($property_path, $where = "after", $where_property_path = null)
	{
		$properties_path = array();
		$count = 0;
		if (($where == "after") && empty($where_property_path)) {
			$properties_path[$count++] = $property_path;
		}
		foreach ($this->properties_path as $key) {
			if (($where == "before") && ($key == $where_property_path)) {
				$properties_path[$count++] = $property_path;
			}
			if ($key !== $property_path) {
				$properties_path[$count++] = $key;
			}
			if (($where == "after") && ($key == $where_property_path)) {
				$properties_path[$count++] = $property_path;
			}
		}
		if (($where == "before") && empty($where_property_path)) {
			$properties_path[$count] = $property_path;
		}
		$this->properties_path = $properties_path;
	}

	//--------------------------------------------------------------------------------------- current
	/**
	 * Get current session / user custom settings object
	 *
	 * @param $class_name string
	 * @return List_Settings
	 */
	public static function current($class_name)
	{
		return self::pCurrent($class_name);
	}

	//------------------------------------------------------------------------------- getDefaultTitle
	/**
	 * @return string
	 */
	private function getDefaultTitle()
	{
		return ucfirst(Names::classToDisplay(
			Reflection_Class::getInstanceOf($this->class_name)->getAnnotation("set")
		));
	}

	//------------------------------------------------------------------------------------------ load
	/**
	 * Loads a List_Settings from the Settings set
	 *
	 * If no List_Settings named $name is stored, a new one will be returned
	 *
	 * @param $class_name string
	 * @param $name       string
	 * @return List_Settings
	 */
	public static function load($class_name, $name)
	{
		return self::pLoad($class_name, $name);
	}

	//-------------------------------------------------------------------------------- removeProperty
	/**
	 * @param $property_path string
	 */
	public function removeProperty($property_path)
	{
		if (($key = array_search($property_path, $this->properties_path, true)) !== false) {
			unset($this->properties_path[$key]);
			$this->properties_path = array_values($this->properties_path);
		}
	}

	//--------------------------------------------------------------------------------------- reverse
	/**
	 * @param $property_path string
	 */
	public function reverse($property_path)
	{
		if (in_array($property_path, $this->sort->reverse)) {
			unset($this->sort->reverse[array_search($property_path, $this->sort->reverse)]);
		}
		else {
			array_push($this->sort->reverse, $property_path);
		}
	}

	//------------------------------------------------------------------------------------------ sort
	/**
	 * @param $property_path string
	 */
	public function sort($property_path)
	{
		$this->sort->addSortColumn($property_path);
	}

	//--------------------------------------------------------------------------------- propertyTitle
	/**
	 * Sets the title of the property
	 *
	 * @param $property_path string
	 * @param $title         string if empty or null, the title is removed to get back to default
	 */
	public function propertyTitle($property_path, $title = null)
	{
		if (empty($title)) {
			if (isset($this->properties_title[$property_path])) {
				unset($this->properties_title[$property_path]);
			}
		}
		else {
			$this->properties_title[$property_path] = $title;
		}
	}

	//---------------------------------------------------------------------------------------- search
	/**
	 * Adds search values
	 *
	 * If a search value is empty, the search value is removed
	 * Already existing search values for other properties path stay unchanged
	 *
	 * @param $search array key is the property path
	 */
	public function search($search)
	{
		foreach ($search as $property_path => $value) {
			if (empty($value)) {
				if (isset($this->search[$property_path])) {
					unset($this->search[$property_path]);
				}
			}
			else {
				$this->search[$property_path] = $value;
			}
		}
	}

	//----------------------------------------------------------------------------------------- title
	/**
	 * @param $title string
	 * @return string
	 */
	public function title($title = null)
	{
		if (isset($title)) {
			$this->title = $title;
		}
		return (empty($this->title))
			? $this->getDefaultTitle()
			: $this->title;
	}

}
