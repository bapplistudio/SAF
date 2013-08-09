<?php
namespace SAF\Framework;

/**
 * This data link stores objects into files
 * - one directory per class (it's path is the full class name)
 * - one file per object (it's name is an internal integer identifier)
 */
class Files_Link extends Identifier_Map_Data_Link
{

	//----------------------------------------------------------------------------------------- count
	/**
	 * Count the number of elements that match filter
	 *
	 * @param $what       object|array source object for filter, only set properties will be used
	 * @param $class_name string must be set if is $what is a filter array instead of a filter object
	 * @return integer
	 */
	public function count($what, $class_name = null)
	{
		// TODO: Implement count() method.
	}

	//---------------------------------------------------------------------------------------- delete
	/**
	 * Delete an object from data source
	 *
	 * If object was originally read from data source, corresponding data will be overwritten.
	 * If object was not originally read from data source, nothing is done and returns false.
	 *
	 * @param $object object object to delete from data source
	 * @return bool true if deleted
	 */
	public function delete($object)
	{
		// TODO: Implement delete() method.
	}

	//--------------------------------------------------------------------------- getStoredProperties
	/**
	 * Returns the list of properties of class $class that are stored into data link
	 *
	 * If data link stores properties not existing into $class, they are listed too, as if they where official properties of $class, but they storage object is a Dao_Column and not a Reflection_Property.
	 *
	 * @param $class string|Reflection_Class
	 * @return Reflection_Property[]|Dao_Column[]
	 */
	public function getStoredProperties($class)
	{
		// TODO: Implement getStoredProperties() method.
	}

	//------------------------------------------------------------------------------------------ read
	/**
	 * Read an object from data source
	 *
	 * @param $identifier mixed identifier for the object
	 * @param $class_name string class for read object
	 * @return object an object of class objectClass, read from data source, or null if nothing found
	 */
	public function read($identifier, $class_name)
	{
		// TODO: Implement read() method.
	}

	//--------------------------------------------------------------------------------------- readAll
	/**
	 * Read all objects of a given class from data source
	 *
	 * @param $class_name string class name of read objects
	 * @param $options    string|array some options for advanced read
	 * @return object[] a collection of read objects
	 */
	public function readAll($class_name, $options = null)
	{
		// TODO: Implement readAll() method.
	}

	//---------------------------------------------------------------------------------------- search
	/**
	 * Search objects from data source
	 *
	 * It is highly recommended to instantiate the $what object using Search_Object::instantiate() in order to initialize all properties as unset and build a correct search object.
	 * If some properties are an not-loaded objects, the search will be done on the object identifier, without joins to the linked object.
	 * If some properties are loaded objects : if the object comes from a read, the search will be done on the object identifier, without join. If object is not linked to data-link, the search is done with the linked object as others search criterion.
	 *
	 * @param $what       object|array source object for filter, or filter array (need class_name) only set properties will be used for search
	 * @param $class_name string must be set if is $what is a filter array instead of a filter object
	 * @param $options    Dao_Option|Dao_Option[] array some options for advanced search
	 * @return object[] a collection of read objects
	 */
	public function search($what, $class_name = null, $options = null)
	{
		// TODO: Implement search() method.
	}

	//---------------------------------------------------------------------------------------- select
	/**
	 * Read selected columns only from data source, using optional filter
	 *
	 * @param $class         string class for the read object
	 * @param $columns       string[] the list of the columns names : only those properties will be read. You can use "column.sub_column" to get values from linked objects from the same data source.
	 * @param $filter_object object|array source object for filter, set properties will be used for search. Can be an array associating properties names to corresponding search value too.
	 * @param $options       Dao_Option|Dao_Option[] some options for advanced search
	 * @return List_Data a list of read records. Each record values (may be objects) are stored in the same order than columns.
	 */
	public function select($class, $columns, $filter_object = null, $options = null)
	{
		// TODO: Implement select() method.
	}

	//----------------------------------------------------------------------------------------- write
	/**
	 * Write an object into data source
	 *
	 * If object was originally read from data source, corresponding data will be overwritten
	 * If object was not originally read from data source nor linked to it using replace(), a new
	 * record will be written into data source using this object's data.
	 *
	 * @param $object object object to write into data source
	 * @return object the written object
	 */
	public function write($object)
	{
		// TODO: Implement write() method.
	}

}