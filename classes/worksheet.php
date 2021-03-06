<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * PHP Excel library. Helper class to make spreadsheet creation easier.
 *
 * @package    Spreadsheet
 * @author     Korney Czukowski
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 */
class Worksheet
{
	/**
	 * Flag whether to size column widths automatically
	 * 
	 * @var boolean
	 */
	protected $auto_size = FALSE;
	/**
	 * Column names, may be associative. Order of this array defines columns order of this worksheet.
	 * The following examples correspond with the examples for `$data` property.
	 * 
	 * Example 1:
	 * 
	 *     $columns = array('First Name', 'Last Name', 'Phone');
	 * 
	 * Example 2:
	 * 
	 *     $columns = array(
	 *         'first_name' => 'First Name',
	 *         'last_name' => 'Last Name',
	 *         'phone' => 'Phone',
	 *     );
	 * 
	 * Example 3:
	 * 
	 *     $columns = array(
	 *         'first_name' => 'First Name',
	 *         'last_name' => 'Last Name',
	 *         'phone' => 'Phone',
	 *         'full_name' => 'Full name',
	 *     );
	 * 
	 * @var array
	 */
	protected $columns = array();
	/**
	 * This array keeps data rows.
	 * 
	 * Example 1:
	 * 
	 *     $data = array(
	 *         array('Martin', 'Hoover', '207-422-9702'), // Note: these data are completely made up
	 *         array('Anna', 'Lantz', '208-704-9524'),
	 *     );
	 * 
	 * Example 2:
	 * 
	 *     $data = array(
	 *         array(
	 *             'first_name' => 'Martin',
	 *             'last_name' => 'Hoover',
	 *             'phone' => '207-422-9702',
	 *         ),
	 *         array(
	 *             'phone' => '208-704-9524',
	 *             'last_name' => 'Lantz',
	 *             'first_name' => 'Anna',
	 *         ),
	 *     );
	 * 
	 * Example 3:
	 * 
	 *     class Person {
	 *         public function full_name() {
	 *             return $this->first_name.' '.$this->last_name;
	 *         }
	 *     }
	 *     $data = array(
	 *         Person {
	 *             $first_name => 'Martin',
	 *             $last_name => 'Hoover',
	 *             $phone => '207-422-9702',
	 *         },
	 *         Person {
	 *             $first_name => 'Anna',
	 *             $last_name => 'Lantz',
	 *             $phone => '208-704-9524',
	 *         },
	 *     );
	 * 
	 * Note: the data in these examples are all completely made up and generated by fakenamegenerator.com
	 * 
	 * @var array
	 */
	protected $data = array();
	/**
	 * Flag whether to include column names as the 1st row of the worksheet
	 * 
	 * @var boolean
	 */
	protected $include_names = FALSE;
	/**
	 * Column formats. It needs to be in a similar manner like `$columns` property and the values may be either
	 * literal or `PHPExcel_Style_NumberFormat` constants. Default value is `PHPExcel_Style_NumberFormat::FORMAT_GENERAL`.
	 * 
	 * @var array
	 */
	protected $formats = array();
	/**
	 * Default worksheet title. This property is only used by class constructor
	 * 
	 * @var string
	 */
	protected $title;
	/**
	 * Column data types. It needs to be in a similar manner like `$columns` property and the values may be either
	 * literal or `PHPExcel_Cell_DataType` constants. Default value is `PHPExcel_Cell_DataType::TYPE_STRING`.
	 * 
	 * @var array
	 */
	protected $types = array();
	/**
	 * PHPExcel Worksheet instance
	 * 
	 * @var PHPExcel_Worksheet 
	 */
	protected $_worksheet;

	/**
	 * Class constructor
	 * @param PHPExcel_Worksheet $worksheet
	 */
	public function __construct(PHPExcel $spreadsheet, PHPExcel_Worksheet $worksheet = NULL)
	{
		if ($worksheet === NULL)
		{
			$this->_worksheet = new PHPExcel_Worksheet($spreadsheet);
		}
		else
		{
			$this->_worksheet = $worksheet;
		}
		// Add worksheet to a spreadsheet
		$spreadsheet->addSheet($this->_worksheet);
		// Set worksheet title
		if ($this->title !== NULL)
		{
			$this->title($this->title);
		}
	}

	/**
	 * Columns getter/setter
	 */
	public function columns($key = NULL, $value = NULL)
	{
		return $this->_get_set('columns', $key, $value);
	}

	/**
	 * Data getter/setter
	 */
	public function data($key = NULL, $value = NULL)
	{
		return $this->_get_set('data', $key, $value);
	}

	/**
	 * Column formats getter/setter
	 */
	public function formats($key = NULL, $value = NULL)
	{
		return $this->_get_set('formats', $key, $value);
	}

	/**
	 * Inserts data into worksheet and returns it
	 * 
	 * @return PHPExcel_Worksheet
	 */
	public function render()
	{
		// Set worksheet header
		if ($this->include_names)
		{
			$this->_set_row(1, $this->columns, TRUE);
			$offset = 2;
		}
		else
		{
			$offset = 1;
		}

		// Set data
		$rows = 0;
		foreach ($this->data as $row => $data)
		{
			$this->_set_row($row + $offset, $data);
			$rows++;
		}

		// Set column styles and width
		$column = 0;
		foreach ($this->columns as $key => $name)
		{
			$column_dim = PHPExcel_Cell::stringFromColumnIndex($column);
			$format = Arr::get($this->formats, $key);
			if ($format !== NULL)
			{
				$this->_worksheet->getStyle($column_dim.$offset.':'.$column_dim.($offset + $rows))
					->getNumberFormat()
					->setFormatCode($format);
			}
			if ($this->auto_size === TRUE)
			{
				$this->_worksheet
					->getColumnDimension($column_dim)
					->setAutoSize(TRUE);
			}
			$column++;
		}
		return $this->_worksheet;
	}

	/**
	 * Worksheet title getter/setter
	 */
	public function title($title = NULL)
	{
		if ($title === NULL)
		{
			return $this->_worksheet->getTitle();
		}
		else
		{
			$this->_worksheet->setTitle($title);
			return $this;
		}
	}

	/**
	 * Common getter/setter method
	 * 
	 * @param string $property
	 * @param mixed $key
	 * @param mixed $value
	 * @return mixed
	 */
	private function _get_set($property, $key = NULL, $value = NULL)
	{
		if ($key === NULL)
		{
			return $this->{$property};
		}
		elseif (is_array($key) OR $key instanceof Iterator)
		{
			$this->{$property} = $key;
		}
		else
		{
			$this->{$property}[$key] = $value;
		}
		return $this;
	}

	/**
	 * Sets cells of a single row
	 * 
	 * @param int $row
	 * @param mixed $cell_values
	 * @param boolean $header
	 * @return Worksheet
	 */
	private function _set_row($row, &$data, $header = FALSE)
	{
		$column = 0;
		$format = NULL;
		$type = PHPExcel_Cell_DataType::TYPE_STRING;
		foreach ($this->columns as $key => $name)
		{
			$value = NULL;
			if (is_array($data))
			{
				$value = $data[$key];
			}
			elseif (is_object($data))
			{
				if (method_exists($data, $key))
				{
					$value = $data->$key();
				}
				elseif (isset($data->$key))
				{
					$value = $data->$key;
				}
			}
			// Determine cell type and format
			if ($header === FALSE)
			{
				$type = Arr::get($this->types, $key);
			}
			// Set cell value
			$coordinates = PHPExcel_Cell::stringFromColumnIndex($column).$row;
			if ($type !== NULL)
			{
				$this->_worksheet->setCellValueExplicit($coordinates, $value, $type);
			}
			else
			{
				$this->_worksheet->setCellValue($coordinates, $value);
			}
			$column++;
		}
		return $this;
	}
}