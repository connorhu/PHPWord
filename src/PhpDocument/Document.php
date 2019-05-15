<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
*/

namespace PhpOffice\PhpDocument;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\Metadata;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Media;
use PhpOffice\PhpWord\StyleBag;


/**
 * Document class
 *
 * @method Collection\Titles getTitles()
 * @method Collection\Footnotes getFootnotes()
 * @method Collection\Endnotes getEndnotes()
 * @method Collection\Charts getCharts()
 * @method Collection\Comments getComments()
 * @method int addBookmark(Element\Bookmark $bookmark)
 * @method int addTitle(Element\Title $title)
 * @method int addFootnote(Element\Footnote $footnote)
 * @method int addEndnote(Element\Endnote $endnote)
 * @method int addChart(Element\Chart $chart)
 * @method int addComment(Element\Comment $comment)
 *
 * @method Style\Paragraph addParagraphStyle(string $styleName, mixed $styles)
 * @method Style\Font addFontStyle(string $styleName, mixed $fontStyle, mixed $paragraphStyle = null)
 * @method Style\Font addLinkStyle(string $styleName, mixed $styles)
 * @method Style\Font addTitleStyle(mixed $depth, mixed $fontStyle, mixed $paragraphStyle = null)
 * @method Style\Table addTableStyle(string $styleName, mixed $styleTable, mixed $styleFirstRow = null)
 * @method Style\Numbering addNumberingStyle(string $styleName, mixed $styles)
 *
 * @deprecated
 */
class Document
{
    /**
     * Default font settings
     *
     * @deprecated 0.11.0 Use Settings constants
     *
     * @const string|int
     */
    const DEFAULT_FONT_NAME = Settings::DEFAULT_FONT_NAME;
    /**
     * @deprecated 0.11.0 Use Settings constants
     */
    const DEFAULT_FONT_SIZE = Settings::DEFAULT_FONT_SIZE;
    /**
     * @deprecated 0.11.0 Use Settings constants
     */
    const DEFAULT_FONT_COLOR = Settings::DEFAULT_FONT_COLOR;
    /**
     * @deprecated 0.11.0 Use Settings constants
     */
    const DEFAULT_FONT_CONTENT_TYPE = Settings::DEFAULT_FONT_CONTENT_TYPE;

    /**
     * Collection of sections
     *
     * @var \PhpOffice\PhpWord\Element\Section[]
     */
    private $sections = array();

    /**
     * Collections
     *
     * @var array
     */
    private $collections = array();

    /**
     * Metadata
     *
     * @var array
     * @since 0.12.0
     */
    private $metadata = array();

    /**
     * Setting
     *
     * @var Settings
     */
    private $settings;

    /**
     * Setting
     *
     * @var StyleBag
     */
    private $styleBag;

    /*
     * Create new instance
     *
     * Collections are created dynamically
     */
    public function __construct()
    {
        // Reset Media and styles
        Media::resetElements();
        $this->settings = new Settings();
        $this->styleBag = new StyleBag();

        // Collection
        $collections = array('Bookmarks', 'Titles', 'Footnotes', 'Endnotes', 'Charts', 'Comments');
        foreach ($collections as $collection) {
            $class = 'PhpOffice\\PhpWord\\Collection\\' . $collection;
            $this->collections[$collection] = new $class();
        }

        // Metadata
        $metadata = [
            Metadata\DocInfo::class,
            Metadata\Settings::class,
            Metadata\Compatibility::class
        ];
        foreach ($metadata as $class) {
            $this->metadata[substr($class, strrpos($class, '\\')+1)] = new $class();
        }
    }

    /**
     * Dynamic function call to reduce static dependency
     *
     * @since 0.12.0
     *
     * @param mixed $function
     * @param mixed $args
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call($function, $args)
    {
        $function = strtolower($function);

        $getCollection = array();
        $addCollection = array();
        $addStyle = array();

        $collections = array('Bookmark', 'Title', 'Footnote', 'Endnote', 'Chart', 'Comment');
        foreach ($collections as $collection) {
            $getCollection[] = strtolower("get{$collection}s");
            $addCollection[] = strtolower("add{$collection}");
        }

        $styles = array('Paragraph', 'Font', 'Table', 'Numbering', 'Link', 'Title');
        foreach ($styles as $style) {
            $addStyle[] = strtolower("add{$style}Style");
        }

        // Run get collection method
        if (in_array($function, $getCollection)) {
            $key = ucfirst(str_replace('get', '', $function));

            return $this->collections[$key];
        }

        // Run add collection item method
        if (in_array($function, $addCollection)) {
            $key = ucfirst(str_replace('add', '', $function) . 's');

            /** @var \PhpOffice\PhpWord\Collection\AbstractCollection $collectionObject */
            $collectionObject = $this->collections[$key];

            return $collectionObject->addItem(isset($args[0]) ? $args[0] : null);
        }

        // Run add style method
        if (in_array($function, $addStyle)) {
            return $this->styleBag->{$function}(...$args);
        }

        // Exception
        throw new \BadMethodCallException("Method $function is not defined.");
    }

    /**
     * Get document properties object
     *
     * @return \PhpOffice\PhpWord\Metadata\DocInfo
     */
    public function getDocInfo()
    {
        return $this->metadata['DocInfo'];
    }

    /**
     * Get protection
     *
     * @return \PhpOffice\PhpWord\Metadata\Protection
     * @since 0.12.0
     * @deprecated Get the Document protection from PhpWord->getSettings()->getDocumentProtection();
     * @codeCoverageIgnore
     */
    public function getProtection()
    {
        @trigger_error('getProtection method deprecated. use PhpWord->getSettings()->getDocumentProtection() method instead', E_USER_DEPRECATED);

        return $this->getSettings()->getDocumentProtection();
    }

    /**
     * Get compatibility
     *
     * @return \PhpOffice\PhpWord\Metadata\Compatibility
     * @since 0.12.0
     */
    public function getCompatibility()
    {
        return $this->metadata['Compatibility'];
    }

    /**
     * Get document settings
     *
     * @return \PhpOffice\PhpWord\Metadata\Settings
     * @deprecated
     * @since 0.14.0
     */
    public function getSettings()
    {
        @trigger_error('getProtection method deprecated. use PhpWord->getSettings()->getDocumentProtection() method instead', E_USER_DEPRECATED);
        return $this->getDocumentSettings();
    }
    
    /**
     * Get document settings
     *
     * @return \PhpOffice\PhpWord\Metadata\Settings
     */
    public function getDocumentSettings() : Metadata\Settings
    {
        return $this->metadata['Settings'];
    }

    /**
     * Get all sections
     *
     * @return \PhpOffice\PhpWord\Element\Section[]
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Returns the section at the requested position
     *
     * @param int $index
     * @return \PhpOffice\PhpWord\Element\Section|null
     */
    public function getSection($index)
    {
        if (array_key_exists($index, $this->sections)) {
            return $this->sections[$index];
        }

        return null;
    }

    /**
     * Create new section
     *
     * @param array $style
     * @return \PhpOffice\PhpWord\Element\Section
     */
    public function addSection($style = null)
    {
        $section = new Section(count($this->sections) + 1, $style);
        $section->setPhpWord($this);
        $this->sections[] = $section;

        return $section;
    }

    /**
     * Sorts the sections using the callable passed
     *
     * @see http://php.net/manual/en/function.usort.php for usage
     * @param callable $sorter
     */
    public function sortSections($sorter)
    {
        usort($this->sections, $sorter);
    }

    /**
     * Get default font name
     *
     * @return string
     */
    public function getDefaultFontName()
    {
        return $this->settings->getDefaultFontName();
    }

    /**
     * Set default font name.
     *
     * @param string $fontName
     */
    public function setDefaultFontName($fontName)
    {
        $this->settings->setDefaultFontName($fontName);
    }

    /**
     * Get default font size
     *
     * @return int
     */
    public function getDefaultFontSize()
    {
        return $this->settings->getDefaultFontSize();
    }

    /**
     * Set default font size.
     *
     * @param int $fontSize
     */
    public function setDefaultFontSize($fontSize)
    {
        $this->settings->setDefaultFontSize($fontSize);
    }

    /**
     * Set default paragraph style definition to styles.xml
     *
     * @param array $styles Paragraph style definition
     * @return \PhpOffice\PhpWord\Style\Paragraph
     */
    public function setDefaultParagraphStyle($styles)
    {
        return $this->styleBag->setDefaultParagraphStyle($styles);
    }

    /**
     * Load template by filename
     *
     * @deprecated 0.12.0 Use `new TemplateProcessor($documentTemplate)` instead.
     *
     * @param  string $filename Fully qualified filename
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception
     *
     * @return TemplateProcessor
     *
     * @codeCoverageIgnore
     */
    public function loadTemplate($filename)
    {
        if (file_exists($filename)) {
            return new TemplateProcessor($filename);
        }
        throw new Exception("Template file {$filename} not found.");
    }

    /**
     * Save to file or download
     *
     * All exceptions should already been handled by the writers
     *
     * @param string $filename
     * @param string $format
     * @param bool $download
     * @return bool
     */
    public function save($filename, $format = 'Word2007', $download = false)
    {
        $mime = array(
            'Word2007'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ODText'    => 'application/vnd.oasis.opendocument.text',
            'RTF'       => 'application/rtf',
            'HTML'      => 'text/html',
            'PDF'       => 'application/pdf',
        );

        $writer = IOFactory::createWriter($this, $format);

        if ($download === true) {
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Type: ' . $mime[$format]);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Expires: 0');
            $filename = 'php://output'; // Change filename to force download
        }

        $writer->save($filename);

        return true;
    }

    /**
     * Create new section
     *
     * @deprecated 0.10.0
     *
     * @param array $settings
     *
     * @return \PhpOffice\PhpWord\Element\Section
     *
     * @codeCoverageIgnore
     */
    public function createSection($settings = null)
    {
        return $this->addSection($settings);
    }

    /**
     * Get document properties object
     *
     * @deprecated 0.12.0
     *
     * @return \PhpOffice\PhpWord\Metadata\DocInfo
     *
     * @codeCoverageIgnore
     */
    public function getDocumentProperties()
    {
        return $this->getDocInfo();
    }

    /**
     * Set document properties object
     *
     * @deprecated 0.12.0
     *
     * @param \PhpOffice\PhpWord\Metadata\DocInfo $documentProperties
     *
     * @return self
     *
     * @codeCoverageIgnore
     */
    public function setDocumentProperties($documentProperties)
    {
        $this->metadata['Document'] = $documentProperties;

        return $this;
    }
    
    public function getPhpWordSettings() : Settings
    {
        return $this->settings;
    }
    
    public function getStyleBag() : StyleBag
    {
        return $this->styleBag;
    }
}

class_alias(Document::class, 'PhpOffice\PhpWord\PhpWord', true);