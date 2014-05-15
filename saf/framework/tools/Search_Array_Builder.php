<?php
namespace SAF\Framework\Tools;

use SAF\Framework\Dao\Func;
use SAF\Framework\Dao\Func\Logical;
use SAF\Framework\Reflection\Annotation\Template\List_Annotation;
use SAF\Framework\Reflection\Reflection_Class;

/**
 * The search array builder builds search arrays from properties paths and search phrases
 */
class Search_Array_Builder
{

	//------------------------------------------------------------------------------------------ $and
	public $and = SP;

	//------------------------------------------------------------------------------------------- $or
	public $or = ',';

	//----------------------------------------------------------------------------------------- build
	/**
	 * @param $property_name string
	 * @param $search_phrase string
	 * @param $prepend string
	 * @param $append string
	 * @return array
	 */
	public function build($property_name, $search_phrase, $prepend = '', $append = '')
	{
		$search_phrase = trim($search_phrase);
		// search phrase contains OR
		if (strpos($search_phrase, $this->or) !== false) {
			$result = [];
			foreach (explode($this->or, $search_phrase) as $search) {
				$sub_result = $this->build('', $search, $prepend, $append);
				if ((!is_array($sub_result)) || (count($sub_result) > 1)) {
					$result[$property_name][] = $sub_result;
				}
				elseif (isset($result[$property_name])) {
					$result[$property_name] = array_merge($result[$property_name], $sub_result);
				}
				else {
					$result[$property_name] = $sub_result;
				}
			}
			return $property_name ? $result : reset($result);
		}
		// search phrase contains AND
		elseif (strpos($search_phrase, $this->and) !== false) {
			$and = [];
			foreach (explode($this->and, $search_phrase) as $search) {
				$and[] = $this->build('', $search, $prepend, $append);
				$prepend = '%';
			}
			$result[$property_name]= Func::andOp($and);
			return $property_name ? $result : reset($result);
		}
		// simple search phrase
		else {
			return $property_name
				? [$property_name => $prepend . $search_phrase . $append]
				: ($prepend . $search_phrase . $append);
		}
	}

	//--------------------------------------------------------------------------------- buildMultiple
	/**
	 * @param $property_names_or_class string[]|Reflection_Class
	 * @param $search_phrase string
	 * @param $prepend string
	 * @param $append string
	 * @return Logical|array
	 */
	public function buildMultiple(
		$property_names_or_class, $search_phrase, $prepend = '', $append = ''
	) {
		$property_names = ($property_names_or_class instanceof Reflection_Class)
			? $this->classRepresentativeProperties($property_names_or_class)
			: $property_names_or_class;
		// search phrase contains OR
		if (strpos($search_phrase, $this->or) !== false) {
			$or = [];
			foreach ($property_names as $property_name) {
				$or[$property_name] = $this->build('', $search_phrase, $prepend, $append);
			}
			$result = Func::orOp($or);
		}
		// search phrase contains AND
		elseif (strpos($search_phrase, $this->and) !== false) {
			$and = [];
			foreach (explode($this->and, $search_phrase) as $search) {
				$and[] = $this->buildMultiple($property_names, $search, $prepend, $append);
				$prepend = '%';
			}
			$result = Func::andOp($and);
		}
		// simple search phrase
		else {
			$or = [];
			foreach ($property_names as $property_name) {
				$or[$property_name] = $prepend . $search_phrase . $append;
			}
			$result = (count($or) > 1) ? Func::orOp($or) : $or;
		}
		return $result;
	}

	//----------------------------------------------------------------- classRepresentativeProperties
	/**
	 * @param $class   Reflection_Class
	 * @param $already string[] For recursion limits : already got classes
	 * @return string[]
	 */
	private function classRepresentativeProperties($class, $already = [])
	{
		/** @var $property_names List_Annotation */
		$property_names = $class->getListAnnotation('representative')->values();
		foreach ($property_names as $key => $property_name) {
			$property_class = $class;
			$i = strpos($property_name, DOT);
			while ($i !== false) {
				$property = $property_class->getProperty(substr($property_name, 0, $i));
				$property_class = new Reflection_Class($property->getType()->asString());
				$property_name = substr($property_name, $i + 1);
				$i = strpos($property_name, DOT);
			}
			$property = $property_class->getProperty($property_name);
			$type = $property->getType();
			$type_string = $type->asString();
			if (!$type->isBasic()) {
				unset($property_names[$key]);
				if (!isset($already[$type_string])) {
					$sub_class = new Reflection_Class($type_string);
					$sub_already = $already;
					$sub_already[$type_string] = $type_string;
					foreach (
						$this->classRepresentativeProperties($sub_class, $sub_already) as $sub_property_name
					) {
						$property_names[] = $property_name . DOT . $sub_property_name;
					}
				}
			}
		}
		return $property_names;
	}

}
