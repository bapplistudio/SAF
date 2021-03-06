<?php
namespace SAF\Framework\Widget\Data_List;

use Exception;
use SAF\Framework\Builder;
use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameter;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Controller\Target;
use SAF\Framework\Dao\Func;
use SAF\Framework\Dao\Func\Group_Concat;
use SAF\Framework\Dao\Option;
use SAF\Framework\Dao\Option\Count;
use SAF\Framework\Dao\Option\Group_By;
use SAF\Framework\Dao\Option\Limit;
use SAF\Framework\Dao;
use SAF\Framework\Dao\Option\Reverse;
use SAF\Framework\Error_Handler\Handled_Error;
use SAF\Framework\Error_Handler\Report_Call_Stack_Error_Handler;
use SAF\Framework\History;
use SAF\Framework\Locale;
use SAF\Framework\Locale\Loc;
use SAF\Framework\Printer\Model;
use SAF\Framework\Reflection\Annotation\Property\Link_Annotation;
use SAF\Framework\Reflection\Annotation\Property\Store_Annotation;
use SAF\Framework\Reflection\Annotation\Property\User_Annotation;
use SAF\Framework\Reflection\Annotation\Property\Var_Annotation;
use SAF\Framework\Reflection\Annotation\Template\Method_Annotation;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Reflection\Reflection_Property_Value;
use SAF\Framework\Reflection\Type;
use SAF\Framework\Setting\Buttons;
use SAF\Framework\Setting\Custom_Settings;
use SAF\Framework\Setting\Custom_Settings_Controller;
use SAF\Framework\Tools\Color;
use SAF\Framework\Tools\Default_List_Data;
use SAF\Framework\Tools\List_Data;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Namespaces;
use SAF\Framework\View;
use SAF\Framework\Widget\Button;
use SAF\Framework\Widget\Button\Has_Selection_Buttons;
use SAF\Framework\Widget\Data_List_Setting;
use SAF\Framework\Widget\Data_List_Setting\Data_List_Settings;
use SAF\Framework\Widget\Output\Output_Controller;

/**
 * The default list controller is called if no list controller has been defined for a business
 * object class
 */
class Data_List_Controller extends Output_Controller implements Has_Selection_Buttons
{

	//---------------------------------------------------------------------------------- $class_names
	/**
	 * @var string The set class name (can be virtual if only the element class name exists)
	 */
	private $class_names;

	//--------------------------------------------------------------------------------------- $errors
	/**
	 * List of errors on fields' search expression
	 *
	 * @var array of Exception
	 */
	private $errors = [];

	//----------------------------------------------------------------- applyParametersToListSettings
	/**
	 * Apply parameters to list settings
	 *
	 * @param $list_settings Data_List_Settings
	 * @param $parameters    array
	 * @param $form          array
	 * @return Data_List_Settings set if parameters did change
	 */
	public function applyParametersToListSettings(
		Data_List_Settings &$list_settings, $parameters, $form = null
	) {
		if (isset($form)) {
			$parameters = array_merge($parameters, $form);
		}
		$did_change = true;
		if (isset($parameters['add_property'])) {
			$list_settings->addProperty(
				$parameters['add_property'],
				isset($parameters['before']) ? 'before' : 'after',
				isset($parameters['before'])
					? $parameters['before']
					: (isset($parameters['after']) ? $parameters['after'] : '')
			);
		}
		elseif (isset($parameters['less'])) {
			if ($parameters['less'] == 20) {
				$list_settings->maximum_displayed_lines_count = 20;
			}
			else {
				$list_settings->maximum_displayed_lines_count = max(
					20, $list_settings->maximum_displayed_lines_count - $parameters['less']
				);
			}
		}
		elseif (isset($parameters['more'])) {
			$list_settings->maximum_displayed_lines_count = round(min(
				1000, $list_settings->maximum_displayed_lines_count + $parameters['more']
			) / 100) * 100;
		}
		elseif (isset($parameters['move'])) {
			if ($parameters['move'] == 'down') {
				$list_settings->start_display_line_number += $list_settings->maximum_displayed_lines_count;
			}
			elseif ($parameters['move'] == 'up') {
				$list_settings->start_display_line_number -= $list_settings->maximum_displayed_lines_count;
			}
			elseif (is_numeric($parameters['move'])) {
				$list_settings->start_display_line_number = $parameters['move'];
			}
		}
		elseif (isset($parameters['remove_property'])) {
			$list_settings->removeProperty($parameters['remove_property']);
		}
		elseif (isset($parameters['property_path'])) {
			if (isset($parameters['property_group_by'])) {
				$list_settings->propertyGroupBy(
					$parameters['property_path'], $parameters['property_group_by']
				);
			}
			if (isset($parameters['property_title'])) {
				$list_settings->propertyTitle($parameters['property_path'], $parameters['property_title']);
			}
		}
		elseif (isset($parameters['reverse'])) {
			$list_settings->reverse($parameters['reverse']);
		}
		elseif (isset($parameters['search'])) {
			$list_settings->search(self::descapeForm($parameters['search']));
		}
		elseif (isset($parameters['sort'])) {
			$list_settings->sort($parameters['sort']);
		}
		elseif (isset($parameters['title'])) {
			$list_settings->title = $parameters['title'];
		}
		else {
			$did_change = false;
		}
		if ($list_settings->start_display_line_number < 1) {
			$list_settings->start_display_line_number = 1;
			$did_change = true;
		}
		if (Custom_Settings_Controller::applyParametersToCustomSettings($list_settings, $parameters)) {
			$did_change = true;
		}
		if (!$list_settings->name) {
			$list_settings->name = $list_settings->title;
		}
		if (!$list_settings->title) {
			$list_settings->title = $list_settings->name;
		}
		// SM : I put the save outside this method because we should save only if search
		// expressions are all valid.
		// TODO Move back save() here once we have a generic validator (parser) not depending of SQL that we could fire here before save !
		//if ($did_change) {
		//	$list_settings->save();
		//}
		return $did_change ? $list_settings : null;
	}

	//------------------------------------------------------------------------- applySearchParameters
	/**
	 * @param $list_settings Data_List_Settings
	 * @return array search-compatible search array
	 */
	public function applySearchParameters(Data_List_Settings $list_settings)
	{
		$class = $list_settings->getClass();
		/** @var $search_parameters_parser Search_Parameters_Parser */
		$search_parameters_parser = Builder::create(
			Search_Parameters_Parser::class, [$class->name, $list_settings->search]
		);
		$search = $search_parameters_parser->parse();
		// check if we have errors in search expressions
		$this->errors = [];
		foreach ($search as $property_path => &$search_value) {
			if ($search_value instanceof Exception) {
				$this->errors[$property_path] = $search_value;
				// reset result value to a valid empty expression that can be given to readData() to work
				// properly
				$search_value = '';
				// reset settings value to a valid empty expression that can be saved
				$list_settings->search[$property_path] = '';
			}
		}
		return $search;
	}

	//----------------------------------------------------------------------------------- descapeForm
	/**
	 * @param $form string[]
	 * @return string[]
	 */
	protected function descapeForm($form)
	{
		$result = [];
		foreach ($form as $property_name => $value) {
			$property_name = self::descapePropertyName($property_name);
			$result[$property_name] = $value;
		}
		return $result;
	}

	//--------------------------------------------------------------------------- descapePropertyName
	/**
	 * @param $property_name string
	 * @return string
	 */
	protected function descapePropertyName($property_name)
	{
		$property_name = str_replace(['.id_', '>id_', '>'], DOT, $property_name);
		if (substr($property_name, 0, 3) == 'id_') {
			$property_name = substr($property_name, 3);
		}
		return $property_name;
	}

	//--------------------------------------------------------------------------------- getClassNames
	/**
	 * Returns the class names
	 *
	 * @return string
	 */
	public function getClassNames()
	{
		return $this->class_names;
	}

	//------------------------------------------------------------------------------ getErrorsSummary
	/**
	 * @return string
	 */
	public function getErrorsSummary()
	{
		$summary = '';
		if (isset($this->errors) && is_array($this->errors)) {
			$first = true;
			foreach ($this->errors as $property_path => $error) {
				if ($first) $first = false; else $summary .= ',';
				// TODO I should not see any HTML code inside the PHP code
				$summary .= SP . ' <span class="error">' . $error->getMessage();
				if ($error instanceof Data_List_Exception) {
					$summary .= ' (' . $error->getExpression() . ')';
				}
				$summary .= '</span>';
			}
		}
		return $summary;
	}

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $class_name string The context object or class name
	 * @param $parameters array Parameters prepared to the view. 'selection_buttons' to be added
	 * @param $settings   Custom_Settings|Data_List_Settings
	 * @return Button[]
	 */
	public function getGeneralButtons($class_name, $parameters, Custom_Settings $settings = null)
	{
		return [
			Feature::F_ADD => new Button(
				'Add',
				View::link($class_name, Feature::F_ADD),
				Feature::F_ADD,
				[Target::MAIN, new Color(Color::GREEN)]
			),
			Feature::F_IMPORT => new Button(
				'Import',
				View::link($class_name, Feature::F_IMPORT),
				Feature::F_IMPORT,
				[Target::MAIN, new Color(Color::GREEN)]
			)
		];
	}

	//--------------------------------------------------------------------------------- getProperties
	/**
	 * @param $list_settings Data_List_Settings
	 * @return Property[]
	 */
	protected function getProperties(Data_List_Settings $list_settings)
	{
		$class_name = $list_settings->getClassName();
		/** @var $properties Property[] */
		$properties = [];
		// properties / search
		foreach ($list_settings->properties as $property) {
			/** @var $property Property */
			$property = Builder::createClone($property, Property::class);
			$property->search = new Reflection_Property($class_name, $property->path);
			if (!$property->search->getType()->isString()) {
				$property->search->setAnnotationLocal(Var_Annotation::ANNOTATION)->value  = Type::STRING;
				$property->search->setAnnotationLocal(Link_Annotation::ANNOTATION)->value = null;
			}
			$properties[$property->path] = $property;
		}
		foreach ($list_settings->search as $property_path => $search_value) {
			if (isset($properties[$property_path])) {
				$properties[$property_path]->search = $this->searchProperty(
					$properties[$property_path]->search, $search_value
				);
			}
		}
		// sort / reverse
		$sort_position = 0;
		foreach ($list_settings->sort->columns as $column) {
			$property_path = ($column instanceof Reverse) ? $column->column : $column;
			if (isset($properties[$property_path])) {
				$properties[$property_path]->sort = ++$sort_position;
				if ($list_settings->sort->isReverse($property_path)) {
					$properties[$property_path]->reverse = true;
				}
			}
		}
		return $properties;
	}

	//------------------------------------------------------------------------------ getSearchSummary
	/**
	 * @param $class_name    string class for the read object
	 * @param $list_settings Data_List_Settings
	 * @param $search        array search-compatible search array
	 * @return string
	 */
	public function getSearchSummary($class_name, Data_List_Settings $list_settings, $search)
	{
		if (empty($search)) {
			return '';
		}
		if ($list_settings->search) {
			if (Locale::current()) {
				$t = '|';
				$i = '¦';
			}
			else {
				$t = $i = '';
			}
			$class_display = Names::classToDisplay(
				$list_settings->getClass()->getAnnotation('set')->value
			);
			$summary = $t . $i. ucfirst($class_display) . $i . ' filtered by' . $t;
			$summary_builder = new Summary_Builder($class_name, $search);
			$summary .= SP . (string)$summary_builder;
			return $summary;
		}
		return null;
	}

	//--------------------------------------------------------------------------- getSelectionButtons
	/**
	 * @param $class_name    string class name
	 * @param $parameters    string[] parameters
	 * @param $list_settings Custom_Settings|Data_List_Settings
	 * @return Button[]
	 */
	public function getSelectionButtons(
		$class_name, $parameters, Custom_Settings $list_settings = null
	) {
		return [
			Feature::F_EXPORT => new Button(
				'Export',
				View::link(
					Names::classToSet($class_name), Feature::F_EXPORT, null, [Parameter::AS_WIDGET => true]
				),
				Feature::F_EXPORT,
				[View::TARGET => Target::TOP]
			),
			Feature::F_PRINT => new Button(
				'Print',
				View::link($class_name, Feature::F_PRINT),
				Feature::F_PRINT, [
					Button::SUB_BUTTONS => [
						new Button(
							'Models',
							View::link(
								Names::classToSet(Model::class),
								Feature::F_LIST,
								Namespaces::shortClassName($class_name)
							),
							Feature::F_LIST,
							Target::MAIN
						)
					]
				]
			)
		];
	}

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(Parameters $parameters, $form, $class_name)
	{
		$parameters = $parameters->getObjects();
		$list_settings = Data_List_Settings::current($class_name);
		$list_settings->cleanup();
		$did_change = $this->applyParametersToListSettings($list_settings, $parameters, $form);
		$customized_list_settings = $list_settings->getCustomSettings();
		$count = new Count();
		// before to fire readData (that may change $list_settings if error found)
		// we need to get a copy in order to display summary with original given parameters
		$list_settings_before_read = clone $list_settings;
		// SM : Moved from readData()
		$search = $this->applySearchParameters($list_settings);
		try {
			$data = $this->readData($class_name, $list_settings, $search, $count);
			// SM : Moved from applyParametersToListSettings()
			// TODO Move back once we have a generic validator (parser) not depending of SQL that we could fire before save
			if (!is_null($did_change) && !(isset($this->errors) && count($this->errors))) {
				$list_settings->save();
			}
		}
		catch (Exception $exception) {
			//set empty list result
			$data  = new Default_List_Data($class_name, []);
			//set an error to display
			$error = new Exception(Report_Call_Stack_Error_Handler::getUserInformationMessage());
			$this->errors[] = $error;
			// log the error in order software maintainer to be informed
			$handled = new Handled_Error(
				$exception->getCode(),
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine(),
				null
			);
			$handler = new Report_Call_Stack_Error_Handler();
			$handler->logError($handled);
		}
		$displayed_lines_count = min($data->length(), $list_settings->maximum_displayed_lines_count);
		$less_twenty = $displayed_lines_count > 20;
		$more_hundred = ($displayed_lines_count < 1000) && ($displayed_lines_count < $count->count);
		$more_thousand = ($displayed_lines_count < 1000) && ($displayed_lines_count < $count->count);
		$parameters = array_merge(
			[$class_name => $data],
			$parameters,
			[
				'customized_lists'      => $customized_list_settings,
				'default_title'         => ucfirst(Names::classToDisplay($this->class_names)),
				'display_start'         => $list_settings->start_display_line_number,
				'displayed_lines_count' => $displayed_lines_count,
				'errors_summary'        => $this->getErrorsSummary(),
				'less_twenty'           => $less_twenty,
				'more_hundred'          => $more_hundred,
				'more_thousand'         => $more_thousand,
				'properties'            => $this->getProperties($list_settings_before_read),
				'rows_count'            => (int)$count->count,
				'search_summary'        => $this->getSearchSummary(
					$class_name, $list_settings_before_read, $search
				),
				'settings'              => $list_settings,
				'title'                 => $list_settings->title()
			]
		);
		// buttons
		/** @var $buttons Buttons */
		$buttons = Builder::create(Buttons::class);
		$parameters['custom_buttons'] = $buttons->getButtons(
			'custom list', Names::classToSet($class_name)
		);
		// if an error occurred, we do not display custom save button
		if (count($this->errors)) {
			if (isset($parameters['custom_buttons'][Feature::F_WRITE])) {
				unset($parameters['custom_buttons'][Feature::F_WRITE]);
			}
		}
		$parameters[self::GENERAL_BUTTONS] = $this->getGeneralButtons(
			$class_name, $parameters, $list_settings
		);
		$parameters[self::SELECTION_BUTTONS] = $this->getSelectionButtons(
			$class_name, $parameters, $list_settings
		);
		if (!isset($customized_list_settings[$list_settings->name])) {
			unset($parameters[self::GENERAL_BUTTONS][Feature::F_DELETE]);
		}
		return $parameters;
	}

	//--------------------------------------------------------------------------------------- groupBy
	/**
	 * @param $properties Data_List_Setting\Property[]
	 * @return Group_By|null
	 */
	private function groupBy($properties)
	{
		$group_by = null;
		foreach ($properties as $property) {
			if ($property->group_by) {
				if (!isset($group_by)) {
					$group_by = new Group_By();
				}
				$group_by->properties[] = $property->path;
			}
		}
		return $group_by;
	}

	//----------------------------------------------------------------------------------- groupConcat
	/**
	 * @param $properties_path string[]
	 * @param Group_By         $group_by
	 */
	private function groupConcat(&$properties_path, Group_By $group_by)
	{
		foreach ($properties_path as $key => $property_path) {
			if (!in_array($property_path, $group_by->properties)) {
				$group_concat = new Group_Concat();
				$group_concat->separator = ', ';
				$properties_path[$key] = $group_concat;
			}
		}
	}

	//------------------------------------------------------------------------------- objectsToString
	/**
	 * In Dao::select() result : replace objects with their matching __toString() result value
	 *
	 * @param $data List_Data
	 */
	private function objectsToString(List_Data $data)
	{
		$class_properties = [];
		$class_name = $data->getClass()->getName();
		foreach ($data->getProperties() as $property_name) {
			$property = new Reflection_Property($class_name, $property_name);
			if ($property->getType()->isClass()) {
				$class_properties[$property_name] = $property_name;
			}
		}
		if ($class_properties) {
			foreach ($data->getRows() as $row) {
				foreach ($class_properties as $property_name) {
					$row->setValue($property_name, strval($row->getValue($property_name)));
				}
			}
		}
	}

	//-------------------------------------------------------------------------------------- readData
	/**
	 * @param $class_name    string
	 * @param $list_settings Data_List_Settings
	 * @param $search        array search-compatible search array
	 * @param $count         Count
	 * @return List_Data
	 */
	public function readData(
		$class_name, Data_List_Settings $list_settings, $search, Count $count = null
	) {
		// SM : Moved outside the method in order result to be used for search summary
		//$search = $this->applySearchParameters($list_settings);

		$class = $list_settings->getClass();
		foreach ($class->getAnnotations('on_data_list') as $execute) {
			/** @var $execute Method_Annotation */
			if ($execute->call($class->name, [&$search]) === false) {
				break;
			}
		}

		$options = [$list_settings->sort, Dao::doublePass()];
		if ($count) {
			$options[] = $count;
		}
		if ($list_settings->maximum_displayed_lines_count) {
			$limit = new Limit(
				$list_settings->start_display_line_number,
				$list_settings->maximum_displayed_lines_count
			);
			$options[] = $limit;
		}
		$properties = array_keys($list_settings->properties);
		list($properties_path, $search) = $this->removeInvisibleProperties(
			$class_name, $properties, $search
		);
		// TODO : an automation to make the group by only when it is useful
		if ($group_by = $this->groupBy($list_settings->properties)) {
			$options[] = $group_by;
			$this->groupConcat($properties_path, $group_by);
		}
		$data = $this->readDataSelect($class_name, $properties_path, $search, $options);
		if (isset($limit) && isset($count)) {
			if (($data->length() < $limit->count) && ($limit->from > 1)) {
				$limit->from = max(1, $count->count - $limit->count + 1);
				$list_settings->start_display_line_number = $limit->from;
				$list_settings->save();
				$data = $this->readDataSelect($class_name, $properties_path, $search, $options);
			}
		}
		$this->objectsToString($data);
		// TODO LOW the following patch lines are to avoid others calculation to use invisible props
		foreach ($list_settings->properties as $property_path => $property) {
			if (!isset($properties_path[$property_path])) {
				unset($list_settings->properties[$property_path]);
			}
		}

		foreach ($data->getRows() as $row) {
			foreach ($row->getValues() as $property_name => $value) {
				$row->setValue($property_name, htmlspecialchars($value));
			}
		}

		return $data;
	}

	//-------------------------------------------------------------------------------- readDataSelect
	/**
	 * @param $class_name      string Class name for the read object
	 * @param $properties_path string[] the list of the columns names : only those properties
	 *                         will be read. There are 'column.sub_column' to get values from linked
	 *                         objects from the same data source
	 * @param $search          array Search array for filter, associating properties names to
	 *                         matching search value too.
	 * @param $options         Option[] some options for advanced search
	 * @return List_Data A list of read records. Each record values (may be objects) are
	 *         stored in the same order than columns.
	 */
	protected function readDataSelect(
		$class_name, array $properties_path, array $search, array $options
	) {
		return Dao::select($class_name, $properties_path, $search, $options);
	}

	//--------------------------------------------------------------------- removeInvisibleProperties
	/**
	 * @param $class_name      string
	 * @param $properties_path string[] properties path that can include invisible properties
	 * @param $search          array search where to add Has_History criteria
	 * @return string[] properties path without the invisible properties
	 */
	public function removeInvisibleProperties($class_name, $properties_path, $search)
	{
		// remove properties directly used as columns
		foreach ($properties_path as $key => $property_path) {
			$property = new Reflection_Property($class_name, $property_path);
			$annotation = $property->getListAnnotation(User_Annotation::ANNOTATION);
			if ($annotation->has(User_Annotation::INVISIBLE)) {
				unset($properties_path[$key]);
			}
			$history_class_name = $property->getFinalClassName();
			if (isA($history_class_name, History::class)) {
				$ignore_invisible_properties[$history_class_name] = lLastParse($property_path, DOT);
			}
		}
		// remove properties read from an History table
		// TODO this should be a specific when we use history. If the app does not use history, this
		// should not execute. Create an AOP advice.
		if (isset($ignore_invisible_properties)) {
			foreach ($ignore_invisible_properties as $history_class_name => $history_path) {
				$property_names = Dao::select(
					$history_class_name, 'property_name', null, Dao::groupBy('property_name')
				)->getRows();
				foreach ($property_names as $property_name) {
					$property_name = $property_name->getValue('property_name');
					$property      = new Reflection_Property($class_name, $property_name);
					$annotation    = $property->getListAnnotation(User_Annotation::ANNOTATION);
					if ($annotation->has(User_Annotation::INVISIBLE)) {
						$all_but[] = $property_name;
					}
				}
				if (isset($all_but)) {
					$history_search[$history_path . DOT . 'property_name'] = Func::notIn($all_but);
					unset($all_but);
				}
			}
			if (isset($history_search)) {
				if ($search) {
					$search = Func::andOp(array_merge([$search], $history_search));
				}
				elseif (count($history_search) > 1) {
					$search = Func::andOp($history_search);
				}
				else {
					$search = $history_search;
				}
			}
		}
		return [array_combine($properties_path, $properties_path), $search];
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for default 'list-typed' view controller
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		$this->class_names = $class_name;
		$class_name = $parameters->getMainObject()->element_class_name;
		Loc::enterContext($class_name);
		$parameters = $this->getViewParameters($parameters, $form, $class_name);
		$view = View::run($parameters, $form, $files, Names::setToClass($class_name), Feature::F_LIST);
		Loc::exitContext();
		return $view;
	}

	//-------------------------------------------------------------------------------- searchProperty
	/**
	 * @param $property Reflection_Property
	 * @param $value    string
	 * @return Reflection_Property_Value
	 */
	private function searchProperty(Reflection_Property $property, $value)
	{
		if (strlen($value) && !is_null($value)) {
			if (
				$property->getType()->isClass()
				&& !$property->getAnnotation(Store_Annotation::ANNOTATION)->value
			) {
				$value = Dao::read($value, $property->getType()->asString());
			}
			$property = new Reflection_Property_Value($property->class, $property->name, $value, true);
			if (!$property->getType()->isString()) {
				$property->setAnnotationLocal(Link_Annotation::ANNOTATION)->value = null;
				$property->setAnnotationLocal(Var_Annotation::ANNOTATION)->value  = Type::STRING;
			}
			$property->value(Loc::propertyToIso($property, $value));
		}
		return $property;
	}

}
