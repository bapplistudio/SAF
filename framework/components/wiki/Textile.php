<?php
namespace SAF\Framework;

/** @noinspection PhpIncludeInspection */
require_once "vendor/textile/classTextile.php";

/**
 * Textile review B-Appli style, in order to be more ergonomic
 *
 * Replace textile's '_' by '/' for italic
 * Replace textile's '+' by '_' for underlined
 */
class Textile extends \Textile
{

	//----------------------------------------------------------------------------------- $span_depth
	/**
	 * This overrides the Textile::$span_depth property, which declaration has been forgotten
	 *
	 * @var integer
	 */
	public $span_depth;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * This override replace '_' by '/' for em and '+' by '_' for ins
	 * Default is html5
	 *
	 * @param string $doctype The output document type, either 'xhtml' or 'html5'
	 */
	public function __construct($doctype = 'html5')
	{
		parent::__construct($doctype);

		$this->span_tags = array(
			'*'  => 'strong',
			'**' => 'b',
			'??' => 'cite',
			'/'  => 'em',
			'//' => 'i',
			'-'  => 'del',
			'%'  => 'span',
			'_'  => 'ins',
			'~'  => 'sub',
			'^'  => 'sup',
		);
	}

	//----------------------------------------------------------------------------------------- parse
	/**
	 * @param $text string
	 * @return string
	 */
	public static function parse($text)
	{
		return (new Textile)->textileThis($text);
	}

	//----------------------------------------------------------------------------------------- spans
	/**
	 * This override replaces '/' by '`' as REGEX separator
	 * Replaces textile spans with their equivalent HTML inline tags.
	 *
	 * @param  string $text The textile document to perform the replacements in.
	 * @return string       The textile document with spans replaced by their HTML inline equivalents
	 */
	protected function spans($text)
	{
		$span_tags = array_keys($this->span_tags);
		$pnct = ".,\"'?!;:‹›«»„“”‚‘’";
		$this->span_depth++;

		if ($this->span_depth <= $this->max_span_depth) {
			foreach ($span_tags as $f) {
				$f = preg_quote($f);
				$text = preg_replace_callback(
					"`
          (^|(?<=[\s>$pnct\(])|[{[]) # pre
          ($f)(?!$f)                 # tag
          ({$this->lc})              # atts - do not use horizontal alignment; it kills html tags within inline elements.
          (?::(\S+))?                # cite
          ([^\s$f]+|\S.*?[^\s$f\n])  # content
          ([$pnct]*)                 # end
          $f
          ($|[\[\]}<]|(?=[$pnct]{1,2}[^0-9]|\s|\)))  # tail
          `x" . $this->regex_snippets['mod'],
					array(&$this, "fSpan"),
					$text
				);
			}
		}
		$this->span_depth--;
		return $text;
	}

}
