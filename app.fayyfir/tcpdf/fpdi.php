<?php
/**
 * Bundled FPDI for TCPDF (Standalone Single File)
 */

// ==========================================
// File: FpdiException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

/**
 * Base exception class for the FPDI package.
 */
class FpdiException extends \Exception
{
}

// ==========================================
// File: PdfParserException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser;

use setasign\Fpdi\FpdiException;

/**
 * Exception for the pdf parser class
 */
class PdfParserException extends FpdiException
{
    /**
     * @var int
     */
    const NOT_IMPLEMENTED = 0x0001;

    /**
     * @var int
     */
    const IMPLEMENTED_IN_FPDI_PDF_PARSER = 0x0002;

    /**
     * @var int
     */
    const INVALID_DATA_TYPE = 0x0003;

    /**
     * @var int
     */
    const FILE_HEADER_NOT_FOUND = 0x0004;

    /**
     * @var int
     */
    const PDF_VERSION_NOT_FOUND = 0x0005;

    /**
     * @var int
     */
    const INVALID_DATA_SIZE = 0x0006;
}

// ==========================================
// File: CrossReferenceException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\PdfParserException;

/**
 * Exception used by the CrossReference and Reader classes.
 */
class CrossReferenceException extends PdfParserException
{
    /**
     * @var int
     */
    const INVALID_DATA = 0x0101;

    /**
     * @var int
     */
    const XREF_MISSING = 0x0102;

    /**
     * @var int
     */
    const ENTRIES_TOO_LARGE = 0x0103;

    /**
     * @var int
     */
    const ENTRIES_TOO_SHORT = 0x0104;

    /**
     * @var int
     */
    const NO_ENTRIES = 0x0105;

    /**
     * @var int
     */
    const NO_TRAILER_FOUND = 0x0106;

    /**
     * @var int
     */
    const NO_STARTXREF_FOUND = 0x0107;

    /**
     * @var int
     */
    const NO_XREF_FOUND = 0x0108;

    /**
     * @var int
     */
    const UNEXPECTED_END = 0x0109;

    /**
     * @var int
     */
    const OBJECT_NOT_FOUND = 0x010A;

    /**
     * @var int
     */
    const COMPRESSED_XREF = 0x010B;

    /**
     * @var int
     */
    const ENCRYPTED = 0x010C;

    /**
     * @var int
     */
    const CYCLIC_STRUCTURE = 0x010D;
}

// ==========================================
// File: FilterException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

use setasign\Fpdi\PdfParser\PdfParserException;

/**
 * Exception for filters
 */
class FilterException extends PdfParserException
{
    const UNSUPPORTED_FILTER = 0x0201;

    const NOT_IMPLEMENTED = 0x0202;
}

// ==========================================
// File: Ascii85Exception.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for Ascii85 filter class
 */
class Ascii85Exception extends FilterException
{
    /**
     * @var integer
     */
    const ILLEGAL_CHAR_FOUND = 0x0301;

    /**
     * @var integer
     */
    const ILLEGAL_LENGTH = 0x0302;
}

// ==========================================
// File: FlateException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for flate filter class
 */
class FlateException extends FilterException
{
    /**
     * @var integer
     */
    const NO_ZLIB = 0x0401;

    /**
     * @var integer
     */
    const DECOMPRESS_ERROR = 0x0402;
}

// ==========================================
// File: LzwException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Exception for LZW filter class
 */
class LzwException extends FilterException
{
    /**
     * @var integer
     */
    const LZW_FLAVOUR_NOT_SUPPORTED = 0x0501;
}

// ==========================================
// File: PdfTypeException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\PdfParserException;

/**
 * Exception class for pdf type classes
 */
class PdfTypeException extends PdfParserException
{
    /**
     * @var int
     */
    const NO_NEWLINE_AFTER_STREAM_KEYWORD = 0x0601;
}

// ==========================================
// File: PdfReaderException.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfReader;

use setasign\Fpdi\FpdiException;

/**
 * Exception for the pdf reader class
 */
class PdfReaderException extends FpdiException
{
    /**
     * @var int
     */
    const KIDS_EMPTY = 0x0101;

    /**
     * @var int
     */
    const UNEXPECTED_DATA_TYPE = 0x0102;

    /**
     * @var int
     */
    const MISSING_DATA = 0x0103;
}

// ==========================================
// File: ReaderInterface.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\Type\PdfDictionary;

/**
 * ReaderInterface for cross-reference readers.
 */
interface ReaderInterface
{
    /**
     * Get an offset by an object number.
     *
     * @param int $objectNumber
     * @return int|bool False if the offset was not found.
     */
    public function getOffsetFor($objectNumber);

    /**
     * Get the trailer related to this cross reference.
     *
     * @return PdfDictionary
     */
    public function getTrailer();
}

// ==========================================
// File: FilterInterface.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Interface for filters
 */
interface FilterInterface
{
    /**
     * Decode a string.
     *
     * @param string $data The input string
     * @return string
     */
    public function decode($data);
}

// ==========================================
// File: PdfType.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;

/**
 * A class defining a PDF data type
 */
class PdfType
{
    /**
     * Resolves a PdfType value to its value.
     *
     * This method is used to evaluate indirect and direct object references until a final value is reached.
     *
     * @param PdfType $value
     * @param PdfParser $parser
     * @param bool $stopAtIndirectObject
     * @param array $ensuredObjectsList A list of all ensured indirect objects to prevent recursion
     * @return PdfType
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public static function resolve(
        PdfType $value,
        PdfParser $parser,
        $stopAtIndirectObject = false,
        array &$ensuredObjectsList = []
    ) {
        if ($value instanceof PdfIndirectObjectReference) {
            $value = $parser->getIndirectObject($value->value);
        }

        if ($value instanceof PdfIndirectObject) {
            if ($stopAtIndirectObject === true) {
                return $value;
            }

            if (\in_array($value->objectNumber, $ensuredObjectsList, true)) {
                throw new PdfParserException(
                    \sprintf('Indirect reference recursion detected (%s).', $value->objectNumber)
                );
            }
            $ensuredObjectsList[] = $value->objectNumber;
            return self::resolve($value->value, $parser, $stopAtIndirectObject, $ensuredObjectsList);
        }

        return $value;
    }

    /**
     * Ensure that a value is an instance of a specific PDF type.
     *
     * @param string $type
     * @param PdfType $value
     * @param string $errorMessage
     * @return mixed
     * @throws PdfTypeException
     */
    protected static function ensureType($type, $value, $errorMessage)
    {
        if (!($value instanceof $type)) {
            throw new PdfTypeException(
                $errorMessage,
                PdfTypeException::INVALID_DATA_TYPE
            );
        }

        return $value;
    }

    /**
     * Flatten indirect object references to direct objects.
     *
     * @param PdfType $value
     * @param PdfParser $parser
     * @return PdfType
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public static function flatten(PdfType $value, PdfParser $parser)
    {
        if ($value instanceof PdfIndirectObjectReference) {
            return self::flatten(self::resolve($value, $parser), $parser);
        }

        if ($value instanceof PdfDictionary || $value instanceof PdfArray) {
            foreach ($value->value as $key => $_value) {
                $value->value[$key] = self::flatten($_value, $parser);
            }
        }

        if ($value instanceof PdfStream) {
            throw new PdfTypeException('There is a stream object found which cannot be flattened to a direct object.');
        }

        return $value;
    }

    /**
     * The value of the PDF type.
     *
     * @var mixed
     */
    public $value;
}

// ==========================================
// File: PdfArray.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\Tokenizer;

/**
 * Class representing a PDF array object
 *
 * @property array $value The value of the PDF type.
 */
class PdfArray extends PdfType
{
    /**
     * Parses an array of the passed tokenizer and parser.
     *
     * @param Tokenizer $tokenizer
     * @param PdfParser $parser
     * @return false|self
     * @throws PdfTypeException
     */
    public static function parse(Tokenizer $tokenizer, PdfParser $parser)
    {
        $result = [];

        // Recurse into this function until we reach the end of the array.
        while (($token = $tokenizer->getNextToken()) !== ']') {
            if ($token === false || ($value = $parser->readValue($token)) === false) {
                return false;
            }

            $result[] = $value;
        }

        $v = new self();
        $v->value = $result;

        return $v;
    }

    /**
     * Helper method to create an instance.
     *
     * @param PdfType[] $values
     * @return self
     */
    public static function create(array $values = [])
    {
        $v = new self();
        $v->value = $values;

        return $v;
    }

    /**
     * Ensures that the passed array is a PdfArray instance with a (optional) specific size.
     *
     * @param mixed $array
     * @param null|int $size
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($array, $size = null)
    {
        $result = PdfType::ensureType(self::class, $array, 'Array value expected.');

        if ($size !== null && \count($array->value) !== $size) {
            throw new PdfTypeException(
                \sprintf('Array with %s entries expected.', $size),
                PdfTypeException::INVALID_DATA_SIZE
            );
        }

        return $result;
    }
}

// ==========================================
// File: PdfBoolean.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

/**
 * Class representing a boolean PDF object
 */
class PdfBoolean extends PdfType
{
    /**
     * Helper method to create an instance.
     *
     * @param bool $value
     * @return self
     */
    public static function create($value)
    {
        $v = new self();
        $v->value = (bool) $value;
        return $v;
    }

    /**
     * Ensures that the passed value is a PdfBoolean instance.
     *
     * @param mixed $value
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($value)
    {
        return PdfType::ensureType(self::class, $value, 'Boolean value expected.');
    }
}

// ==========================================
// File: PdfDictionary.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Tokenizer;

/**
 * Class representing a PDF dictionary object
 */
class PdfDictionary extends PdfType
{
    /**
     * Parses a dictionary of the passed tokenizer, stream-reader and parser.
     *
     * @param Tokenizer $tokenizer
     * @param StreamReader $streamReader
     * @param PdfParser $parser
     * @return bool|self
     * @throws PdfTypeException
     */
    public static function parse(Tokenizer $tokenizer, StreamReader $streamReader, PdfParser $parser)
    {
        $entries = [];

        while (true) {
            $token = $tokenizer->getNextToken();
            if ($token === '>' && $streamReader->getByte() === '>') {
                $streamReader->addOffset(1);
                break;
            }

            $key = $parser->readValue($token);
            if ($key === false) {
                return false;
            }

            // ensure the first value to be a Name object
            if (!($key instanceof PdfName)) {
                $lastToken = null;
                // ignore all other entries and search for the closing brackets
                while (($token = $tokenizer->getNextToken()) !== '>' || $lastToken !== '>') {
                    if ($token === false) {
                        return false;
                    }
                    $lastToken = $token;
                }

                break;
            }


            $value = $parser->readValue();
            if ($value === false) {
                return false;
            }

            if ($value instanceof PdfNull) {
                continue;
            }

            // catch missing value
            if ($value instanceof PdfToken && $value->value === '>' && $streamReader->getByte() === '>') {
                $streamReader->addOffset(1);
                break;
            }

            $entries[$key->value] = $value;
        }

        $v = new self();
        $v->value = $entries;

        return $v;
    }

    /**
     * Helper method to create an instance.
     *
     * @param PdfType[] $entries The keys are the name entries of the dictionary.
     * @return self
     */
    public static function create(array $entries = [])
    {
        $v = new self();
        $v->value = $entries;

        return $v;
    }

    /**
     * Get a value by its key from a dictionary or a default value.
     *
     * @param mixed $dictionary
     * @param string $key
     * @param PdfType|null $default
     * @return PdfNull|PdfType
     * @throws PdfTypeException
     */
    public static function get($dictionary, $key, ?PdfType $default = null)
    {
        $dictionary = self::ensure($dictionary);

        if (isset($dictionary->value[$key])) {
            return $dictionary->value[$key];
        }

        return $default ?? new PdfNull();
    }

    /**
     * Ensures that the passed value is a PdfDictionary instance.
     *
     * @param mixed $dictionary
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($dictionary)
    {
        return PdfType::ensureType(self::class, $dictionary, 'Dictionary value expected.');
    }
}

// ==========================================
// File: PdfHexString.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Class representing a hexadecimal encoded PDF string object
 */
class PdfHexString extends PdfType
{
    /**
     * Parses a hexadecimal string object from the stream reader.
     *
     * @param StreamReader $streamReader
     * @return false|self
     */
    public static function parse(StreamReader $streamReader)
    {
        $bufferOffset = $streamReader->getOffset();

        while (true) {
            $buffer = $streamReader->getBuffer(false);
            $pos = \strpos($buffer, '>', $bufferOffset);
            if ($pos === false) {
                if (!$streamReader->increaseLength()) {
                    return false;
                }
                continue;
            }

            break;
        }

        $result = \substr($buffer, $bufferOffset, $pos - $bufferOffset);
        $streamReader->setOffset($pos + 1);

        $v = new self();
        $v->value = $result;

        return $v;
    }

    /**
     * Helper method to create an instance.
     *
     * @param string $string The hex encoded string.
     * @return self
     */
    public static function create($string)
    {
        $v = new self();
        $v->value = $string;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfHexString instance.
     *
     * @param mixed $hexString
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($hexString)
    {
        return PdfType::ensureType(self::class, $hexString, 'Hex string value expected.');
    }
}

// ==========================================
// File: PdfIndirectObject.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Tokenizer;

/**
 * Class representing an indirect object
 */
class PdfIndirectObject extends PdfType
{
    /**
     * Parses an indirect object from a tokenizer, parser and stream-reader.
     *
     * @param int $objectNumber
     * @param int $objectGenerationNumber
     * @param PdfParser $parser
     * @param Tokenizer $tokenizer
     * @param StreamReader $reader
     * @return self|false
     * @throws PdfTypeException
     */
    public static function parse(
        $objectNumber,
        $objectGenerationNumber,
        PdfParser $parser,
        Tokenizer $tokenizer,
        StreamReader $reader
    ) {
        $value = $parser->readValue();
        if ($value === false) {
            return false;
        }

        $nextToken = $tokenizer->getNextToken();
        if ($nextToken === 'stream') {
            $value = PdfStream::parse($value, $reader, $parser);
        } elseif ($nextToken !== false) {
            $tokenizer->pushStack($nextToken);
        }

        $v = new self();
        $v->objectNumber = (int) $objectNumber;
        $v->generationNumber = (int) $objectGenerationNumber;
        $v->value = $value;

        return $v;
    }

    /**
     * Helper method to create an instance.
     *
     * @param int $objectNumber
     * @param int $generationNumber
     * @param PdfType $value
     * @return self
     */
    public static function create($objectNumber, $generationNumber, PdfType $value)
    {
        $v = new self();
        $v->objectNumber = (int) $objectNumber;
        $v->generationNumber = (int) $generationNumber;
        $v->value = $value;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfIndirectObject instance.
     *
     * @param mixed $indirectObject
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($indirectObject)
    {
        return PdfType::ensureType(self::class, $indirectObject, 'Indirect object expected.');
    }

    /**
     * The object number.
     *
     * @var int
     */
    public $objectNumber;

    /**
     * The generation number.
     *
     * @var int
     */
    public $generationNumber;
}

// ==========================================
// File: PdfIndirectObjectReference.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

/**
 * Class representing an indirect object reference
 */
class PdfIndirectObjectReference extends PdfType
{
    /**
     * Helper method to create an instance.
     *
     * @param int $objectNumber
     * @param int $generationNumber
     * @return self
     */
    public static function create($objectNumber, $generationNumber)
    {
        $v = new self();
        $v->value = (int) $objectNumber;
        $v->generationNumber = (int) $generationNumber;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfIndirectObject instance.
     *
     * @param mixed $value
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($value)
    {
        return PdfType::ensureType(self::class, $value, 'Indirect reference value expected.');
    }

    /**
     * The generation number.
     *
     * @var int
     */
    public $generationNumber;
}

// ==========================================
// File: PdfName.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Tokenizer;

/**
 * Class representing a PDF name object
 */
class PdfName extends PdfType
{
    /**
     * Parses a name object from the passed tokenizer and stream-reader.
     *
     * @param Tokenizer $tokenizer
     * @param StreamReader $streamReader
     * @return self
     */
    public static function parse(Tokenizer $tokenizer, StreamReader $streamReader)
    {
        $v = new self();
        if (\strspn($streamReader->getByte(), "\x00\x09\x0A\x0C\x0D\x20()<>[]{}/%") === 0) {
            $v->value = (string) $tokenizer->getNextToken();
            return $v;
        }

        $v->value = '';
        return $v;
    }

    /**
     * Unescapes a name string.
     *
     * @param string $value
     * @return string
     */
    public static function unescape($value)
    {
        if (\strpos($value, '#') === false) {
            return $value;
        }

        return \preg_replace_callback('/#([a-fA-F\d]{2})/', function ($matches) {
            return \chr(\hexdec($matches[1]));
        }, $value);
    }

    /**
     * Helper method to create an instance.
     *
     * @param string $string
     * @return self
     */
    public static function create($string)
    {
        $v = new self();
        $v->value = $string;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfName instance.
     *
     * @param mixed $name
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($name)
    {
        return PdfType::ensureType(self::class, $name, 'Name value expected.');
    }
}

// ==========================================
// File: PdfNull.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

/**
 * Class representing a PDF null object
 */
class PdfNull extends PdfType
{
    // empty body
}

// ==========================================
// File: PdfNumeric.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

/**
 * Class representing a numeric PDF object
 */
class PdfNumeric extends PdfType
{
    /**
     * Helper method to create an instance.
     *
     * @param int|float $value
     * @return PdfNumeric
     */
    public static function create($value)
    {
        $v = new self();
        $v->value = $value + 0;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfNumeric instance.
     *
     * @param mixed $value
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($value)
    {
        return PdfType::ensureType(self::class, $value, 'Numeric value expected.');
    }
}

// ==========================================
// File: PdfStream.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\Ascii85;
use setasign\Fpdi\PdfParser\Filter\AsciiHex;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\Filter\Flate;
use setasign\Fpdi\PdfParser\Filter\Lzw;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\FpdiPdfParser\PdfParser\Filter\Predictor;

/**
 * Class representing a PDF stream object
 */
class PdfStream extends PdfType
{
    /**
     * Parses a stream from a stream reader.
     *
     * @param PdfDictionary $dictionary
     * @param StreamReader $reader
     * @param PdfParser|null $parser Optional to keep backwards compatibility
     * @return self
     * @throws PdfTypeException
     */
    public static function parse(PdfDictionary $dictionary, StreamReader $reader, ?PdfParser $parser = null)
    {
        $v = new self();
        $v->value = $dictionary;
        $v->reader = $reader;
        $v->parser = $parser;

        $offset = $reader->getOffset();

        // Find the first "newline"
        while (($firstByte = $reader->getByte($offset)) !== false) {
            $offset++;
            if ($firstByte === "\n" || $firstByte === "\r") {
                break;
            }
        }

        if ($firstByte === false) {
            throw new PdfTypeException(
                'Unable to parse stream data. No newline after the stream keyword found.',
                PdfTypeException::NO_NEWLINE_AFTER_STREAM_KEYWORD
            );
        }

        $sndByte = $reader->getByte($offset);
        if ($sndByte === "\n" && $firstByte !== "\n") {
            $offset++;
        }

        $reader->setOffset($offset);
        // let's only save the byte-offset and read the stream only when needed
        $v->stream = $reader->getPosition() + $reader->getOffset();

        return $v;
    }

    /**
     * Helper method to create an instance.
     *
     * @param PdfDictionary $dictionary
     * @param string $stream
     * @return self
     */
    public static function create(PdfDictionary $dictionary, $stream)
    {
        $v = new self();
        $v->value = $dictionary;
        $v->stream = (string) $stream;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfStream instance.
     *
     * @param mixed $stream
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($stream)
    {
        return PdfType::ensureType(self::class, $stream, 'Stream value expected.');
    }

    /**
     * The stream or its byte-offset position.
     *
     * @var int|string
     */
    protected $stream;

    /**
     * The stream reader instance.
     *
     * @var StreamReader|null
     */
    protected $reader;

    /**
     * The PDF parser instance.
     *
     * @var PdfParser
     */
    protected $parser;

    /**
     * Get the stream data.
     *
     * @param bool $cache Whether cache the stream data or not.
     * @return bool|string
     * @throws PdfTypeException
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public function getStream($cache = false)
    {
        if (\is_int($this->stream)) {
            $length = PdfDictionary::get($this->value, 'Length');
            if ($this->parser !== null) {
                $length = PdfType::resolve($length, $this->parser);
            }

            if (!($length instanceof PdfNumeric) || $length->value === 0) {
                $this->reader->reset($this->stream, 100000);
                $buffer = $this->extractStream();
            } else {
                $this->reader->reset($this->stream, $length->value);
                $buffer = $this->reader->getBuffer(false);
                if ($this->parser !== null) {
                    $this->reader->reset($this->stream + strlen($buffer));
                    $this->parser->getTokenizer()->clearStack();
                    $token = $this->parser->readValue();
                    if (!($token instanceof PdfToken) || $token->value !== 'endstream') {
                        $this->reader->reset($this->stream, 100000);
                        $buffer = $this->extractStream();
                        $this->reader->reset($this->stream + strlen($buffer));
                    }
                }
            }

            if ($cache === false) {
                return $buffer;
            }

            $this->stream = $buffer;
            $this->reader = null;
        }

        return $this->stream;
    }

    /**
     * Extract the stream "manually".
     *
     * @return string
     * @throws PdfTypeException
     */
    protected function extractStream()
    {
        while (true) {
            $buffer = $this->reader->getBuffer(false);
            $length = \strpos($buffer, 'endstream');
            if ($length === false) {
                if (!$this->reader->increaseLength(100000)) {
                    throw new PdfTypeException('Cannot extract stream.');
                }
                continue;
            }
            break;
        }

        $buffer = \substr($buffer, 0, $length);
        $lastByte = \substr($buffer, -1);

        /* Check for EOL marker =
         *   CARRIAGE RETURN (\r) and a LINE FEED (\n) or just a LINE FEED (\n},
         *   and not by a CARRIAGE RETURN (\r) alone
         */
        if ($lastByte === "\n") {
            $buffer = \substr($buffer, 0, -1);

            $lastByte = \substr($buffer, -1);
            if ($lastByte === "\r") {
                $buffer = \substr($buffer, 0, -1);
            }
        }

        // There are streams in the wild, which have only white signs in them but need to be parsed manually due
        // to a problem encountered before (e.g. Length === 0). We should set them to empty streams to avoid problems
        // in further processing (e.g. applying of filters).
        if (\trim($buffer) === '') {
            $buffer = '';
        }

        return $buffer;
    }

    /**
     * Get all filters defined for this stream.
     *
     * @return PdfType[]
     * @throws PdfTypeException
     */
    public function getFilters()
    {
        $filters = PdfDictionary::get($this->value, 'Filter');
        if ($filters instanceof PdfNull) {
            return [];
        }

        if ($filters instanceof PdfArray) {
            $filters = $filters->value;
        } else {
            $filters = [$filters];
        }

        return $filters;
    }

    /**
     * Get the unfiltered stream data.
     *
     * @return string
     * @throws FilterException
     * @throws PdfParserException
     */
    public function getUnfilteredStream()
    {
        $stream = $this->getStream();
        $filters = $this->getFilters();
        if ($filters === []) {
            return $stream;
        }

        $decodeParams = PdfDictionary::get($this->value, 'DecodeParms');
        if ($decodeParams instanceof PdfArray) {
            $decodeParams = $decodeParams->value;
        } else {
            $decodeParams = [$decodeParams];
        }

        foreach ($filters as $key => $filter) {
            if (!($filter instanceof PdfName)) {
                continue;
            }

            $decodeParam = null;
            if (isset($decodeParams[$key])) {
                $decodeParam = ($decodeParams[$key] instanceof PdfDictionary ? $decodeParams[$key] : null);
            }

            switch ($filter->value) {
                case 'FlateDecode':
                case 'Fl':
                case 'LZWDecode':
                case 'LZW':
                    if (\strpos($filter->value, 'LZW') === 0) {
                        $filterObject = new Lzw();
                    } else {
                        $filterObject = new Flate();
                    }

                    $stream = $filterObject->decode($stream);

                    if ($decodeParam instanceof PdfDictionary) {
                        $predictor = PdfDictionary::get($decodeParam, 'Predictor', PdfNumeric::create(1));
                        if ($predictor->value !== 1) {
                            if (!\class_exists(Predictor::class)) {
                                throw new PdfParserException(
                                    'This PDF document makes use of features which are only implemented in the ' .
                                    'commercial "FPDI PDF-Parser" add-on (see https://www.setasign.com/fpdi-pdf-' .
                                    'parser).',
                                    PdfParserException::IMPLEMENTED_IN_FPDI_PDF_PARSER
                                );
                            }

                            $colors = PdfDictionary::get($decodeParam, 'Colors', PdfNumeric::create(1));
                            $bitsPerComponent = PdfDictionary::get(
                                $decodeParam,
                                'BitsPerComponent',
                                PdfNumeric::create(8)
                            );

                            $columns = PdfDictionary::get($decodeParam, 'Columns', PdfNumeric::create(1));

                            $filterObject = new Predictor(
                                $predictor->value,
                                $colors->value,
                                $bitsPerComponent->value,
                                $columns->value
                            );

                            $stream = $filterObject->decode($stream);
                        }
                    }

                    break;
                case 'ASCII85Decode':
                case 'A85':
                    $filterObject = new Ascii85();
                    $stream = $filterObject->decode($stream);
                    break;

                case 'ASCIIHexDecode':
                case 'AHx':
                    $filterObject = new AsciiHex();
                    $stream = $filterObject->decode($stream);
                    break;

                case 'Crypt':
                    if (!$decodeParam instanceof PdfDictionary) {
                        break;
                    }
                    // Filter is "Identity"
                    $name = PdfDictionary::get($decodeParam, 'Name');
                    if (!$name instanceof PdfName || $name->value !== 'Identity') {
                        break;
                    }

                    throw new FilterException(
                        'Support for Crypt filters other than "Identity" is not implemented.',
                        FilterException::UNSUPPORTED_FILTER
                    );

                default:
                    throw new FilterException(
                        \sprintf('Unsupported filter "%s".', $filter->value),
                        FilterException::UNSUPPORTED_FILTER
                    );
            }
        }

        return $stream;
    }
}

// ==========================================
// File: PdfString.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Class representing a PDF string object
 */
class PdfString extends PdfType
{
    /**
     * Parses a string object from the stream reader.
     *
     * @param StreamReader $streamReader
     * @return self
     */
    public static function parse(StreamReader $streamReader)
    {
        $pos = $startPos = $streamReader->getOffset();
        $openBrackets = 1;
        do {
            $buffer = $streamReader->getBuffer(false);
            for ($length = \strlen($buffer); $openBrackets !== 0 && $pos < $length; $pos++) {
                switch ($buffer[$pos]) {
                    case '(':
                        $openBrackets++;
                        break;
                    case ')':
                        $openBrackets--;
                        break;
                    case '\\':
                        $pos++;
                }
            }
        } while ($openBrackets !== 0 && $streamReader->increaseLength());

        $result = \substr($buffer, $startPos, $openBrackets + $pos - $startPos - 1);
        $streamReader->setOffset($pos);

        $v = new self();
        $v->value = $result;

        return $v;
    }

    /**
     * Helper method to create an instance.
     *
     * @param string $value The string needs to be escaped accordingly.
     * @return self
     */
    public static function create($value)
    {
        $v = new self();
        $v->value = $value;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfString instance.
     *
     * @param mixed $string
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($string)
    {
        return PdfType::ensureType(self::class, $string, 'String value expected.');
    }

    /**
     * Escapes sequences in a string according to the PDF specification.
     *
     * @param string $s
     * @return string
     */
    public static function escape($s)
    {
        // Still a bit faster, than direct replacing
        if (
            \strpos($s, '\\') !== false ||
            \strpos($s, ')')  !== false ||
            \strpos($s, '(')  !== false ||
            \strpos($s, "\x0D") !== false ||
            \strpos($s, "\x0A") !== false ||
            \strpos($s, "\x09") !== false ||
            \strpos($s, "\x08") !== false ||
            \strpos($s, "\x0C") !== false
        ) {
            // is faster than strtr(...)
            return \str_replace(
                ['\\',   ')',   '(',   "\x0D", "\x0A", "\x09", "\x08", "\x0C"],
                ['\\\\', '\\)', '\\(', '\r',   '\n',   '\t',   '\b',   '\f'],
                $s
            );
        }

        return $s;
    }

    /**
     * Unescapes escaped sequences in a PDF string according to the PDF specification.
     *
     * @param string $s
     * @return string
     */
    public static function unescape($s)
    {
        $out = '';
        /** @noinspection ForeachInvariantsInspection */
        for ($count = 0, $n = \strlen($s); $count < $n; $count++) {
            if ($s[$count] !== '\\') {
                $out .= $s[$count];
            } else {
                // A backslash at the end of the string - ignore it
                if ($count === ($n - 1)) {
                    break;
                }

                switch ($s[++$count]) {
                    case ')':
                    case '(':
                    case '\\':
                        $out .= $s[$count];
                        break;

                    case 'f':
                        $out .= "\x0C";
                        break;

                    case 'b':
                        $out .= "\x08";
                        break;

                    case 't':
                        $out .= "\x09";
                        break;

                    case 'r':
                        $out .= "\x0D";
                        break;

                    case 'n':
                        $out .= "\x0A";
                        break;

                    case "\r":
                        if ($count !== $n - 1 && $s[$count + 1] === "\n") {
                            $count++;
                        }
                        break;

                    case "\n":
                        break;

                    default:
                        $actualChar = \ord($s[$count]);
                        // ascii 48 = number 0
                        // ascii 57 = number 9
                        if ($actualChar >= 48 && $actualChar <= 57) {
                            $oct = '' . $s[$count];

                            /** @noinspection NotOptimalIfConditionsInspection */
                            if (
                                $count + 1 < $n
                                && \ord($s[$count + 1]) >= 48
                                && \ord($s[$count + 1]) <= 57
                            ) {
                                $count++;
                                $oct .= $s[$count];

                                /** @noinspection NotOptimalIfConditionsInspection */
                                if (
                                    $count + 1 < $n
                                    && \ord($s[$count + 1]) >= 48
                                    && \ord($s[$count + 1]) <= 57
                                ) {
                                    $oct .= $s[++$count];
                                }
                            }

                            $out .= \chr(\octdec($oct));
                        } else {
                            // If the character is not one of those defined, the backslash is ignored
                            $out .= $s[$count];
                        }
                }
            }
        }
        return $out;
    }
}

// ==========================================
// File: PdfToken.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Type;

/**
 * Class representing PDF token object
 */
class PdfToken extends PdfType
{
    /**
     * Helper method to create an instance.
     *
     * @param string $token
     * @return self
     */
    public static function create($token)
    {
        $v = new self();
        $v->value = $token;

        return $v;
    }

    /**
     * Ensures that the passed value is a PdfToken instance.
     *
     * @param mixed $token
     * @return self
     * @throws PdfTypeException
     */
    public static function ensure($token)
    {
        return PdfType::ensureType(self::class, $token, 'Token value expected.');
    }
}

// ==========================================
// File: Matrix.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\Math;

/**
 * A simple 2D-Matrix class
 */
class Matrix
{
    /**
     * @var float
     */
    protected $a;

    /**
     * @var float
     */
    protected $b;

    /**
     * @var float
     */
    protected $c;

    /**
     * @var float
     */
    protected $d;

    /**
     * @var float
     */
    protected $e;

    /**
     * @var float
     */
    protected $f;

    /**
     * @param int|float $a
     * @param int|float $b
     * @param int|float $c
     * @param int|float $d
     * @param int|float $e
     * @param int|float $f
     */
    public function __construct($a = 1, $b = 0, $c = 0, $d = 1, $e = 0, $f = 0)
    {
        $this->a = (float)$a;
        $this->b = (float)$b;
        $this->c = (float)$c;
        $this->d = (float)$d;
        $this->e = (float)$e;
        $this->f = (float)$f;
    }

    /**
     * @return float[]
     */
    public function getValues()
    {
        return [$this->a, $this->b, $this->c, $this->d, $this->e, $this->f];
    }

    /**
     * @param Matrix $by
     * @return Matrix
     */
    public function multiply(self $by)
    {
        $a =
            $this->a * $by->a
            + $this->b * $by->c
            //+ 0 * $by->e
        ;

        $b =
            $this->a * $by->b
            + $this->b * $by->d
            //+ 0 * $by->f
        ;

        $c =
            $this->c * $by->a
            + $this->d * $by->c
            //+ 0 * $by->e
        ;

        $d =
            $this->c * $by->b
            + $this->d * $by->d
            //+ 0 * $by->f
        ;

        $e =
            $this->e * $by->a
            + $this->f * $by->c
            + /*1 * */$by->e;

        $f =
            $this->e * $by->b
            + $this->f * $by->d
            + /*1 * */$by->f;

        return new self($a, $b, $c, $d, $e, $f);
    }
}

// ==========================================
// File: Vector.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\Math;

/**
 * A simple 2D-Vector class
 */
class Vector
{
    /**
     * @var float
     */
    protected $x;

    /**
     * @var float
     */
    protected $y;

    /**
     * @param int|float $x
     * @param int|float $y
     */
    public function __construct($x = .0, $y = .0)
    {
        $this->x = (float)$x;
        $this->y = (float)$y;
    }

    /**
     * @return float
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param Matrix $matrix
     * @return Vector
     */
    public function multiplyWithMatrix(Matrix $matrix)
    {
        [$a, $b, $c, $d, $e, $f] = $matrix->getValues();
        $x = $a * $this->x + $c * $this->y + $e;
        $y = $b * $this->x + $d * $this->y + $f;

        return new self($x, $y);
    }
}

// ==========================================
// File: Rectangle.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfReader\DataStructure;

use setasign\Fpdi\Math\Vector;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * Class representing a rectangle
 */
class Rectangle
{
    /**
     * @var int|float
     */
    protected $llx;

    /**
     * @var int|float
     */
    protected $lly;

    /**
     * @var int|float
     */
    protected $urx;

    /**
     * @var int|float
     */
    protected $ury;

    /**
     * Create a rectangle instance by a PdfArray.
     *
     * @param PdfArray|mixed $array
     * @param PdfParser $parser
     * @return Rectangle
     * @throws PdfTypeException
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public static function byPdfArray($array, PdfParser $parser)
    {
        $array = PdfArray::ensure(PdfType::resolve($array, $parser), 4)->value;
        $ax = PdfNumeric::ensure(PdfType::resolve($array[0], $parser))->value;
        $ay = PdfNumeric::ensure(PdfType::resolve($array[1], $parser))->value;
        $bx = PdfNumeric::ensure(PdfType::resolve($array[2], $parser))->value;
        $by = PdfNumeric::ensure(PdfType::resolve($array[3], $parser))->value;

        return new self($ax, $ay, $bx, $by);
    }

    public static function byVectors(Vector $ll, Vector $ur)
    {
        return new self($ll->getX(), $ll->getY(), $ur->getX(), $ur->getY());
    }

    /**
     * Rectangle constructor.
     *
     * @param float|int $ax
     * @param float|int $ay
     * @param float|int $bx
     * @param float|int $by
     */
    public function __construct($ax, $ay, $bx, $by)
    {
        $this->llx = \min($ax, $bx);
        $this->lly = \min($ay, $by);
        $this->urx = \max($ax, $bx);
        $this->ury = \max($ay, $by);
    }

    /**
     * Get the width of the rectangle.
     *
     * @return float|int
     */
    public function getWidth()
    {
        return $this->urx - $this->llx;
    }

    /**
     * Get the height of the rectangle.
     *
     * @return float|int
     */
    public function getHeight()
    {
        return $this->ury - $this->lly;
    }

    /**
     * Get the lower left abscissa.
     *
     * @return float|int
     */
    public function getLlx()
    {
        return $this->llx;
    }

    /**
     * Get the lower left ordinate.
     *
     * @return float|int
     */
    public function getLly()
    {
        return $this->lly;
    }

    /**
     * Get the upper right abscissa.
     *
     * @return float|int
     */
    public function getUrx()
    {
        return $this->urx;
    }

    /**
     * Get the upper right ordinate.
     *
     * @return float|int
     */
    public function getUry()
    {
        return $this->ury;
    }

    /**
     * Get the rectangle as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            $this->llx,
            $this->lly,
            $this->urx,
            $this->ury
        ];
    }

    /**
     * Get the rectangle as a PdfArray.
     *
     * @return PdfArray
     */
    public function toPdfArray()
    {
        $array = new PdfArray();
        $array->value[] = PdfNumeric::create($this->llx);
        $array->value[] = PdfNumeric::create($this->lly);
        $array->value[] = PdfNumeric::create($this->urx);
        $array->value[] = PdfNumeric::create($this->ury);

        return $array;
    }
}

// ==========================================
// File: StreamReader.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser;

/**
 * A stream reader class
 */
class StreamReader
{
    /**
     * Creates a stream reader instance by a string value.
     *
     * @param string $content
     * @param int $maxMemory
     * @return StreamReader
     */
    public static function createByString($content, $maxMemory = 2097152)
    {
        $h = \fopen('php://temp/maxmemory:' . ((int) $maxMemory), 'r+b');
        \fwrite($h, $content);
        \rewind($h);

        return new self($h, true);
    }

    /**
     * Creates a stream reader instance by a filename.
     *
     * @param string $filename
     * @return StreamReader
     */
    public static function createByFile($filename)
    {
        $h = \fopen($filename, 'rb');
        return new self($h, true);
    }

    /**
     * Defines whether the stream should be closed when the stream reader instance is deconstructed or not.
     *
     * @var bool
     */
    protected $closeStream;

    /**
     * The stream resource.
     *
     * @var resource
     */
    protected $stream;

    /**
     * The byte-offset position in the stream.
     *
     * @var int
     */
    protected $position;

    /**
     * The byte-offset position in the buffer.
     *
     * @var int
     */
    protected $offset;

    /**
     * The buffer length.
     *
     * @var int
     */
    protected $bufferLength;

    /**
     * The total length of the stream.
     *
     * @var int
     */
    protected $totalLength;

    /**
     * The buffer.
     *
     * @var string
     */
    protected $buffer;

    /**
     * StreamReader constructor.
     *
     * @param resource $stream
     * @param bool $closeStream Defines whether to close the stream resource if the instance is destructed or not.
     */
    public function __construct($stream, $closeStream = false)
    {
        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException(
                'No stream given.'
            );
        }

        $metaData = \stream_get_meta_data($stream);
        if (!$metaData['seekable']) {
            throw new \InvalidArgumentException(
                'Given stream is not seekable!'
            );
        }

        if (fseek($stream, 0) === -1) {
            throw new \InvalidArgumentException(
                'Given stream is not seekable!'
            );
        }

        $this->stream = $stream;
        $this->closeStream = $closeStream;
        $this->reset();
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * Closes the file handle.
     */
    public function cleanUp()
    {
        if ($this->closeStream && is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }

    /**
     * Returns the byte length of the buffer.
     *
     * @param bool $atOffset
     * @return int
     */
    public function getBufferLength($atOffset = false)
    {
        if ($atOffset === false) {
            return $this->bufferLength;
        }

        return $this->bufferLength - $this->offset;
    }

    /**
     * Get the current position in the stream.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Returns the current buffer.
     *
     * @param bool $atOffset
     * @return string
     */
    public function getBuffer($atOffset = true)
    {
        if ($atOffset === false) {
            return $this->buffer;
        }

        $string = \substr($this->buffer, $this->offset);

        return (string) $string;
    }

    /**
     * Gets a byte at a specific position in the buffer.
     *
     * If the position is invalid the method will return false.
     *
     * If the $position parameter is set to null the value of $this->offset will be used.
     *
     * @param int|null $position
     * @return string|bool
     */
    public function getByte($position = null)
    {
        $position = (int) ($position !== null ? $position : $this->offset);
        if (
            $position >= $this->bufferLength
            && (!$this->increaseLength() || $position >= $this->bufferLength)
        ) {
            return false;
        }

        return $this->buffer[$position];
    }

    /**
     * Returns a byte at a specific position, and set the offset to the next byte position.
     *
     * If the position is invalid the method will return false.
     *
     * If the $position parameter is set to null the value of $this->offset will be used.
     *
     * @param int|null $position
     * @return string|bool
     */
    public function readByte($position = null)
    {
        if ($position !== null) {
            $position = (int) $position;
            // check if needed bytes are available in the current buffer
            if (!($position >= $this->position && $position < $this->position + $this->bufferLength)) {
                $this->reset($position);
                $offset = $this->offset;
            } else {
                $offset = $position - $this->position;
            }
        } else {
            $offset = $this->offset;
        }

        if (
            $offset >= $this->bufferLength
            && ((!$this->increaseLength()) || $offset >= $this->bufferLength)
        ) {
            return false;
        }

        $this->offset = $offset + 1;
        return $this->buffer[$offset];
    }

    /**
     * Read bytes from the current or a specific offset position and set the internal pointer to the next byte.
     *
     * If the position is invalid the method will return false.
     *
     * If the $position parameter is set to null the value of $this->offset will be used.
     *
     * @param int $length
     * @param int|null $position
     * @return string|false
     */
    public function readBytes($length, $position = null)
    {
        $length = (int) $length;
        if ($position !== null) {
            // check if needed bytes are available in the current buffer
            if (!($position >= $this->position && $position < $this->position + $this->bufferLength)) {
                $this->reset($position, $length);
                $offset = $this->offset;
            } else {
                $offset = $position - $this->position;
            }
        } else {
            $offset = $this->offset;
        }

        if (
            ($offset + $length) > $this->bufferLength
            && ((!$this->increaseLength($length)) || ($offset + $length) > $this->bufferLength)
        ) {
            return false;
        }

        $bytes = \substr($this->buffer, $offset, $length);
        $this->offset = $offset + $length;

        return $bytes;
    }

    /**
     * Read a line from the current position.
     *
     * @param int $length
     * @return string|bool
     */
    public function readLine($length = 1024)
    {
        if ($this->ensureContent() === false) {
            return false;
        }

        $line = '';
        while ($this->ensureContent()) {
            $char = $this->readByte();

            if ($char === "\n") {
                break;
            }

            if ($char === "\r") {
                if ($this->getByte() === "\n") {
                    $this->addOffset(1);
                }
                break;
            }

            $line .= $char;

            if (\strlen($line) >= $length) {
                break;
            }
        }

        return $line;
    }

    /**
     * Set the offset position in the current buffer.
     *
     * @param int $offset
     */
    public function setOffset($offset)
    {
        if ($offset > $this->bufferLength || $offset < 0) {
            throw new \OutOfRangeException(
                \sprintf('Offset (%s) out of range (length: %s)', $offset, $this->bufferLength)
            );
        }

        $this->offset = (int) $offset;
    }

    /**
     * Returns the current offset in the current buffer.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Add an offset to the current offset.
     *
     * @param int $offset
     */
    public function addOffset($offset)
    {
        $this->setOffset($this->offset + $offset);
    }

    /**
     * Make sure that there is at least one character beyond the current offset in the buffer.
     *
     * @return bool
     */
    public function ensureContent()
    {
        while ($this->offset >= $this->bufferLength) {
            if (!$this->increaseLength()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the stream.
     *
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Gets the total available length.
     *
     * @return int
     */
    public function getTotalLength()
    {
        if ($this->totalLength === null) {
            $stat = \fstat($this->stream);
            $this->totalLength = $stat['size'];
        }

        return $this->totalLength;
    }

    /**
     * Resets the buffer to a position and re-read the buffer with the given length.
     *
     * If the $pos parameter is negative the start buffer position will be the $pos'th position from
     * the end of the file.
     *
     * If the $pos parameter is negative and the absolute value is bigger then the totalLength of
     * the file $pos will set to zero.
     *
     * @param int|null $pos Start position of the new buffer
     * @param int $length Length of the new buffer. Mustn't be negative
     */
    public function reset($pos = 0, $length = 200)
    {
        if ($pos === null) {
            $pos = $this->position + $this->offset;
        } elseif ($pos < 0) {
            $pos = \max(0, $this->getTotalLength() + $pos);
        }

        \fseek($this->stream, $pos);

        $this->position = $pos;
        $this->offset = 0;
        if ($length > 0) {
            $this->buffer = (string) \fread($this->stream, $length);
        } else {
            $this->buffer = '';
        }
        $this->bufferLength = \strlen($this->buffer);

        // If a stream wrapper is in use it is possible that
        // length values > 8096 will be ignored, so use the
        // increaseLength()-method to correct that behavior
        if ($this->bufferLength < $length && $this->increaseLength($length - $this->bufferLength)) {
            // increaseLength parameter is $minLength, so cut to have only the required bytes in the buffer
            $this->buffer = (string) \substr($this->buffer, 0, $length);
            $this->bufferLength = \strlen($this->buffer);
        }
    }

    /**
     * Ensures bytes in the buffer with a specific length and location in the file.
     *
     * @param int $pos
     * @param int $length
     * @see reset()
     */
    public function ensure($pos, $length)
    {
        if (
            $pos >= $this->position
            && $pos < ($this->position + $this->bufferLength)
            && ($this->position + $this->bufferLength) >= ($pos + $length)
        ) {
            $this->offset = $pos - $this->position;
        } else {
            $this->reset($pos, $length);
        }
    }

    /**
     * Forcefully read more data into the buffer.
     *
     * @param int $minLength
     * @return bool Returns false if the stream reaches the end
     */
    public function increaseLength($minLength = 100)
    {
        $length = \max($minLength, 100);

        if (\feof($this->stream) || $this->getTotalLength() === $this->position + $this->bufferLength) {
            return false;
        }

        $newLength = $this->bufferLength + $length;
        do {
            $this->buffer .= \fread($this->stream, $newLength - $this->bufferLength);
            $this->bufferLength = \strlen($this->buffer);
        } while (($this->bufferLength !== $newLength) && !\feof($this->stream));

        return true;
    }
}

// ==========================================
// File: Tokenizer.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser;

/**
 * A tokenizer class.
 */
class Tokenizer
{
    /**
     * @var StreamReader
     */
    protected $streamReader;

    /**
     * A token stack.
     *
     * @var string[]
     */
    protected $stack = [];

    /**
     * Tokenizer constructor.
     *
     * @param StreamReader $streamReader
     */
    public function __construct(StreamReader $streamReader)
    {
        $this->streamReader = $streamReader;
    }

    /**
     * Get the stream reader instance.
     *
     * @return StreamReader
     */
    public function getStreamReader()
    {
        return $this->streamReader;
    }

    /**
     * Clear the token stack.
     */
    public function clearStack()
    {
        $this->stack = [];
    }

    /**
     * Push a token onto the stack.
     *
     * @param string $token
     */
    public function pushStack($token)
    {
        $this->stack[] = $token;
    }

    /**
     * Get next token.
     *
     * @return false|string
     */
    public function getNextToken()
    {
        $token = \array_pop($this->stack);
        if ($token !== null) {
            return $token;
        }

        if (($byte = $this->streamReader->readByte()) === false) {
            return false;
        }

        if (\in_array($byte, ["\x20", "\x0A", "\x0D", "\x0C", "\x09", "\x00"], true)) {
            if ($this->leapWhiteSpaces() === false) {
                return false;
            }
            $byte = $this->streamReader->readByte();
        }

        switch ($byte) {
            case '/':
            case '[':
            case ']':
            case '(':
            case ')':
            case '{':
            case '}':
            case '<':
            case '>':
                return $byte;
            case '%':
                $this->streamReader->readLine();
                return $this->getNextToken();
        }

        /* This way is faster than checking single bytes.
         */
        $bufferOffset = $this->streamReader->getOffset();
        do {
            $lastBuffer = $this->streamReader->getBuffer(false);
            $pos = \strcspn(
                $lastBuffer,
                "\x00\x09\x0A\x0C\x0D\x20()<>[]{}/%",
                $bufferOffset
            );
        } while (
            // Break the loop if a delimiter or white space char is matched
            // in the current buffer or increase the buffers length
            $bufferOffset + $pos === \strlen($lastBuffer)
            && $this->streamReader->increaseLength()
        );

        $result = \substr($lastBuffer, $bufferOffset - 1, $pos + 1);
        $this->streamReader->setOffset($bufferOffset + $pos);

        return $result;
    }

    /**
     * Leap white spaces.
     *
     * @return boolean
     */
    public function leapWhiteSpaces()
    {
        do {
            if (!$this->streamReader->ensureContent()) {
                return false;
            }

            $buffer = $this->streamReader->getBuffer(false);
            $matches = \strspn($buffer, "\x20\x0A\x0C\x0D\x09\x00", $this->streamReader->getOffset());
            if ($matches > 0) {
                $this->streamReader->addOffset($matches);
            }
        } while ($this->streamReader->getOffset() >= $this->streamReader->getBufferLength());

        return true;
    }
}

// ==========================================
// File: AbstractReader.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfToken;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * Abstract class for cross-reference reader classes.
 */
abstract class AbstractReader
{
    /**
     * @var PdfParser
     */
    protected $parser;

    /**
     * @var PdfDictionary
     */
    protected $trailer;

    /**
     * AbstractReader constructor.
     *
     * @param PdfParser $parser
     * @throws CrossReferenceException
     */
    public function __construct(PdfParser $parser)
    {
        $this->parser = $parser;
        $this->readTrailer();
    }

    /**
     * Get the trailer dictionary.
     *
     * @return PdfDictionary
     */
    public function getTrailer()
    {
        return $this->trailer;
    }

    /**
     * Read the trailer dictionary.
     *
     * @throws CrossReferenceException
     */
    protected function readTrailer()
    {
        try {
            $trailerKeyword = $this->parser->readValue(null, PdfToken::class);
            if ($trailerKeyword->value !== 'trailer') {
                throw new CrossReferenceException(
                    \sprintf(
                        'Unexpected end of cross reference. "trailer"-keyword expected, got: %s.',
                        $trailerKeyword->value
                    ),
                    CrossReferenceException::UNEXPECTED_END
                );
            }
        } catch (PdfTypeException $e) {
            throw new CrossReferenceException(
                'Unexpected end of cross reference. "trailer"-keyword expected, got an invalid object type.',
                CrossReferenceException::UNEXPECTED_END,
                $e
            );
        }

        try {
            $trailer = $this->parser->readValue(null, PdfDictionary::class);
        } catch (PdfTypeException $e) {
            throw new CrossReferenceException(
                'Unexpected end of cross reference. Trailer not found.',
                CrossReferenceException::UNEXPECTED_END,
                $e
            );
        }

        $this->trailer = $trailer;
    }
}

// ==========================================
// File: FixedReader.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Class FixedReader
 *
 * This reader allows a very less overhead parsing of single entries of the cross-reference, because the main entries
 * are only read when needed and not in a single run.
 */
class FixedReader extends AbstractReader implements ReaderInterface
{
    /**
     * @var StreamReader
     */
    protected $reader;

    /**
     * Data of subsections.
     *
     * @var array
     */
    protected $subSections;

    /**
     * FixedReader constructor.
     *
     * @param PdfParser $parser
     * @throws CrossReferenceException
     */
    public function __construct(PdfParser $parser)
    {
        $this->reader = $parser->getStreamReader();
        $this->read();
        parent::__construct($parser);
    }

    /**
     * Get all subsection data.
     *
     * @return array
     */
    public function getSubSections()
    {
        return $this->subSections;
    }

    /**
     * @inheritdoc
     * @return int|false
     */
    public function getOffsetFor($objectNumber)
    {
        foreach ($this->subSections as $offset => [$startObject, $objectCount]) {
            /**
             * @var int $startObject
             * @var int $objectCount
             */
            if ($objectNumber >= $startObject && $objectNumber < ($startObject + $objectCount)) {
                $position = $offset + 20 * ($objectNumber - $startObject);
                $this->reader->ensure($position, 20);
                $line = $this->reader->readBytes(20);
                if ($line === false || $line[17] === 'f') {
                    return false;
                }

                return (int) \substr($line, 0, 10);
            }
        }

        return false;
    }

    /**
     * Read the cross-reference.
     *
     * This reader will only read the subsections in this method. The offsets were resolved individually by this
     * information.
     *
     * @throws CrossReferenceException
     */
    protected function read()
    {
        $subSections = [];

        $startObject = $entryCount = $lastLineStart = null;
        $validityChecked = false;
        while (($line = $this->reader->readLine(20)) !== false) {
            if (\strpos($line, 'trailer') !== false) {
                $this->reader->reset($lastLineStart);
                break;
            }

            // jump over if line content doesn't match the expected string
            if (\sscanf($line, '%d %d', $startObject, $entryCount) !== 2) {
                continue;
            }

            $oldPosition = $this->reader->getPosition();
            $position = $oldPosition + $this->reader->getOffset();

            if (!$validityChecked && $entryCount > 0) {
                $nextLine = $this->reader->readBytes(21);
                /* Check the next line for maximum of 20 bytes and not longer
                 * By catching 21 bytes and trimming the length should be still 21.
                 */
                if (\strlen(\trim($nextLine)) !== 21) {
                    throw new CrossReferenceException(
                        'Cross-reference entries are larger than 20 bytes.',
                        CrossReferenceException::ENTRIES_TOO_LARGE
                    );
                }

                /* Check for less than 20 bytes: cut the line to 20 bytes and trim; have to result in exactly 18 bytes.
                 * If it would have less bytes the substring would get the first bytes of the next line which would
                 * evaluate to a 20 bytes long string after trimming.
                 */
                if (\strlen(\trim(\substr($nextLine, 0, 20))) !== 18) {
                    throw new CrossReferenceException(
                        'Cross-reference entries are less than 20 bytes.',
                        CrossReferenceException::ENTRIES_TOO_SHORT
                    );
                }

                $validityChecked = true;
            }

            $subSections[$position] = [$startObject, $entryCount];

            $lastLineStart = $position + $entryCount * 20;
            $this->reader->reset($lastLineStart);
        }

        // reset after the last correct parsed line
        $this->reader->reset($lastLineStart);

        if (\count($subSections) === 0) {
            throw new CrossReferenceException(
                'No entries found in cross-reference.',
                CrossReferenceException::NO_ENTRIES
            );
        }

        $this->subSections = $subSections;
    }

    /**
     * Fixes an invalid object number shift.
     *
     * This method can be used to repair documents with an invalid subsection header:
     *
     * <code>
     * xref
     * 1 7
     * 0000000000 65535 f
     * 0000000009 00000 n
     * 0000412075 00000 n
     * 0000412172 00000 n
     * 0000412359 00000 n
     * 0000412417 00000 n
     * 0000412468 00000 n
     * </code>
     *
     * It shall only be called on the first table.
     *
     * @return bool
     */
    public function fixFaultySubSectionShift()
    {
        $subSections = $this->getSubSections();
        if (\count($subSections) > 1) {
            return false;
        }

        $subSection = \current($subSections);
        if ($subSection[0] != 1) {
            return false;
        }

        if ($this->getOffsetFor(1) === false) {
            foreach ($subSections as $offset => [$startObject, $objectCount]) {
                $this->subSections[$offset] = [$startObject - 1, $objectCount];
            }
            return true;
        }

        return false;
    }
}

// ==========================================
// File: LineReader.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Class LineReader
 *
 * This reader class read all cross-reference entries in a single run.
 * It supports reading cross-references with e.g. invalid data (e.g. entries with a length < or > 20 bytes).
 */
class LineReader extends AbstractReader implements ReaderInterface
{
    /**
     * The object offsets.
     *
     * @var array
     */
    protected $offsets;

    /**
     * LineReader constructor.
     *
     * @param PdfParser $parser
     * @throws CrossReferenceException
     */
    public function __construct(PdfParser $parser)
    {
        $this->read($this->extract($parser->getStreamReader()));
        parent::__construct($parser);
    }

    /**
     * @inheritdoc
     * @return int|false
     */
    public function getOffsetFor($objectNumber)
    {
        if (isset($this->offsets[$objectNumber])) {
            return $this->offsets[$objectNumber][0];
        }

        return false;
    }

    /**
     * Get all found offsets.
     *
     * @return array
     */
    public function getOffsets()
    {
        return $this->offsets;
    }

    /**
     * Extracts the cross reference data from the stream reader.
     *
     * @param StreamReader $reader
     * @return string
     * @throws CrossReferenceException
     */
    protected function extract(StreamReader $reader)
    {
        $bytesPerCycle = 100;
        $reader->reset(null, $bytesPerCycle);

        $cycles = 0;
        do {
            // 6 = length of "trailer" - 1
            $pos = \max(($bytesPerCycle * $cycles) - 6, 0);
            $trailerPos = \strpos($reader->getBuffer(false), 'trailer', $pos);
            $cycles++;
        } while ($trailerPos === false && $reader->increaseLength($bytesPerCycle) !== false);

        if ($trailerPos === false) {
            throw new CrossReferenceException(
                'Unexpected end of cross reference. "trailer"-keyword not found.',
                CrossReferenceException::NO_TRAILER_FOUND
            );
        }

        $xrefContent = \substr($reader->getBuffer(false), 0, $trailerPos);
        $reader->reset($reader->getPosition() + $trailerPos);

        return $xrefContent;
    }

    /**
     * Read the cross-reference entries.
     *
     * @param string $xrefContent
     * @throws CrossReferenceException
     */
    protected function read($xrefContent)
    {
        // get eol markers in the first 100 bytes
        \preg_match_all("/(\r\n|\n|\r)/", \substr($xrefContent, 0, 100), $m);

        if (\count($m[0]) === 0) {
            throw new CrossReferenceException(
                'No data found in cross-reference.',
                CrossReferenceException::INVALID_DATA
            );
        }

        // count(array_count_values()) is faster then count(array_unique())
        // @see https://github.com/symfony/symfony/pull/23731
        // can be reverted for php7.2
        $differentLineEndings = \count(\array_count_values($m[0]));
        if ($differentLineEndings > 1) {
            $lines = \preg_split("/(\r\n|\n|\r)/", $xrefContent, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $lines = \explode($m[0][0], $xrefContent);
        }

        unset($differentLineEndings, $m);
        if (!\is_array($lines)) {
            $this->offsets = [];
            return;
        }

        $start = 0;
        $offsets = [];

        // trim all lines and remove empty lines
        $lines = \array_filter(\array_map('\trim', $lines));
        foreach ($lines as $line) {
            $pieces = \explode(' ', $line);

            switch (\count($pieces)) {
                case 2:
                    $start = (int) $pieces[0];
                    break;

                case 3:
                    switch ($pieces[2]) {
                        case 'n':
                            $offsets[$start] = [(int) $pieces[0], (int) $pieces[1]];
                            $start++;
                            break 2;
                        case 'f':
                            $start++;
                            break 2;
                    }
                    // fall through if pieces doesn't match

                default:
                    throw new CrossReferenceException(
                        \sprintf('Unexpected data in xref table (%s)', \implode(' ', $pieces)),
                        CrossReferenceException::INVALID_DATA
                    );
            }
        }

        $this->offsets = $offsets;
    }
}

// ==========================================
// File: CrossReference.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\CrossReference;

use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfToken;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * Class CrossReference
 *
 * This class processes the standard cross reference of a PDF document.
 */
class CrossReference
{
    /**
     * The byte length in which the "startxref" keyword should be searched.
     *
     * @var int
     */
    public static $trailerSearchLength = 5500;

    /**
     * @var int
     */
    protected $fileHeaderOffset = 0;

    /**
     * @var PdfParser
     */
    protected $parser;

    /**
     * @var ReaderInterface[]
     */
    protected $readers = [];

    /**
     * CrossReference constructor.
     *
     * @param PdfParser $parser
     * @throws CrossReferenceException
     * @throws PdfTypeException
     */
    public function __construct(PdfParser $parser, $fileHeaderOffset = 0)
    {
        // clear the token stack, if the parser instance is re-used
        $parser->getTokenizer()->clearStack();

        $this->parser = $parser;
        $this->fileHeaderOffset = $fileHeaderOffset;

        $offset = $this->findStartXref();
        $reader = null;
        $offsets = [$offset];
        /** @noinspection TypeUnsafeComparisonInspection */
        while ($offset != false) { // By doing an unsafe comparsion we ignore faulty references to byte offset 0
            try {
                $reader = $this->readXref($offset + $this->fileHeaderOffset);
            } catch (CrossReferenceException $e) {
                // sometimes the file header offset is part of the byte offsets, so let's retry by resetting it to zero.
                if ($e->getCode() === CrossReferenceException::INVALID_DATA && $this->fileHeaderOffset !== 0) {
                    $this->fileHeaderOffset = 0;
                    $reader = $this->readXref($offset);
                } else {
                    throw $e;
                }
            }

            $trailer = $reader->getTrailer();
            $this->checkForEncryption($trailer);
            $this->readers[] = $reader;

            if (isset($trailer->value['Prev'])) {
                $offset = $trailer->value['Prev']->value;
                if (\in_array($offset, $offsets, true)) {
                    throw new CrossReferenceException(
                        'Cross-references includes cyclic structure.',
                        CrossReferenceException::CYCLIC_STRUCTURE
                    );
                }
                $offsets[] = $offset;
            } else {
                break;
            }
        }

        // fix faulty sub-section header
        if ($reader instanceof FixedReader) {
            /**
             * @var FixedReader $reader
             */
            $reader->fixFaultySubSectionShift();
        }

        if ($reader === null) {
            throw new CrossReferenceException('No cross-reference found.', CrossReferenceException::NO_XREF_FOUND);
        }
    }

    /**
     * Get the size of the cross reference.
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->getTrailer()->value['Size']->value;
    }

    /**
     * Get the trailer dictionary.
     *
     * @return PdfDictionary
     */
    public function getTrailer()
    {
        return $this->readers[0]->getTrailer();
    }

    /**
     * Get the cross reference readser instances.
     *
     * @return ReaderInterface[]
     */
    public function getReaders()
    {
        return $this->readers;
    }

    /**
     * Get the offset by an object number.
     *
     * @param int $objectNumber
     * @return integer|bool
     */
    public function getOffsetFor($objectNumber)
    {
        foreach ($this->getReaders() as $reader) {
            $offset = $reader->getOffsetFor($objectNumber);
            if ($offset !== false) {
                return $offset;
            }
        }

        return false;
    }

    /**
     * Get an indirect object by its object number.
     *
     * @param int $objectNumber
     * @return PdfIndirectObject
     * @throws CrossReferenceException
     */
    public function getIndirectObject($objectNumber)
    {
        $offset = $this->getOffsetFor($objectNumber);
        if ($offset === false) {
            throw new CrossReferenceException(
                \sprintf('Object (id:%s) not found.', $objectNumber),
                CrossReferenceException::OBJECT_NOT_FOUND
            );
        }

        $parser = $this->parser;

        $parser->getTokenizer()->clearStack();
        $parser->getStreamReader()->reset($offset + $this->fileHeaderOffset);

        try {
            /** @var PdfIndirectObject $object */
            $object = $parser->readValue(null, PdfIndirectObject::class);
        } catch (PdfTypeException $e) {
            throw new CrossReferenceException(
                \sprintf('Object (id:%s) not found at location (%s).', $objectNumber, $offset),
                CrossReferenceException::OBJECT_NOT_FOUND,
                $e
            );
        }

        if ($object->objectNumber !== $objectNumber) {
            throw new CrossReferenceException(
                \sprintf('Wrong object found, got %s while %s was expected.', $object->objectNumber, $objectNumber),
                CrossReferenceException::OBJECT_NOT_FOUND
            );
        }

        return $object;
    }

    /**
     * Read the cross-reference table at a given offset.
     *
     * Internally the method will try to evaluate the best reader for this cross-reference.
     *
     * @param int $offset
     * @return ReaderInterface
     * @throws CrossReferenceException
     * @throws PdfTypeException
     */
    protected function readXref($offset)
    {
        $this->parser->getStreamReader()->reset($offset);
        $this->parser->getTokenizer()->clearStack();
        $initValue = $this->parser->readValue();

        return $this->initReaderInstance($initValue);
    }

    /**
     * Get a cross-reference reader instance.
     *
     * @param PdfToken|PdfIndirectObject $initValue
     * @return ReaderInterface|bool
     * @throws CrossReferenceException
     * @throws PdfTypeException
     */
    protected function initReaderInstance($initValue)
    {
        $position = $this->parser->getStreamReader()->getPosition()
            + $this->parser->getStreamReader()->getOffset() + $this->fileHeaderOffset;

        if ($initValue instanceof PdfToken && $initValue->value === 'xref') {
            try {
                return new FixedReader($this->parser);
            } catch (CrossReferenceException $e) {
                $this->parser->getStreamReader()->reset($position);
                $this->parser->getTokenizer()->clearStack();

                return new LineReader($this->parser);
            }
        }

        if ($initValue instanceof PdfIndirectObject) {
            try {
                $stream = PdfStream::ensure($initValue->value);
            } catch (PdfTypeException $e) {
                throw new CrossReferenceException(
                    'Invalid object type at xref reference offset.',
                    CrossReferenceException::INVALID_DATA,
                    $e
                );
            }

            $type = PdfDictionary::get($stream->value, 'Type');
            if ($type->value !== 'XRef') {
                throw new CrossReferenceException(
                    'The xref position points to an incorrect object type.',
                    CrossReferenceException::INVALID_DATA
                );
            }

            $this->checkForEncryption($stream->value);

            throw new CrossReferenceException(
                'This PDF document probably uses a compression technique which is not supported by the ' .
                'free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)',
                CrossReferenceException::COMPRESSED_XREF
            );
        }

        throw new CrossReferenceException(
            'The xref position points to an incorrect object type.',
            CrossReferenceException::INVALID_DATA
        );
    }

    /**
     * Check for encryption.
     *
     * @param PdfDictionary $dictionary
     * @throws CrossReferenceException
     */
    protected function checkForEncryption(PdfDictionary $dictionary)
    {
        if (isset($dictionary->value['Encrypt'])) {
            throw new CrossReferenceException(
                'This PDF document is encrypted and cannot be processed with FPDI.',
                CrossReferenceException::ENCRYPTED
            );
        }
    }

    /**
     * Find the start position for the first cross-reference.
     *
     * @return int The byte-offset position of the first cross-reference.
     * @throws CrossReferenceException
     */
    protected function findStartXref()
    {
        $reader = $this->parser->getStreamReader();
        $reader->reset(-self::$trailerSearchLength, self::$trailerSearchLength);

        $buffer = $reader->getBuffer(false);
        $pos = \strrpos($buffer, 'startxref');
        $addOffset = 9;
        if ($pos === false) {
            // Some corrupted documents uses startref, instead of startxref
            $pos = \strrpos($buffer, 'startref');
            if ($pos === false) {
                throw new CrossReferenceException(
                    'Unable to find pointer to xref table',
                    CrossReferenceException::NO_STARTXREF_FOUND
                );
            }
            $addOffset = 8;
        }

        $reader->setOffset($pos + $addOffset);

        try {
            $value = $this->parser->readValue(null, PdfNumeric::class);
        } catch (PdfTypeException $e) {
            throw new CrossReferenceException(
                'Invalid data after startxref keyword.',
                CrossReferenceException::INVALID_DATA,
                $e
            );
        }

        return $value->value;
    }
}

// ==========================================
// File: Ascii85.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Class for handling ASCII base-85 encoded data
 */
class Ascii85 implements FilterInterface
{
    /**
     * Decode ASCII85 encoded string.
     *
     * @param string $data The input string
     * @return string
     * @throws Ascii85Exception
     */
    public function decode($data)
    {
        $out = '';
        $state = 0;
        $chn = null;

        $data = \preg_replace('/\s/', '', $data);

        $l = \strlen($data);

        /** @noinspection ForeachInvariantsInspection */
        for ($k = 0; $k < $l; ++$k) {
            $ch = \ord($data[$k]) & 0xff;

            //Start <~
            if ($k === 0 && $ch === 60 && isset($data[$k + 1]) && (\ord($data[$k + 1]) & 0xFF) === 126) {
                $k++;
                continue;
            }
            //End ~>
            if ($ch === 126 && isset($data[$k + 1]) && (\ord($data[$k + 1]) & 0xFF) === 62) {
                break;
            }

            if ($ch === 122 /* z */ && $state === 0) {
                $out .= "\x00\x00\x00\x00";
                continue;
            }

            if ($ch < 33 /* ! */ || $ch > 117 /* u */) {
                throw new Ascii85Exception(
                    'Illegal character found while ASCII85 decode.',
                    Ascii85Exception::ILLEGAL_CHAR_FOUND
                );
            }

            $chn[$state] = $ch - 33;/* ! */
            $state++;

            if ($state === 5) {
                $state = 0;
                $r = 0;
                for ($j = 0; $j < 5; ++$j) {
                    /** @noinspection UnnecessaryCastingInspection */
                    $r = (int)($r * 85 + $chn[$j]);
                }

                $out .= \chr(($r >> 24) & 255)
                    . \chr(($r >> 16) & 255)
                    . \chr(($r >> 8) & 255)
                    . \chr($r & 255);
            }
        }

        if ($state === 1) {
            throw new Ascii85Exception(
                'Illegal length while ASCII85 decode.',
                Ascii85Exception::ILLEGAL_LENGTH
            );
        }

        if ($state === 2) {
            $r = $chn[0] * 85 * 85 * 85 * 85 + ($chn[1] + 1) * 85 * 85 * 85;
            $out .= \chr(($r >> 24) & 255);
        } elseif ($state === 3) {
            $r = $chn[0] * 85 * 85 * 85 * 85 + $chn[1] * 85 * 85 * 85 + ($chn[2] + 1) * 85 * 85;
            $out .= \chr(($r >> 24) & 255);
            $out .= \chr(($r >> 16) & 255);
        } elseif ($state === 4) {
            $r = $chn[0] * 85 * 85 * 85 * 85 + $chn[1] * 85 * 85 * 85 + $chn[2] * 85 * 85 + ($chn[3] + 1) * 85;
            $out .= \chr(($r >> 24) & 255);
            $out .= \chr(($r >> 16) & 255);
            $out .= \chr(($r >> 8) & 255);
        }

        return $out;
    }
}

// ==========================================
// File: AsciiHex.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Class for handling ASCII hexadecimal encoded data
 */
class AsciiHex implements FilterInterface
{
    /**
     * Converts an ASCII hexadecimal encoded string into its binary representation.
     *
     * @param string $data The input string
     * @return string
     */
    public function decode($data)
    {
        $data = \preg_replace('/[^0-9A-Fa-f]/', '', \rtrim($data, '>'));
        if ((\strlen($data) % 2) === 1) {
            $data .= '0';
        }

        return \pack('H*', $data);
    }

    /**
     * Converts a string into ASCII hexadecimal representation.
     *
     * @param string $data The input string
     * @param boolean $leaveEOD
     * @return string
     */
    public function encode($data, $leaveEOD = false)
    {
        $t = \unpack('H*', $data);
        return \current($t)
            . ($leaveEOD ? '' : '>');
    }
}

// ==========================================
// File: Flate.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Class for handling zlib/deflate encoded data
 */
class Flate implements FilterInterface
{
    /**
     * Checks whether the zlib extension is loaded.
     *
     * Used for testing purpose.
     *
     * @return boolean
     * @internal
     */
    protected function extensionLoaded()
    {
        return \extension_loaded('zlib');
    }

    /**
     * Decodes a flate compressed string.
     *
     * @param string|false $data The input string
     * @return string
     * @throws FlateException
     */
    public function decode($data)
    {
        if ($this->extensionLoaded()) {
            $oData = $data;
            $data = (($data !== '') ? @\gzuncompress($data) : '');
            if ($data === false) {
                // let's try if the checksum is CRC32
                $fh = fopen('php://temp', 'w+b');
                fwrite($fh, "\x1f\x8b\x08\x00\x00\x00\x00\x00" . $oData);
                // "window" == 31 -> 16 + (8 to 15): Uses the low 4 bits of the value as the window size logarithm.
                //                   The input must include a gzip header and trailer (via 16).
                stream_filter_append($fh, 'zlib.inflate', STREAM_FILTER_READ, ['window' => 31]);
                fseek($fh, 0);
                $data = @stream_get_contents($fh);
                fclose($fh);

                if ($data) {
                    return $data;
                }

                // Try this fallback (remove the zlib stream header)
                $data = @(gzinflate(substr($oData, 2)));

                if ($data === false) {
                    throw new FlateException(
                        'Error while decompressing stream.',
                        FlateException::DECOMPRESS_ERROR
                    );
                }
            }
        } else {
            throw new FlateException(
                'To handle FlateDecode filter, enable zlib support in PHP.',
                FlateException::NO_ZLIB
            );
        }

        return $data;
    }
}

// ==========================================
// File: Lzw.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser\Filter;

/**
 * Class for handling LZW encoded data
 */
class Lzw implements FilterInterface
{
    /**
     * @var null|string
     */
    protected $data;

    /**
     * @var array
     */
    protected $sTable = [];

    /**
     * @var int
     */
    protected $dataLength = 0;

    /**
     * @var int
     */
    protected $tIdx;

    /**
     * @var int
     */
    protected $bitsToGet = 9;

    /**
     * @var int
     */
    protected $bytePointer;

    /**
     * @var int
     */
    protected $nextData = 0;

    /**
     * @var int
     */
    protected $nextBits = 0;

    /**
     * @var array
     */
    protected $andTable = [511, 1023, 2047, 4095];

    /**
     * Method to decode LZW compressed data.
     *
     * @param string $data The compressed data
     * @return string The uncompressed data
     * @throws LzwException
     */
    public function decode($data)
    {
        if ($data[0] === "\x00" && $data[1] === "\x01") {
            throw new LzwException(
                'LZW flavour not supported.',
                LzwException::LZW_FLAVOUR_NOT_SUPPORTED
            );
        }

        $this->initsTable();

        $this->data = $data;
        $this->dataLength = \strlen($data);

        // Initialize pointers
        $this->bytePointer = 0;

        $this->nextData = 0;
        $this->nextBits = 0;

        $prevCode = 0;

        $uncompData = '';

        while (($code = $this->getNextCode()) !== 257) {
            if ($code === 256) {
                $this->initsTable();
            } elseif ($prevCode === 256) {
                $uncompData .= $this->sTable[$code];
            } elseif ($code < $this->tIdx) {
                $string = $this->sTable[$code];
                $uncompData .= $string;

                $this->addStringToTable($this->sTable[$prevCode], $string[0]);
            } else {
                $string = $this->sTable[$prevCode];
                $string .= $string[0];
                $uncompData .= $string;

                $this->addStringToTable($string);
            }
            $prevCode = $code;
        }

        return $uncompData;
    }

    /**
     * Initialize the string table.
     */
    protected function initsTable()
    {
        $this->sTable = [];

        for ($i = 0; $i < 256; $i++) {
            $this->sTable[$i] = \chr($i);
        }

        $this->tIdx = 258;
        $this->bitsToGet = 9;
    }

    /**
     * Add a new string to the string table.
     *
     * @param string $oldString
     * @param string $newString
     */
    protected function addStringToTable($oldString, $newString = '')
    {
        $string = $oldString . $newString;

        // Add this new String to the table
        $this->sTable[$this->tIdx++] = $string;

        if ($this->tIdx === 511) {
            $this->bitsToGet = 10;
        } elseif ($this->tIdx === 1023) {
            $this->bitsToGet = 11;
        } elseif ($this->tIdx === 2047) {
            $this->bitsToGet = 12;
        }
    }

    /**
     * Returns the next 9, 10, 11 or 12 bits.
     *
     * @return int
     */
    protected function getNextCode()
    {
        if ($this->bytePointer === $this->dataLength) {
            return 257;
        }

        $this->nextData = ($this->nextData << 8) | (\ord($this->data[$this->bytePointer++]) & 0xff);
        $this->nextBits += 8;

        if ($this->nextBits < $this->bitsToGet) {
            $this->nextData = ($this->nextData << 8) | (\ord($this->data[$this->bytePointer++]) & 0xff);
            $this->nextBits += 8;
        }

        $code = ($this->nextData >> ($this->nextBits - $this->bitsToGet)) & $this->andTable[$this->bitsToGet - 9];
        $this->nextBits -= $this->bitsToGet;

        return $code;
    }
}

// ==========================================
// File: PdfParser.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfParser;

use setasign\Fpdi\PdfParser\CrossReference\CrossReference;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfBoolean;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfHexString;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObjectReference;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNull;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfToken;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * A PDF parser class
 */
class PdfParser
{
    /**
     * @var StreamReader
     */
    protected $streamReader;

    /**
     * @var Tokenizer
     */
    protected $tokenizer;

    /**
     * The file header.
     *
     * @var string
     */
    protected $fileHeader;

    /**
     * The offset to the file header.
     *
     * @var int
     */
    protected $fileHeaderOffset;

    /**
     * @var CrossReference|null
     */
    protected $xref;

    /**
     * All read objects.
     *
     * @var array
     */
    protected $objects = [];

    /**
     * PdfParser constructor.
     *
     * @param StreamReader $streamReader
     */
    public function __construct(StreamReader $streamReader)
    {
        $this->streamReader = $streamReader;
        $this->tokenizer = new Tokenizer($streamReader);
    }

    /**
     * Removes cycled references.
     *
     * @internal
     */
    public function cleanUp()
    {
        $this->xref = null;
    }

    /**
     * Get the stream reader instance.
     *
     * @return StreamReader
     */
    public function getStreamReader()
    {
        return $this->streamReader;
    }

    /**
     * Get the tokenizer instance.
     *
     * @return Tokenizer
     */
    public function getTokenizer()
    {
        return $this->tokenizer;
    }

    /**
     * Resolves the file header.
     *
     * @throws PdfParserException
     * @return int
     */
    protected function resolveFileHeader()
    {
        if ($this->fileHeader) {
            return $this->fileHeaderOffset;
        }

        $this->streamReader->reset(0);
        $maxIterations = 1000;
        while (true) {
            $buffer = $this->streamReader->getBuffer(false);
            $offset = \strpos($buffer, '%PDF-');
            if ($offset === false) {
                if (!$this->streamReader->increaseLength(100) || (--$maxIterations === 0)) {
                    throw new PdfParserException(
                        'Unable to find PDF file header.',
                        PdfParserException::FILE_HEADER_NOT_FOUND
                    );
                }
                continue;
            }
            break;
        }

        $this->fileHeaderOffset = $offset;
        $this->streamReader->setOffset($offset);

        $this->fileHeader = \trim($this->streamReader->readLine());
        return $this->fileHeaderOffset;
    }

    /**
     * Get the cross-reference instance.
     *
     * @return CrossReference
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public function getCrossReference()
    {
        if ($this->xref === null) {
            $this->xref = new CrossReference($this, $this->resolveFileHeader());
        }

        return $this->xref;
    }

    /**
     * Get the PDF version.
     *
     * @return int[] An array of major and minor version.
     * @throws PdfParserException
     */
    public function getPdfVersion()
    {
        $this->resolveFileHeader();

        if (\preg_match('/%PDF-(\d)\.(\d)/', $this->fileHeader, $result) === 0) {
            throw new PdfParserException(
                'Unable to extract PDF version from file header.',
                PdfParserException::PDF_VERSION_NOT_FOUND
            );
        }
        [, $major, $minor] = $result;

        $catalog = $this->getCatalog();
        if (isset($catalog->value['Version'])) {
            $versionParts = \explode(
                '.',
                PdfName::unescape(PdfType::resolve($catalog->value['Version'], $this)->value)
            );
            if (count($versionParts) === 2) {
                [$major, $minor] = $versionParts;
            }
        }

        return [(int) $major, (int) $minor];
    }

    /**
     * Get the catalog dictionary.
     *
     * @return PdfDictionary
     * @throws Type\PdfTypeException
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public function getCatalog()
    {
        $trailer = $this->getCrossReference()->getTrailer();

        $catalog = PdfType::resolve(PdfDictionary::get($trailer, 'Root'), $this);

        return PdfDictionary::ensure($catalog);
    }

    /**
     * Get an indirect object by its object number.
     *
     * @param int $objectNumber
     * @param bool $cache
     * @return PdfIndirectObject
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public function getIndirectObject($objectNumber, $cache = false)
    {
        $objectNumber = (int) $objectNumber;
        if (isset($this->objects[$objectNumber])) {
            return $this->objects[$objectNumber];
        }

        $object = $this->getCrossReference()->getIndirectObject($objectNumber);

        if ($cache) {
            $this->objects[$objectNumber] = $object;
        }

        return $object;
    }

    /**
     * Read a PDF value.
     *
     * @param null|bool|string $token
     * @param null|string $expectedType
     * @return false|PdfArray|PdfBoolean|PdfDictionary|PdfHexString|PdfIndirectObject|PdfIndirectObjectReference|PdfName|PdfNull|PdfNumeric|PdfStream|PdfString|PdfToken
     * @throws Type\PdfTypeException
     */
    public function readValue($token = null, $expectedType = null)
    {
        if ($token === null) {
            $token = $this->tokenizer->getNextToken();
        }

        if ($token === false) {
            if ($expectedType !== null) {
                throw new Type\PdfTypeException('Got unexpected token type.', Type\PdfTypeException::INVALID_DATA_TYPE);
            }
            return false;
        }

        switch ($token) {
            case '(':
                $this->ensureExpectedType($token, $expectedType);
                return $this->parsePdfString();

            case '<':
                if ($this->streamReader->getByte() === '<') {
                    $this->ensureExpectedType('<<', $expectedType);
                    $this->streamReader->addOffset(1);
                    return $this->parsePdfDictionary();
                }

                $this->ensureExpectedType($token, $expectedType);
                return $this->parsePdfHexString();

            case '/':
                $this->ensureExpectedType($token, $expectedType);
                return $this->parsePdfName();

            case '[':
                $this->ensureExpectedType($token, $expectedType);
                return $this->parsePdfArray();

            default:
                if (\is_numeric($token)) {
                    $token2 = $this->tokenizer->getNextToken();
                    if ($token2 !== false) {
                        if (\is_numeric($token2)) {
                            $token3 = $this->tokenizer->getNextToken();
                            if ($token3 === 'obj') {
                                if ($expectedType !== null && $expectedType !== PdfIndirectObject::class) {
                                    throw new Type\PdfTypeException(
                                        'Got unexpected token type.',
                                        Type\PdfTypeException::INVALID_DATA_TYPE
                                    );
                                }

                                return $this->parsePdfIndirectObject((int) $token, (int) $token2);
                            } elseif ($token3 === 'R') {
                                if (
                                    $expectedType !== null &&
                                    $expectedType !== PdfIndirectObjectReference::class
                                ) {
                                    throw new Type\PdfTypeException(
                                        'Got unexpected token type.',
                                        Type\PdfTypeException::INVALID_DATA_TYPE
                                    );
                                }

                                return PdfIndirectObjectReference::create((int) $token, (int) $token2);
                            } elseif ($token3 !== false) {
                                $this->tokenizer->pushStack($token3);
                            }
                        }

                        $this->tokenizer->pushStack($token2);
                    }

                    if ($expectedType !== null && $expectedType !== PdfNumeric::class) {
                        throw new Type\PdfTypeException(
                            'Got unexpected token type.',
                            Type\PdfTypeException::INVALID_DATA_TYPE
                        );
                    }
                    return PdfNumeric::create($token + 0);
                }

                if ($token === 'true' || $token === 'false') {
                    $this->ensureExpectedType($token, $expectedType);
                    return PdfBoolean::create($token === 'true');
                }

                if ($token === 'null') {
                    $this->ensureExpectedType($token, $expectedType);
                    return new PdfNull();
                }

                if ($expectedType !== null && $expectedType !== PdfToken::class) {
                    throw new Type\PdfTypeException(
                        'Got unexpected token type.',
                        Type\PdfTypeException::INVALID_DATA_TYPE
                    );
                }

                $v = new PdfToken();
                $v->value = $token;

                return $v;
        }
    }

    /**
     * @return PdfString
     */
    protected function parsePdfString()
    {
        return PdfString::parse($this->streamReader);
    }

    /**
     * @return false|PdfHexString
     */
    protected function parsePdfHexString()
    {
        return PdfHexString::parse($this->streamReader);
    }

    /**
     * @return bool|PdfDictionary
     * @throws PdfTypeException
     */
    protected function parsePdfDictionary()
    {
        return PdfDictionary::parse($this->tokenizer, $this->streamReader, $this);
    }

    /**
     * @return PdfName
     */
    protected function parsePdfName()
    {
        return PdfName::parse($this->tokenizer, $this->streamReader);
    }

    /**
     * @return false|PdfArray
     * @throws PdfTypeException
     */
    protected function parsePdfArray()
    {
        return PdfArray::parse($this->tokenizer, $this);
    }

    /**
     * @param int $objectNumber
     * @param int $generationNumber
     * @return false|PdfIndirectObject
     * @throws Type\PdfTypeException
     */
    protected function parsePdfIndirectObject($objectNumber, $generationNumber)
    {
        return PdfIndirectObject::parse(
            $objectNumber,
            $generationNumber,
            $this,
            $this->tokenizer,
            $this->streamReader
        );
    }

    /**
     * Ensures that the token will evaluate to an expected object type (or not).
     *
     * @param string $token
     * @param string|null $expectedType
     * @return bool
     * @throws Type\PdfTypeException
     */
    protected function ensureExpectedType($token, $expectedType)
    {
        static $mapping = [
            '(' => PdfString::class,
            '<' => PdfHexString::class,
            '<<' => PdfDictionary::class,
            '/' => PdfName::class,
            '[' => PdfArray::class,
            'true' => PdfBoolean::class,
            'false' => PdfBoolean::class,
            'null' => PdfNull::class
        ];

        if ($expectedType === null || $mapping[$token] === $expectedType) {
            return true;
        }

        throw new Type\PdfTypeException('Got unexpected token type.', Type\PdfTypeException::INVALID_DATA_TYPE);
    }
}

// ==========================================
// File: PageBoundaries.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfReader;

/**
 * An abstract class for page boundary constants and some helper methods
 */
abstract class PageBoundaries
{
    /**
     * MediaBox
     *
     * The media box defines the boundaries of the physical medium on which the page is to be printed.
     *
     * @see PDF 32000-1:2008 - 14.11.2 Page Boundaries
     * @var string
     */
    const MEDIA_BOX = 'MediaBox';

    /**
     * CropBox
     *
     * The crop box defines the region to which the contents of the page shall be clipped (cropped) when displayed or
     * printed.
     *
     * @see PDF 32000-1:2008 - 14.11.2 Page Boundaries
     * @var string
     */
    const CROP_BOX = 'CropBox';

    /**
     * BleedBox
     *
     * The bleed box defines the region to which the contents of the page shall be clipped when output in a
     * production environment.
     *
     * @see PDF 32000-1:2008 - 14.11.2 Page Boundaries
     * @var string
     */
    const BLEED_BOX = 'BleedBox';

    /**
     * TrimBox
     *
     * The trim box defines the intended dimensions of the finished page after trimming.
     *
     * @see PDF 32000-1:2008 - 14.11.2 Page Boundaries
     * @var string
     */
    const TRIM_BOX = 'TrimBox';

    /**
     * ArtBox
     *
     * The art box defines the extent of the page’s meaningful content (including potential white space) as intended
     * by the page’s creator.
     *
     * @see PDF 32000-1:2008 - 14.11.2 Page Boundaries
     * @var string
     */
    const ART_BOX = 'ArtBox';

    /**
     * All page boundaries
     *
     * @var array
     */
    public static $all = array(
        self::MEDIA_BOX,
        self::CROP_BOX,
        self::BLEED_BOX,
        self::TRIM_BOX,
        self::ART_BOX
    );

    /**
     * Checks if a name is a valid page boundary name.
     *
     * @param string $name The boundary name
     * @return boolean A boolean value whether the name is valid or not.
     */
    public static function isValidName($name)
    {
        return \in_array($name, self::$all, true);
    }
}

// ==========================================
// File: Page.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfReader;

use setasign\Fpdi\FpdiException;
use setasign\Fpdi\GraphicsState;
use setasign\Fpdi\Math\Vector;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfHexString;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNull;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\DataStructure\Rectangle;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;

/**
 * Class representing a page of a PDF document
 */
class Page
{
    /**
     * @var PdfIndirectObject
     */
    protected $pageObject;

    /**
     * @var PdfDictionary
     */
    protected $pageDictionary;

    /**
     * @var PdfParser
     */
    protected $parser;

    /**
     * Inherited attributes
     *
     * @var null|array
     */
    protected $inheritedAttributes;

    /**
     * Page constructor.
     *
     * @param PdfIndirectObject $page
     * @param PdfParser $parser
     */
    public function __construct(PdfIndirectObject $page, PdfParser $parser)
    {
        $this->pageObject = $page;
        $this->parser = $parser;
    }

    /**
     * Get the indirect object of this page.
     *
     * @return PdfIndirectObject
     */
    public function getPageObject()
    {
        return $this->pageObject;
    }

    /**
     * Get the dictionary of this page.
     *
     * @return PdfDictionary
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws CrossReferenceException
     */
    public function getPageDictionary()
    {
        if ($this->pageDictionary === null) {
            $this->pageDictionary = PdfDictionary::ensure(PdfType::resolve($this->getPageObject(), $this->parser));
        }

        return $this->pageDictionary;
    }

    /**
     * Get a page attribute.
     *
     * @param string $name
     * @param bool $inherited
     * @return PdfType|null
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws CrossReferenceException
     */
    public function getAttribute($name, $inherited = true)
    {
        $dict = $this->getPageDictionary();

        if (isset($dict->value[$name])) {
            return $dict->value[$name];
        }

        $inheritedKeys = ['Resources', 'MediaBox', 'CropBox', 'Rotate'];
        if ($inherited && \in_array($name, $inheritedKeys, true)) {
            if ($this->inheritedAttributes === null) {
                $this->inheritedAttributes = [];
                $inheritedKeys = \array_filter($inheritedKeys, static function ($key) use ($dict) {
                    return !isset($dict->value[$key]);
                });

                if (\count($inheritedKeys) > 0) {
                    $ensuredObjectList = [];
                    $parentDict = PdfType::resolve(
                        PdfDictionary::get($dict, 'Parent'),
                        $this->parser,
                        false,
                        $ensuredObjectList
                    );
                    while ($parentDict instanceof PdfDictionary) {
                        foreach ($inheritedKeys as $index => $key) {
                            if (isset($parentDict->value[$key])) {
                                $this->inheritedAttributes[$key] = $parentDict->value[$key];
                                unset($inheritedKeys[$index]);
                            }
                        }

                        /** @noinspection NotOptimalIfConditionsInspection */
                        if (isset($parentDict->value['Parent']) && \count($inheritedKeys) > 0) {
                            $parentDict = PdfType::resolve(
                                PdfDictionary::get($parentDict, 'Parent'),
                                $this->parser,
                                false,
                                $ensuredObjectList
                            );
                        } else {
                            break;
                        }
                    }
                }
            }

            if (isset($this->inheritedAttributes[$name])) {
                return $this->inheritedAttributes[$name];
            }
        }

        return null;
    }

    /**
     * Get the rotation value.
     *
     * @return int
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws CrossReferenceException
     */
    public function getRotation()
    {
        $rotation = $this->getAttribute('Rotate');
        if ($rotation === null) {
            return 0;
        }

        $rotation = PdfNumeric::ensure(PdfType::resolve($rotation, $this->parser))->value % 360;

        if ($rotation < 0) {
            $rotation += 360;
        }

        return $rotation;
    }

    /**
     * Get a boundary of this page.
     *
     * @param string $box
     * @param bool $fallback
     * @return bool|Rectangle
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws CrossReferenceException
     * @see PageBoundaries
     */
    public function getBoundary($box = PageBoundaries::CROP_BOX, $fallback = true)
    {
        $value = $this->getAttribute($box);

        if ($value !== null) {
            return Rectangle::byPdfArray($value, $this->parser);
        }

        if ($fallback === false) {
            return false;
        }

        switch ($box) {
            case PageBoundaries::BLEED_BOX:
            case PageBoundaries::TRIM_BOX:
            case PageBoundaries::ART_BOX:
                return $this->getBoundary(PageBoundaries::CROP_BOX, true);
            case PageBoundaries::CROP_BOX:
                return $this->getBoundary(PageBoundaries::MEDIA_BOX, true);
        }

        return false;
    }

    /**
     * Get the width and height of this page.
     *
     * @param string $box
     * @param bool $fallback
     * @return array|bool
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws CrossReferenceException
     */
    public function getWidthAndHeight($box = PageBoundaries::CROP_BOX, $fallback = true)
    {
        $boundary = $this->getBoundary($box, $fallback);
        if ($boundary === false) {
            return false;
        }

        $rotation = $this->getRotation();
        $interchange = ($rotation / 90) % 2;

        return [
            $interchange ? $boundary->getHeight() : $boundary->getWidth(),
            $interchange ? $boundary->getWidth() : $boundary->getHeight()
        ];
    }

    /**
     * Get the raw content stream.
     *
     * @return string
     * @throws PdfReaderException
     * @throws PdfTypeException
     * @throws FilterException
     * @throws PdfParserException
     */
    public function getContentStream()
    {
        $dict = $this->getPageDictionary();
        $contents = PdfType::resolve(PdfDictionary::get($dict, 'Contents'), $this->parser);
        if ($contents instanceof PdfNull) {
            return '';
        }

        if ($contents instanceof PdfArray) {
            $result = [];
            foreach ($contents->value as $content) {
                $content = PdfType::resolve($content, $this->parser);
                if (!($content instanceof PdfStream)) {
                    continue;
                }

                try {
                    $result[] = $content->getUnfilteredStream();
                } catch (FilterException $e) {
                    // ignore streams that cannot be unfiltered
                }
            }

            return \implode("\n", $result);
        }

        if ($contents instanceof PdfStream) {
            try {
                return $contents->getUnfilteredStream();
            } catch (FilterException $e) {
                // ignore streams that cannot be unfiltered
                return '';
            }
        }

        throw new PdfReaderException(
            'Array or stream expected.',
            PdfReaderException::UNEXPECTED_DATA_TYPE
        );
    }

    /**
     * Get information of all external links on this page.
     *
     * All coordinates are normalized in view to rotation and translation of the boundary-box, so that their
     * origin is lower-left.
     *
     * The URI is the binary value of the PDF string object. It can be in PdfDocEncoding or in UTF-16BE encoding.
     *
     * @return array
     */
    public function getExternalLinks($box = PageBoundaries::CROP_BOX)
    {
        try {
            $dict = $this->getPageDictionary();
            $annotations = PdfType::resolve(PdfDictionary::get($dict, 'Annots'), $this->parser);
        } catch (FpdiException $e) {
            return [];
        }

        if (!$annotations instanceof PdfArray) {
            return [];
        }

        $links = [];

        foreach ($annotations->value as $entry) {
            try {
                $annotation = PdfType::resolve($entry, $this->parser);

                $value = PdfType::resolve(PdfDictionary::get($annotation, 'Subtype'), $this->parser);
                if (!$value instanceof PdfName || $value->value !== 'Link') {
                    continue;
                }

                $dest = PdfType::resolve(PdfDictionary::get($annotation, 'Dest'), $this->parser);
                if (!$dest instanceof PdfNull) {
                    continue;
                }

                $action = PdfType::resolve(PdfDictionary::get($annotation, 'A'), $this->parser);
                if (!$action instanceof PdfDictionary) {
                    continue;
                }

                $actionType = PdfType::resolve(PdfDictionary::get($action, 'S'), $this->parser);
                if (!$actionType instanceof PdfName || $actionType->value !== 'URI') {
                    continue;
                }

                $uri = PdfType::resolve(PdfDictionary::get($action, 'URI'), $this->parser);
                if ($uri instanceof PdfString) {
                    $uriValue = PdfString::unescape($uri->value);
                } elseif ($uri instanceof PdfHexString) {
                    $uriValue = \hex2bin($uri->value);
                } else {
                    continue;
                }

                $rect = PdfType::resolve(PdfDictionary::get($annotation, 'Rect'), $this->parser);
                if (!$rect instanceof PdfArray || \count($rect->value) !== 4) {
                    continue;
                }

                $rect = Rectangle::byPdfArray($rect, $this->parser);
                if ($rect->getWidth() === 0 || $rect->getHeight() === 0) {
                    continue;
                }

                $bbox = $this->getBoundary($box);
                $rotation = $this->getRotation();

                $gs = new GraphicsState();
                $gs->translate(-$bbox->getLlx(), -$bbox->getLly());
                $gs->rotate($bbox->getLlx(), $bbox->getLly(), -$rotation);

                switch ($rotation) {
                    case 90:
                        $gs->translate(-$bbox->getWidth(), 0);
                        break;
                    case 180:
                        $gs->translate(-$bbox->getWidth(), -$bbox->getHeight());
                        break;
                    case 270:
                        $gs->translate(0, -$bbox->getHeight());
                        break;
                }

                $normalizedRect = Rectangle::byVectors(
                    $gs->toUserSpace(new Vector($rect->getLlx(), $rect->getLly())),
                    $gs->toUserSpace(new Vector($rect->getUrx(), $rect->getUry()))
                );

                $quadPoints = PdfType::resolve(PdfDictionary::get($annotation, 'QuadPoints'), $this->parser);
                $normalizedQuadPoints = [];
                if ($quadPoints instanceof PdfArray) {
                    $quadPointsCount = \count($quadPoints->value);
                    if ($quadPointsCount % 8 === 0) {
                        for ($i = 0; ($i + 1) < $quadPointsCount; $i += 2) {
                            $x = PdfNumeric::ensure(PdfType::resolve($quadPoints->value[$i], $this->parser));
                            $y = PdfNumeric::ensure(PdfType::resolve($quadPoints->value[$i + 1], $this->parser));

                            $v = $gs->toUserSpace(new Vector($x->value, $y->value));
                            $normalizedQuadPoints[] = $v->getX();
                            $normalizedQuadPoints[] = $v->getY();
                        }
                    }
                }

                // we remove unsupported/unneeded values here
                unset(
                    $annotation->value['P'],
                    $annotation->value['NM'],
                    $annotation->value['AP'],
                    $annotation->value['AS'],
                    $annotation->value['Type'],
                    $annotation->value['Subtype'],
                    $annotation->value['Rect'],
                    $annotation->value['A'],
                    $annotation->value['QuadPoints'],
                    $annotation->value['Rotate'],
                    $annotation->value['M'],
                    $annotation->value['StructParent'],
                    $annotation->value['OC']
                );

                // ...and flatten the PDF object to eliminate any indirect references.
                // Indirect references are a problem when writing the output in FPDF
                // because FPDF uses pre-calculated object numbers while FPDI creates
                // them at runtime.
                $annotation = PdfType::flatten($annotation, $this->parser);

                $links[] = [
                    'rect' => $normalizedRect,
                    'quadPoints' => $normalizedQuadPoints,
                    'uri' => $uriValue,
                    'pdfObject' => $annotation
                ];
            } catch (FpdiException $e) {
                continue;
            }
        }

        return $links;
    }
}

// ==========================================
// File: PdfReader.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\PdfReader;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObjectReference;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * A PDF reader class
 */
class PdfReader
{
    /**
     * @var PdfParser
     */
    protected $parser;

    /**
     * @var int
     */
    protected $pageCount;

    /**
     * Indirect objects of resolved pages.
     *
     * @var PdfIndirectObjectReference[]|PdfIndirectObject[]
     */
    protected $pages = [];

    /**
     * PdfReader constructor.
     *
     * @param PdfParser $parser
     */
    public function __construct(PdfParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * PdfReader destructor.
     */
    public function __destruct()
    {
        if ($this->parser !== null) {
            $this->parser->cleanUp();
        }
    }

    /**
     * Get the pdf parser instance.
     *
     * @return PdfParser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Get the PDF version.
     *
     * @return string
     * @throws PdfParserException
     */
    public function getPdfVersion()
    {
        return \implode('.', $this->parser->getPdfVersion());
    }

    /**
     * Get the page count.
     *
     * @return int
     * @throws PdfTypeException
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    public function getPageCount()
    {
        if ($this->pageCount === null) {
            $catalog = $this->parser->getCatalog();

            $pages = PdfType::resolve(PdfDictionary::get($catalog, 'Pages'), $this->parser);
            $count = PdfType::resolve(PdfDictionary::get($pages, 'Count'), $this->parser);

            $this->pageCount = PdfNumeric::ensure($count)->value;
        }

        return $this->pageCount;
    }

    /**
     * Get a page instance.
     *
     * @param int|numeric-string $pageNumber
     * @return Page
     * @throws PdfTypeException
     * @throws CrossReferenceException
     * @throws PdfParserException
     * @throws \InvalidArgumentException
     */
    public function getPage($pageNumber)
    {
        if (!\is_numeric($pageNumber)) {
            throw new \InvalidArgumentException(
                'Page number needs to be a number.'
            );
        }

        $pageNumber = (int)$pageNumber;

        if ($pageNumber < 1 || $pageNumber > $this->getPageCount()) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Page number "%s" out of available page range (1 - %s)',
                    $pageNumber,
                    $this->getPageCount()
                )
            );
        }

        $this->readPages();

        $page = $this->pages[$pageNumber - 1];

        if ($page instanceof PdfIndirectObjectReference) {
            $alreadyReadKids = [];
            $readPages = function ($kids) use (&$readPages, &$alreadyReadKids) {
                $kids = PdfArray::ensure($kids);

                /** @noinspection LoopWhichDoesNotLoopInspection */
                foreach ($kids->value as $reference) {
                    $reference = PdfIndirectObjectReference::ensure($reference);

                    if (\in_array($reference->value, $alreadyReadKids, true)) {
                        throw new PdfReaderException('Recursive pages dictionary detected.');
                    }
                    $alreadyReadKids[] = $reference->value;

                    $object = $this->parser->getIndirectObject($reference->value);
                    $type = PdfDictionary::get($object->value, 'Type');

                    if ($type->value === 'Pages') {
                        return $readPages(PdfDictionary::get($object->value, 'Kids'));
                    }

                    return $object;
                }

                throw new PdfReaderException(
                    'Kids array cannot be empty.',
                    PdfReaderException::KIDS_EMPTY
                );
            };

            $page = $this->parser->getIndirectObject($page->value);
            $dict = PdfType::resolve($page, $this->parser);
            $type = PdfDictionary::get($dict, 'Type');

            if ($type->value === 'Pages') {
                $kids = PdfType::resolve(PdfDictionary::get($dict, 'Kids'), $this->parser);
                try {
                    $alreadyReadKids[] = $page->objectNumber;
                    $page = $this->pages[$pageNumber - 1] = $readPages($kids);
                } catch (PdfReaderException $e) {
                    if ($e->getCode() !== PdfReaderException::KIDS_EMPTY) {
                        throw $e;
                    }

                    // let's reset the pages array and read all page objects
                    $this->pages = [];
                    $this->readPages(true);
                    // @phpstan-ignore-next-line
                    $page = $this->pages[$pageNumber - 1];
                }
            } else {
                $this->pages[$pageNumber - 1] = $page;
            }
        }

        return new Page($page, $this->parser);
    }

    /**
     * Walk the page tree and resolve all indirect objects of all pages.
     *
     * @param bool $readAll
     * @throws CrossReferenceException
     * @throws PdfParserException
     * @throws PdfTypeException
     */
    protected function readPages($readAll = false)
    {
        if (\count($this->pages) > 0) {
            return;
        }

        $expectedPageCount = $this->getPageCount();
        $alreadyReadKids = [];
        $readPages = function ($kids, $count) use (&$readPages, &$alreadyReadKids, $readAll, $expectedPageCount) {
            $kids = PdfArray::ensure($kids);
            $isLeaf = ($count->value === \count($kids->value));

            foreach ($kids->value as $reference) {
                $reference = PdfIndirectObjectReference::ensure($reference);

                if (!$readAll && $isLeaf) {
                    $this->pages[] = $reference;
                    continue;
                }

                if (\in_array($reference->value, $alreadyReadKids, true)) {
                    throw new PdfReaderException('Recursive pages dictionary detected.');
                }
                $alreadyReadKids[] = $reference->value;

                $object = $this->parser->getIndirectObject($reference->value);
                $type = PdfDictionary::get($object->value, 'Type');

                if ($type->value === 'Pages') {
                    $readPages(
                        PdfType::resolve(PdfDictionary::get($object->value, 'Kids'), $this->parser),
                        PdfType::resolve(PdfDictionary::get($object->value, 'Count'), $this->parser)
                    );
                } else {
                    $this->pages[] = $object;
                }

                // stop if all pages are read - faulty documents exists with additional entries with invalid data.
                if (count($this->pages) === $expectedPageCount) {
                    break;
                }
            }
        };

        $catalog = $this->parser->getCatalog();
        $pages = PdfType::resolve(PdfDictionary::get($catalog, 'Pages'), $this->parser);
        $count = PdfType::resolve(PdfDictionary::get($pages, 'Count'), $this->parser);
        $kids = PdfType::resolve(PdfDictionary::get($pages, 'Kids'), $this->parser);
        $readPages($kids, $count);
    }
}

// ==========================================
// File: FpdfTrait.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfNull;
use setasign\Fpdi\PdfParser\Type\PdfType;

/**
 * This trait is used for the implementation of FPDI in FPDF and tFPDF.
 */
trait FpdfTrait
{
    protected function _enddoc()
    {
        parent::_enddoc();
        $this->cleanUp();
    }

    /**
     * Draws an imported page or a template onto the page or another template.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param float|int|array $x The abscissa of upper-left corner. Alternatively you could use an assoc array
     *                           with the keys "x", "y", "width", "height", "adjustPageSize".
     * @param float|int $y The ordinate of upper-left corner.
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @param bool $adjustPageSize
     * @return array The size
     * @see Fpdi::getTemplateSize()
     */
    public function useTemplate($tpl, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false)
    {
        if (isset($this->importedPages[$tpl])) {
            $size = $this->useImportedPage($tpl, $x, $y, $width, $height, $adjustPageSize);
            if ($this->currentTemplateId !== null) {
                $this->templates[$this->currentTemplateId]['resources']['templates']['importedPages'][$tpl] = $tpl;
            }
            return $size;
        }

        return parent::useTemplate($tpl, $x, $y, $width, $height, $adjustPageSize);
    }

    /**
     * Get the size of an imported page or template.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
     */
    public function getTemplateSize($tpl, $width = null, $height = null)
    {
        $size = parent::getTemplateSize($tpl, $width, $height);
        if ($size === false) {
            return $this->getImportedPageSize($tpl, $width, $height);
        }

        return $size;
    }

    /**
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    protected function _putimages()
    {
        $this->currentReaderId = null;
        parent::_putimages();

        foreach ($this->importedPages as $key => $pageData) {
            $this->_newobj();
            $this->importedPages[$key]['objectNumber'] = $this->n;
            $this->currentReaderId = $pageData['readerId'];
            $this->writePdfType($pageData['stream']);
            $this->_put('endobj');
        }

        foreach (\array_keys($this->readers) as $readerId) {
            $parser = $this->getPdfReader($readerId)->getParser();
            $this->currentReaderId = $readerId;

            while (($objectNumber = \array_pop($this->objectsToCopy[$readerId])) !== null) {
                try {
                    $object = $parser->getIndirectObject($objectNumber);
                } catch (CrossReferenceException $e) {
                    if ($e->getCode() === CrossReferenceException::OBJECT_NOT_FOUND) {
                        $object = PdfIndirectObject::create($objectNumber, 0, new PdfNull());
                    } else {
                        throw $e;
                    }
                }

                $this->writePdfType($object);
            }
        }

        $this->currentReaderId = null;
    }

    /**
     * @inheritdoc
     */
    protected function _putxobjectdict()
    {
        foreach ($this->importedPages as $pageData) {
            $this->_put('/' . $pageData['id'] . ' ' . $pageData['objectNumber'] . ' 0 R');
        }

        parent::_putxobjectdict();
    }

    /**
     * @param int $n
     * @return void
     * @throws PdfParser\Type\PdfTypeException
     */
    protected function _putlinks($n)
    {
        foreach ($this->PageLinks[$n] as $pl) {
            $this->_newobj();
            $rect = \sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
            $this->_put('<</Type /Annot /Subtype /Link /Rect [' . $rect . ']', false);
            if (is_string($pl[4])) {
                if (isset($pl['importedLink'])) {
                    $this->_put('/A <</S /URI /URI (' . $this->_escape($pl[4]) . ')>>');
                    $values = $pl['importedLink']['pdfObject']->value;

                    foreach ($values as $name => $entry) {
                        $this->_put('/' . $name . ' ', false);
                        $this->writePdfType($entry);
                    }

                    if (isset($pl['quadPoints'])) {
                        $s = '/QuadPoints[';
                        foreach ($pl['quadPoints'] as $value) {
                            $s .= \sprintf('%.2F ', $value);
                        }
                        $s .= ']';
                        $this->_put($s);
                    }
                } else {
                    $this->_put('/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>');
                    $this->_put('/Border [0 0 0]', false);
                }
                $this->_put('>>');
            } else {
                $this->_put('/Border [0 0 0] ', false);
                $l = $this->links[$pl[4]];
                if (isset($this->PageInfo[$l[0]]['size'])) {
                    $h = $this->PageInfo[$l[0]]['size'][1];
                } else {
                    $h = ($this->DefOrientation === 'P')
                        ? $this->DefPageSize[1] * $this->k
                        : $this->DefPageSize[0] * $this->k;
                }
                $this->_put(\sprintf(
                    '/Dest [%d 0 R /XYZ 0 %.2F null]>>',
                    $this->PageInfo[$l[0]]['n'],
                    $h - $l[1] * $this->k
                ));
            }
            $this->_put('endobj');
        }
    }

    protected function _put($s, $newLine = true)
    {
        if ($newLine) {
            $this->buffer .= $s . "\n";
        } else {
            $this->buffer .= $s;
        }
    }
}

// ==========================================
// File: FpdfTplTrait.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

/**
 * Trait FpdfTplTrait
 *
 * This trait adds a templating feature to FPDF and tFPDF.
 */
trait FpdfTplTrait
{
    /**
     * Data of all created templates.
     *
     * @var array
     */
    protected $templates = [];

    /**
     * The template id for the currently created template.
     *
     * @var null|int
     */
    protected $currentTemplateId;

    /**
     * A counter for template ids.
     *
     * @var int
     */
    protected $templateId = 0;

    /**
     * Set the page format of the current page.
     *
     * @param array $size An array with two values defining the size.
     * @param string $orientation "L" for landscape, "P" for portrait.
     * @throws \BadMethodCallException
     */
    public function setPageFormat($size, $orientation)
    {
        if ($this->currentTemplateId !== null) {
            throw new \BadMethodCallException('The page format cannot be changed when writing to a template.');
        }

        if (!\in_array($orientation, ['P', 'L'], true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid page orientation "%s"! Only "P" and "L" are allowed!',
                $orientation
            ));
        }

        $size = $this->_getpagesize($size);

        if (
            $orientation != $this->CurOrientation
            || $size[0] != $this->CurPageSize[0]
            || $size[1] != $this->CurPageSize[1]
        ) {
            // New size or orientation
            if ($orientation === 'P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w * $this->k;
            $this->hPt = $this->h * $this->k;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;

            $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
        }
    }

    /**
     * Draws a template onto the page or another template.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param array|float|int $x The abscissa of upper-left corner. Alternatively you could use an assoc array
     *                           with the keys "x", "y", "width", "height", "adjustPageSize".
     * @param float|int $y The ordinate of upper-left corner.
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @param bool $adjustPageSize
     * @return array The size
     * @see FpdfTplTrait::getTemplateSize()
     */
    public function useTemplate($tpl, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false)
    {
        if (!isset($this->templates[$tpl])) {
            throw new \InvalidArgumentException('Template does not exist!');
        }

        if (\is_array($x)) {
            unset($x['tpl']);
            \extract($x, EXTR_IF_EXISTS);
            /** @noinspection NotOptimalIfConditionsInspection */
            /** @phpstan-ignore function.alreadyNarrowedType  */
            if (\is_array($x)) {
                $x = 0;
            }
        }

        $template = $this->templates[$tpl];

        $originalSize = $this->getTemplateSize($tpl);
        $newSize = $this->getTemplateSize($tpl, $width, $height);
        if ($adjustPageSize) {
            $this->setPageFormat($newSize, $newSize['orientation']);
        }

        $this->_out(
        // reset standard values, translate and scale
            \sprintf(
                'q 0 J 1 w 0 j 0 G 0 g %.4F 0 0 %.4F %.4F %.4F cm /%s Do Q',
                ($newSize['width'] / $originalSize['width']),
                ($newSize['height'] / $originalSize['height']),
                $x * $this->k,
                ($this->h - $y - $newSize['height']) * $this->k,
                $template['id']
            )
        );

        return $newSize;
    }

    /**
     * Get the size of a template.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
     */
    public function getTemplateSize($tpl, $width = null, $height = null)
    {
        if (!isset($this->templates[$tpl])) {
            return false;
        }

        if ($width === null && $height === null) {
            $width = $this->templates[$tpl]['width'];
            $height = $this->templates[$tpl]['height'];
        } elseif ($width === null) {
            $width = $height * $this->templates[$tpl]['width'] / $this->templates[$tpl]['height'];
        }

        if ($height === null) {
            $height = $width * $this->templates[$tpl]['height'] / $this->templates[$tpl]['width'];
        }

        if ($height <= 0. || $width <= 0.) {
            throw new \InvalidArgumentException('Width or height parameter needs to be larger than zero.');
        }

        return [
            'width' => $width,
            'height' => $height,
            0 => $width,
            1 => $height,
            'orientation' => $width > $height ? 'L' : 'P'
        ];
    }

    /**
     * Begins a new template.
     *
     * @param float|int|null $width The width of the template. If null, the current page width is used.
     * @param float|int|null $height The height of the template. If null, the current page height is used.
     * @param bool $groupXObject Define the form XObject as a group XObject to support transparency (if used).
     * @return int A template identifier.
     */
    public function beginTemplate($width = null, $height = null, $groupXObject = false)
    {
        if ($width === null) {
            $width = $this->w;
        }

        if ($height === null) {
            $height = $this->h;
        }

        $templateId = $this->getNextTemplateId();

        // initiate buffer with current state of FPDF
        $buffer = "2 J\n"
            . \sprintf('%.2F w', $this->LineWidth * $this->k) . "\n";

        if ($this->FontFamily) {
            $buffer .= \sprintf("BT /F%d %.2F Tf ET\n", $this->CurrentFont['i'], $this->FontSizePt);
        }

        if ($this->DrawColor !== '0 G') {
            $buffer .= $this->DrawColor . "\n";
        }
        if ($this->FillColor !== '0 g') {
            $buffer .= $this->FillColor . "\n";
        }

        if ($groupXObject && \version_compare('1.4', $this->PDFVersion, '>')) {
            $this->PDFVersion = '1.4';
        }

        $this->templates[$templateId] = [
            'objectNumber' => null,
            'id' => 'TPL' . $templateId,
            'buffer' => $buffer,
            'width' => $width,
            'height' => $height,
            'groupXObject' => $groupXObject,
            'state' => [
                'x' => $this->x,
                'y' => $this->y,
                'AutoPageBreak' => $this->AutoPageBreak,
                'bMargin' => $this->bMargin,
                'tMargin' => $this->tMargin,
                'lMargin' => $this->lMargin,
                'rMargin' => $this->rMargin,
                'h' => $this->h,
                'hPt' => $this->hPt,
                'w' => $this->w,
                'wPt' => $this->wPt,
                'FontFamily' => $this->FontFamily,
                'FontStyle' => $this->FontStyle,
                'FontSizePt' => $this->FontSizePt,
                'FontSize' => $this->FontSize,
                'underline' => $this->underline,
                'TextColor' => $this->TextColor,
                'DrawColor' => $this->DrawColor,
                'FillColor' => $this->FillColor,
                'ColorFlag' => $this->ColorFlag
            ]
        ];

        $this->SetAutoPageBreak(false);
        $this->currentTemplateId = $templateId;

        $this->h = $height;
        $this->hPt = $height * $this->k;
        $this->w = $width;
        $this->wPt = $width * $this->k;

        $this->SetXY($this->lMargin, $this->tMargin);
        $this->SetRightMargin($this->w - $width + $this->rMargin);

        return $templateId;
    }

    /**
     * Ends a template.
     *
     * @return bool|int|null A template identifier.
     */
    public function endTemplate()
    {
        if ($this->currentTemplateId === null) {
            return false;
        }

        $templateId = $this->currentTemplateId;
        $template = $this->templates[$templateId];

        $state = $template['state'];
        $this->SetXY($state['x'], $state['y']);
        $this->tMargin = $state['tMargin'];
        $this->lMargin = $state['lMargin'];
        $this->rMargin = $state['rMargin'];
        $this->h = $state['h'];
        $this->hPt = $state['hPt'];
        $this->w = $state['w'];
        $this->wPt = $state['wPt'];
        $this->SetAutoPageBreak($state['AutoPageBreak'], $state['bMargin']);

        $this->FontFamily = $state['FontFamily'];
        $this->FontStyle = $state['FontStyle'];
        $this->FontSizePt = $state['FontSizePt'];
        $this->FontSize = $state['FontSize'];

        $this->TextColor = $state['TextColor'];
        $this->DrawColor = $state['DrawColor'];
        $this->FillColor = $state['FillColor'];
        $this->ColorFlag = $state['ColorFlag'];

        $this->underline = $state['underline'];

        $fontKey = $this->FontFamily . $this->FontStyle;
        if ($fontKey) {
            $this->CurrentFont = $this->fonts[$fontKey];
        } else {
            unset($this->CurrentFont);
        }

        $this->currentTemplateId = null;

        return $templateId;
    }

    /**
     * Get the next template id.
     *
     * @return int
     */
    protected function getNextTemplateId()
    {
        return $this->templateId++;
    }

    /* overwritten FPDF methods: */

    /**
     * @inheritdoc
     */
    public function AddPage($orientation = '', $size = '', $rotation = 0)
    {
        if ($this->currentTemplateId !== null) {
            throw new \BadMethodCallException('Pages cannot be added when writing to a template.');
        }
        parent::AddPage($orientation, $size, $rotation);
    }

    /**
     * @inheritdoc
     */
    public function Link($x, $y, $w, $h, $link)
    {
        if ($this->currentTemplateId !== null) {
            throw new \BadMethodCallException('Links cannot be set when writing to a template.');
        }
        parent::Link($x, $y, $w, $h, $link);
    }

    /**
     * @inheritdoc
     */
    public function SetLink($link, $y = 0, $page = -1)
    {
        if ($this->currentTemplateId !== null) {
            throw new \BadMethodCallException('Links cannot be set when writing to a template.');
        }
        return parent::SetLink($link, $y, $page);
    }

    /**
     * @inheritdoc
     */
    public function SetDrawColor($r, $g = null, $b = null)
    {
        parent::SetDrawColor($r, $g, $b);
        if ($this->page === 0 && $this->currentTemplateId !== null) {
            $this->_out($this->DrawColor);
        }
    }

    /**
     * @inheritdoc
     */
    public function SetFillColor($r, $g = null, $b = null)
    {
        parent::SetFillColor($r, $g, $b);
        if ($this->page === 0 && $this->currentTemplateId !== null) {
            $this->_out($this->FillColor);
        }
    }

    /**
     * @inheritdoc
     */
    public function SetLineWidth($width)
    {
        parent::SetLineWidth($width);
        if ($this->page === 0 && $this->currentTemplateId !== null) {
            $this->_out(\sprintf('%.2F w', $width * $this->k));
        }
    }

    /**
     * @inheritdoc
     */
    public function SetFont($family, $style = '', $size = 0)
    {
        parent::SetFont($family, $style, $size);
        if ($this->page === 0 && $this->currentTemplateId !== null) {
            $this->_out(\sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }

    /**
     * @inheritdoc
     */
    public function SetFontSize($size)
    {
        parent::SetFontSize($size);
        if ($this->page === 0 && $this->currentTemplateId !== null) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }

    protected function _putimages()
    {
        parent::_putimages();

        foreach ($this->templates as $key => $template) {
            $this->_newobj();
            $this->templates[$key]['objectNumber'] = $this->n;

            $this->_put('<</Type /XObject /Subtype /Form /FormType 1');
            $this->_put(\sprintf(
                '/BBox[0 0 %.2F %.2F]',
                $template['width'] * $this->k,
                $template['height'] * $this->k
            ));
            $this->_put('/Resources 2 0 R'); // default resources dictionary of FPDF

            if ($this->compress) {
                $buffer = \gzcompress($template['buffer']);
                $this->_put('/Filter/FlateDecode');
            } else {
                $buffer = $template['buffer'];
            }

            $this->_put('/Length ' . \strlen($buffer));

            if ($template['groupXObject']) {
                $this->_put('/Group <</Type/Group/S/Transparency>>');
            }

            $this->_put('>>');
            $this->_putstream($buffer);
            $this->_put('endobj');
        }
    }

    /**
     * @inheritdoc
     */
    protected function _putxobjectdict()
    {
        foreach ($this->templates as $key => $template) {
            $this->_put('/' . $template['id'] . ' ' . $template['objectNumber'] . ' 0 R');
        }

        parent::_putxobjectdict();
    }

    /**
     * @inheritdoc
     */
    public function _out($s)
    {
        if ($this->currentTemplateId !== null) {
            $this->templates[$this->currentTemplateId]['buffer'] .= $s . "\n";
        } else {
            parent::_out($s);
        }
    }
}

// ==========================================
// File: FpdiTrait.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfBoolean;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfHexString;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObjectReference;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNull;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfToken;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\DataStructure\Rectangle;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\PdfReader\PdfReader;
use setasign\Fpdi\PdfReader\PdfReaderException;
use /* This namespace/class is used by the commercial FPDI PDF-Parser add-on. */
    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUndefinedNamespaceInspection */
    setasign\FpdiPdfParser\PdfParser\PdfParser as FpdiPdfParser;

/**
 * The FpdiTrait
 *
 * This trait offers the core functionalities of FPDI. By passing them to a trait we can reuse it with e.g. TCPDF in a
 * very easy way.
 */
trait FpdiTrait
{
    /**
     * The pdf reader instances.
     *
     * @var PdfReader[]
     */
    protected $readers = [];

    /**
     * Instances created internally.
     *
     * @var array
     */
    protected $createdReaders = [];

    /**
     * The current reader id.
     *
     * @var string|null
     */
    protected $currentReaderId;

    /**
     * Data of all imported pages.
     *
     * @var array
     */
    protected $importedPages = [];

    /**
     * A map from object numbers of imported objects to new assigned object numbers by FPDF.
     *
     * @var array
     */
    protected $objectMap = [];

    /**
     * An array with information about objects, which needs to be copied to the resulting document.
     *
     * @var array
     */
    protected $objectsToCopy = [];

    /**
     * Release resources and file handles.
     *
     * This method is called internally when the document is created successfully. By default it only cleans up
     * stream reader instances which were created internally.
     *
     * @param bool $allReaders
     */
    public function cleanUp($allReaders = false)
    {
        $readers = $allReaders ? array_keys($this->readers) : $this->createdReaders;
        foreach ($readers as $id) {
            $this->readers[$id]->getParser()->getStreamReader()->cleanUp();
            unset($this->readers[$id]);
        }

        $this->createdReaders = [];
    }

    /**
     * Set the minimal PDF version.
     *
     * @param string $pdfVersion
     */
    protected function setMinPdfVersion($pdfVersion)
    {
        if (\version_compare($pdfVersion, $this->PDFVersion, '>')) {
            $this->PDFVersion = $pdfVersion;
        }
    }

    /** @noinspection PhpUndefinedClassInspection */
    /**
     * Get a new pdf parser instance.
     *
     * @param StreamReader $streamReader
     * @param array $parserParams Individual parameters passed to the parser instance.
     * @return PdfParser|FpdiPdfParser
     */
    protected function getPdfParserInstance(
        StreamReader $streamReader,
        #[\SensitiveParameter]
        array $parserParams = []
    ) {
        // note: if you get an exception here - turn off errors/warnings on not found classes for your autoloader.
        // psr-4 (https://www.php-fig.org/psr/psr-4/) says: Autoloader implementations MUST NOT throw
        // exceptions, MUST NOT raise errors of any level, and SHOULD NOT return a value.
        /** @noinspection PhpUndefinedClassInspection */
        if (\class_exists(FpdiPdfParser::class)) {
            /** @noinspection PhpUndefinedClassInspection */
            return new FpdiPdfParser($streamReader, $parserParams);
        }

        return new PdfParser($streamReader);
    }

    /**
     * Get an unique reader id by the $file parameter.
     *
     * @param string|resource|PdfReader|StreamReader $file An open file descriptor, a path to a file, a PdfReader
     *                                                     instance or a StreamReader instance.
     * @param array $parserParams Individual parameters passed to the parser instance.
     * @return string
     */
    protected function getPdfReaderId(
        $file,
        #[\SensitiveParameter]
        array $parserParams = []
    ) {
        if (\is_resource($file)) {
            $id = (string) $file;
        } elseif (\is_string($file)) {
            $id = \realpath($file);
            if ($id === false) {
                $id = $file;
            }
        } elseif (\is_object($file)) {
            $id = \spl_object_hash($file);
        } else {
            throw new \InvalidArgumentException(
                \sprintf('Invalid type in $file parameter (%s)', \gettype($file))
            );
        }

        $id = \md5($id . '|' . \print_r($parserParams, true));

        if (isset($this->readers[$id])) {
            return $id;
        }

        if (\is_resource($file)) {
            $streamReader = new StreamReader($file);
        } elseif (\is_string($file)) {
            $streamReader = StreamReader::createByFile($file);
            $this->createdReaders[] = $id;
        } else {
            $streamReader = $file;
        }

        $reader = new PdfReader($this->getPdfParserInstance($streamReader, $parserParams));
        $this->readers[$id] = $reader;

        return $id;
    }

    /**
     * Get a pdf reader instance by its id.
     *
     * @param string $id
     * @return PdfReader
     */
    protected function getPdfReader($id)
    {
        if (isset($this->readers[$id])) {
            return $this->readers[$id];
        }

        throw new \InvalidArgumentException(
            \sprintf('No pdf reader with the given id (%s) exists.', $id)
        );
    }

    /**
     * Set the source PDF file.
     *
     * @param string|resource|StreamReader $file Path to the file or a stream resource or a StreamReader instance.
     * @return int The page count of the PDF document.
     * @throws PdfParserException
     */
    public function setSourceFile($file)
    {
        return $this->setSourceFileWithParserParams($file);
    }

    /**
     * Set the source PDF file with parameters which are passed to the parser instance.
     *
     * This method allows us to pass e.g. authentication information to the parser instance.
     *
     * @param string|resource|StreamReader $file Path to the file or a stream resource or a StreamReader instance.
     * @param array $parserParams Individual parameters passed to the parser instance.
     * @return int The page count of the PDF document.
     * @throws CrossReferenceException
     * @throws PdfParserException
     * @throws PdfTypeException
     */
    public function setSourceFileWithParserParams(
        $file,
        #[\SensitiveParameter]
        array $parserParams = []
    ) {
        $this->currentReaderId = $this->getPdfReaderId($file, $parserParams);
        $this->objectsToCopy[$this->currentReaderId] = [];

        $reader = $this->getPdfReader($this->currentReaderId);
        $this->setMinPdfVersion($reader->getPdfVersion());

        return $reader->getPageCount();
    }

    /**
     * Imports a page.
     *
     * @param int $pageNumber The page number.
     * @param string $box The page boundary to import. Default set to PageBoundaries::CROP_BOX.
     * @param bool $groupXObject Define the form XObject as a group XObject to support transparency (if used).
     * @param bool $importExternalLinks Define whether external links are imported or not.
     * @return string A unique string identifying the imported page.
     * @throws CrossReferenceException
     * @throws FilterException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws PdfReaderException
     * @see PageBoundaries
     */
    public function importPage(
        $pageNumber,
        $box = PageBoundaries::CROP_BOX,
        $groupXObject = true,
        $importExternalLinks = false
    ) {
        if ($this->currentReaderId === null) {
            throw new \BadMethodCallException('No reader initiated. Call setSourceFile() first.');
        }

        $pageId = $this->currentReaderId;

        $pageNumber = (int)$pageNumber;
        $pageId .= '|' . $pageNumber . '|' . ($groupXObject ? '1' : '0') . '|' . ($importExternalLinks ? '1' : '0');

        // for backwards compatibility with FPDI 1
        $box = \ltrim($box, '/');
        if (!PageBoundaries::isValidName($box)) {
            throw new \InvalidArgumentException(
                \sprintf('Box name is invalid: "%s"', $box)
            );
        }

        $pageId .= '|' . $box;

        if (isset($this->importedPages[$pageId])) {
            return $pageId;
        }

        $reader = $this->getPdfReader($this->currentReaderId);
        $page = $reader->getPage($pageNumber);

        $bbox = $page->getBoundary($box);
        if ($bbox === false) {
            throw new PdfReaderException(
                \sprintf("Page doesn't have a boundary box (%s).", $box),
                PdfReaderException::MISSING_DATA
            );
        }

        $dict = new PdfDictionary();
        $dict->value['Type'] = PdfName::create('XObject');
        $dict->value['Subtype'] = PdfName::create('Form');
        $dict->value['FormType'] = PdfNumeric::create(1);
        $dict->value['BBox'] = $bbox->toPdfArray();

        if ($groupXObject) {
            $this->setMinPdfVersion('1.4');
            $dict->value['Group'] = PdfDictionary::create([
                'Type' => PdfName::create('Group'),
                'S' => PdfName::create('Transparency')
            ]);
        }

        $resources = $page->getAttribute('Resources');
        if ($resources !== null) {
            $dict->value['Resources'] = $resources;
        }

        [$width, $height] = $page->getWidthAndHeight($box);

        $a = 1;
        $b = 0;
        $c = 0;
        $d = 1;
        $e = -$bbox->getLlx();
        $f = -$bbox->getLly();

        $rotation = $page->getRotation();

        if ($rotation !== 0) {
            $rotation *= -1;
            $angle = $rotation * M_PI / 180;
            $a = \cos($angle);
            $b = \sin($angle);
            $c = -$b;
            $d = $a;

            switch ($rotation) {
                case -90:
                    $e = -$bbox->getLly();
                    $f = $bbox->getUrx();
                    break;
                case -180:
                    $e = $bbox->getUrx();
                    $f = $bbox->getUry();
                    break;
                case -270:
                    $e = $bbox->getUry();
                    $f = -$bbox->getLlx();
                    break;
            }
        }

        // we need to rotate/translate
        if ($a != 1 || $b != 0 || $c != 0 || $d != 1 || $e != 0 || $f != 0) {
            $dict->value['Matrix'] = PdfArray::create([
                PdfNumeric::create($a), PdfNumeric::create($b), PdfNumeric::create($c),
                PdfNumeric::create($d), PdfNumeric::create($e), PdfNumeric::create($f)
            ]);
        }

        // try to use the existing content stream
        $pageDict = $page->getPageDictionary();

        try {
            $contentsObject = PdfType::resolve(PdfDictionary::get($pageDict, 'Contents'), $reader->getParser(), true);
            $contents =  PdfType::resolve($contentsObject, $reader->getParser());

            // just copy the stream reference if it is only a single stream
            if (
                ($contentsIsStream = ($contents instanceof PdfStream))
                || ($contents instanceof PdfArray && \count($contents->value) === 1)
            ) {
                if ($contentsIsStream) {
                    /**
                     * @var PdfIndirectObject $contentsObject
                     */
                    $stream = $contents;
                } else {
                    $stream = PdfType::resolve($contents->value[0], $reader->getParser());
                }

                $filter = PdfDictionary::get($stream->value, 'Filter');
                if (!$filter instanceof PdfNull) {
                    $dict->value['Filter'] = $filter;
                }
                $length = PdfType::resolve(PdfDictionary::get($stream->value, 'Length'), $reader->getParser());
                $dict->value['Length'] = $length;
                $stream->value = $dict;
                // otherwise extract it from the array and re-compress the whole stream
            } else {
                $streamContent = $this->compress
                    ? \gzcompress($page->getContentStream())
                    : $page->getContentStream();

                $dict->value['Length'] = PdfNumeric::create(\strlen($streamContent));
                if ($this->compress) {
                    $dict->value['Filter'] = PdfName::create('FlateDecode');
                }

                $stream = PdfStream::create($dict, $streamContent);
            }
        // Catch faulty pages and use an empty content stream
        } catch (FpdiException $e) {
            $dict->value['Length'] = PdfNumeric::create(0);
            $stream = PdfStream::create($dict, '');
        }

        $externalLinks = [];
        if ($importExternalLinks) {
            $externalLinks = $page->getExternalLinks($box);
        }

        $this->importedPages[$pageId] = [
            'objectNumber' => null,
            'readerId' => $this->currentReaderId,
            'id' => 'TPL' . $this->getNextTemplateId(),
            'width' => $width / $this->k,
            'height' => $height / $this->k,
            'stream' => $stream,
            'externalLinks' => $externalLinks
        ];

        return $pageId;
    }

    /**
     * Draws an imported page onto the page.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $pageId The page id
     * @param float|int|array $x The abscissa of upper-left corner. Alternatively you could use an assoc array
     *                           with the keys "x", "y", "width", "height", "adjustPageSize".
     * @param float|int $y The ordinate of upper-left corner.
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @param bool $adjustPageSize
     * @return array The size.
     * @see Fpdi::getTemplateSize()
     */
    public function useImportedPage($pageId, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false)
    {
        if (\is_array($x)) {
            /** @noinspection OffsetOperationsInspection */
            unset($x['pageId']);
            \extract($x, EXTR_IF_EXISTS);
            /** @noinspection NotOptimalIfConditionsInspection */
            /** @phpstan-ignore function.alreadyNarrowedType  */
            if (\is_array($x)) {
                $x = 0;
            }
        }

        if (!isset($this->importedPages[$pageId])) {
            throw new \InvalidArgumentException('Imported page does not exist!');
        }

        $importedPage = $this->importedPages[$pageId];

        $originalSize = $this->getTemplateSize($pageId);
        $newSize = $this->getTemplateSize($pageId, $width, $height);
        if ($adjustPageSize) {
            $this->setPageFormat($newSize, $newSize['orientation']);
        }

        $scaleX = ($newSize['width'] / $originalSize['width']);
        $scaleY = ($newSize['height'] / $originalSize['height']);
        $xPt = $x * $this->k;
        $yPt = $y * $this->k;
        $newHeightPt = $newSize['height'] * $this->k;

        $this->_out(
            // reset standard values, translate and scale
            \sprintf(
                'q 0 J 1 w 0 j 0 G 0 g %.4F 0 0 %.4F %.4F %.4F cm /%s Do Q',
                $scaleX,
                $scaleY,
                $xPt,
                $this->hPt - $yPt - $newHeightPt,
                $importedPage['id']
            )
        );

        if (count($importedPage['externalLinks']) > 0) {
            foreach ($importedPage['externalLinks'] as $externalLink) {
                // mPDF uses also 'externalLinks' but doesn't come with a rect-value
                if (!isset($externalLink['rect'])) {
                    continue;
                }

                /** @var Rectangle $rect */
                $rect = $externalLink['rect'];
                $this->Link(
                    $x + $rect->getLlx() / $this->k * $scaleX,
                    $y + $newSize['height'] - ($rect->getLly() + $rect->getHeight()) / $this->k * $scaleY,
                    $rect->getWidth() / $this->k * $scaleX,
                    $rect->getHeight()  / $this->k * $scaleY,
                    $externalLink['uri']
                );

                $this->adjustLastLink($externalLink, $xPt, $scaleX, $yPt, $newHeightPt, $scaleY, $importedPage);
            }
        }

        return $newSize;
    }

    /**
     * This method will add additional data to the last created link/annotation.
     *
     * It is separated because TCPDF uses its own logic to handle link annotations.
     * This method is overwritten in the TCPDF implementation.
     *
     * @param array $externalLink
     * @param float|int $xPt
     * @param float|int $scaleX
     * @param float|int $yPt
     * @param float|int $newHeightPt
     * @param float|int $scaleY
     * @param array $importedPage
     * @return void
     */
    protected function adjustLastLink($externalLink, $xPt, $scaleX, $yPt, $newHeightPt, $scaleY, $importedPage)
    {
        // let's create a relation of the newly created link to the data of the external link
        $lastLink = \count($this->PageLinks[$this->page]);
        $this->PageLinks[$this->page][$lastLink - 1]['importedLink'] = $externalLink;
        if (\count($externalLink['quadPoints']) > 0) {
            $quadPoints = [];
            for ($i = 0, $n = \count($externalLink['quadPoints']); $i < $n; $i += 2) {
                $quadPoints[] = $xPt + $externalLink['quadPoints'][$i] * $scaleX;
                $quadPoints[] = $this->hPt - $yPt - $newHeightPt + $externalLink['quadPoints'][$i + 1] * $scaleY;
            }

            $this->PageLinks[$this->page][$lastLink - 1]['quadPoints'] = $quadPoints;
        }
    }

    /**
     * Get the size of an imported page.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
     */
    public function getImportedPageSize($tpl, $width = null, $height = null)
    {
        if (isset($this->importedPages[$tpl])) {
            $importedPage = $this->importedPages[$tpl];

            if ($width === null && $height === null) {
                $width = $importedPage['width'];
                $height = $importedPage['height'];
            } elseif ($width === null) {
                $width = $height * $importedPage['width'] / $importedPage['height'];
            }

            if ($height  === null) {
                $height = $width * $importedPage['height'] / $importedPage['width'];
            }

            if ($height <= 0. || $width <= 0.) {
                throw new \InvalidArgumentException('Width or height parameter needs to be larger than zero.');
            }

            return [
                'width' => $width,
                'height' => $height,
                0 => $width,
                1 => $height,
                'orientation' => $width > $height ? 'L' : 'P'
            ];
        }

        return false;
    }

    /**
     * Writes a PdfType object to the resulting buffer.
     *
     * @param PdfType $value
     * @throws PdfTypeException
     */
    protected function writePdfType(PdfType $value)
    {
        if ($value instanceof PdfNumeric) {
            if (\is_int($value->value)) {
                $this->_put($value->value . ' ', false);
            } else {
                $this->_put(\rtrim(\rtrim(\sprintf('%.5F', $value->value), '0'), '.') . ' ', false);
            }
        } elseif ($value instanceof PdfName) {
            $this->_put('/' . $value->value . ' ', false);
        } elseif ($value instanceof PdfString) {
            $this->_put('(' . $value->value . ')', false);
        } elseif ($value instanceof PdfHexString) {
            $this->_put('<' . $value->value . '>', false);
        } elseif ($value instanceof PdfBoolean) {
            $this->_put($value->value ? 'true ' : 'false ', false);
        } elseif ($value instanceof PdfArray) {
            $this->_put('[', false);
            foreach ($value->value as $entry) {
                $this->writePdfType($entry);
            }
            $this->_put(']');
        } elseif ($value instanceof PdfDictionary) {
            $this->_put('<<', false);
            foreach ($value->value as $name => $entry) {
                $this->_put('/' . $name . ' ', false);
                $this->writePdfType($entry);
            }
            $this->_put('>>');
        } elseif ($value instanceof PdfToken) {
            $this->_put($value->value);
        } elseif ($value instanceof PdfNull) {
            $this->_put('null ', false);
        } elseif ($value instanceof PdfStream) {
            $this->writePdfType($value->value);
            $this->_put('stream');
            $this->_put($value->getStream());
            $this->_put('endstream');
        } elseif ($value instanceof PdfIndirectObjectReference) {
            if (!isset($this->objectMap[$this->currentReaderId])) {
                $this->objectMap[$this->currentReaderId] = [];
            }

            if (!isset($this->objectMap[$this->currentReaderId][$value->value])) {
                $this->objectMap[$this->currentReaderId][$value->value] = ++$this->n;
                $this->objectsToCopy[$this->currentReaderId][] = $value->value;
            }

            $this->_put($this->objectMap[$this->currentReaderId][$value->value] . ' 0 R ', false);
        } elseif ($value instanceof PdfIndirectObject) {
            $n = $this->objectMap[$this->currentReaderId][$value->objectNumber];
            $this->_newobj($n);
            $this->writePdfType($value->value);

            // add newline before "endobj" for all objects in view to PDF/A conformance
            if (
                !(
                    ($value->value instanceof PdfArray) ||
                    ($value->value instanceof PdfDictionary) ||
                    ($value->value instanceof PdfToken) ||
                    ($value->value instanceof PdfStream)
                )
            ) {
                $this->_put("\n", false);
            }

            $this->_put('endobj');
        }
    }
}

// ==========================================
// File: GraphicsState.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

use setasign\Fpdi\Math\Matrix;
use setasign\Fpdi\Math\Vector;

/**
 * A simple graphic state class which holds the current transformation matrix.
 */
class GraphicsState
{
    /**
     * @var Matrix
     */
    protected $ctm;

    /**
     * @param Matrix|null $ctm
     */
    public function __construct(?Matrix $ctm = null)
    {
        if ($ctm === null) {
            $ctm = new Matrix();
        }

        $this->ctm = $ctm;
    }

    /**
     * @param Matrix $matrix
     * @return $this
     */
    public function add(Matrix $matrix)
    {
        $this->ctm = $matrix->multiply($this->ctm);
        return $this;
    }

    /**
     * @param int|float $x
     * @param int|float $y
     * @param int|float $angle
     * @return $this
     */
    public function rotate($x, $y, $angle)
    {
        if (\abs($angle) < 1e-5) {
            return  $this;
        }

        $angle = \deg2rad($angle);
        $c = \cos($angle);
        $s = \sin($angle);

        $this->add(new Matrix($c, $s, -$s, $c, $x, $y));

        return $this->translate(-$x, -$y);
    }

    /**
     * @param int|float $shiftX
     * @param int|float $shiftY
     * @return $this
     */
    public function translate($shiftX, $shiftY)
    {
        return $this->add(new Matrix(1, 0, 0, 1, $shiftX, $shiftY));
    }

    /**
     * @param int|float $scaleX
     * @param int|float $scaleY
     * @return $this
     */
    public function scale($scaleX, $scaleY)
    {
        return $this->add(new Matrix($scaleX, 0, 0, $scaleY, 0, 0));
    }

    /**
     * @param Vector $vector
     * @return Vector
     */
    public function toUserSpace(Vector $vector)
    {
        return $vector->multiplyWithMatrix($this->ctm);
    }
}

// ==========================================
// File: Fpdi.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi\Tcpdf;

use setasign\Fpdi\FpdiException;
use setasign\Fpdi\FpdiTrait;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\AsciiHex;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfHexString;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNull;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * Class Fpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for TCPDF.
 *
 * @method _encrypt_data(int $n, string $s) string
 */
class Fpdi extends \TCPDF
{
    use FpdiTrait {
        writePdfType as fpdiWritePdfType;
        useImportedPage as fpdiUseImportedPage;
    }

    /**
     * FPDI version
     *
     * @string
     */
    const VERSION = '2.6.8';

    /**
     * A counter for template ids.
     *
     * @var int
     */
    protected $templateId = 0;

    /**
     * The currently used object number.
     *
     * @var int|null
     */
    protected $currentObjectNumber;

    protected function _enddoc()
    {
        parent::_enddoc();
        $this->cleanUp();
    }

    /**
     * Get the next template id.
     *
     * @return int
     */
    protected function getNextTemplateId()
    {
        return $this->templateId++;
    }

    /**
     * Draws an imported page onto the page or another template.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param float|int|array $x The abscissa of upper-left corner. Alternatively you could use an assoc array
     *                           with the keys "x", "y", "width", "height", "adjustPageSize".
     * @param float|int $y The ordinate of upper-left corner.
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @param bool $adjustPageSize
     * @return array The size
     * @see FpdiTrait::getTemplateSize()
     */
    public function useTemplate($tpl, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false)
    {
        return $this->useImportedPage($tpl, $x, $y, $width, $height, $adjustPageSize);
    }

    /**
     * Draws an imported page onto the page.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $pageId The page id
     * @param float|int|array $x The abscissa of upper-left corner. Alternatively you could use an assoc array
     *                           with the keys "x", "y", "width", "height", "adjustPageSize".
     * @param float|int $y The ordinate of upper-left corner.
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @param bool $adjustPageSize
     * @return array The size.
     * @see Fpdi::getTemplateSize()
     */
    public function useImportedPage($pageId, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false)
    {
        $size = $this->fpdiUseImportedPage($pageId, $x, $y, $width, $height, $adjustPageSize);
        if ($this->inxobj) {
            $importedPage = $this->importedPages[$pageId];
            $this->xobjects[$this->xobjid]['importedPages'][$importedPage['id']] = $pageId;
        }

        return $size;
    }

    /**
     * Get the size of an imported page.
     *
     * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
     * aspect ratio.
     *
     * @param mixed $tpl The template id
     * @param float|int|null $width The width.
     * @param float|int|null $height The height.
     * @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
     */
    public function getTemplateSize($tpl, $width = null, $height = null)
    {
        return $this->getImportedPageSize($tpl, $width, $height);
    }

    /**
     * @inheritdoc
     * @return string
     */
    protected function _getxobjectdict()
    {
        $out = parent::_getxobjectdict();

        foreach ($this->importedPages as $pageData) {
            $out .= '/' . $pageData['id'] . ' ' . $pageData['objectNumber'] . ' 0 R ';
        }

        return $out;
    }

    /**
     * @inheritdoc
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    protected function _putxobjects()
    {
        foreach ($this->importedPages as $key => $pageData) {
            $this->currentObjectNumber = $this->_newobj();
            $this->importedPages[$key]['objectNumber'] = $this->currentObjectNumber;
            $this->currentReaderId = $pageData['readerId'];
            $this->writePdfType($pageData['stream']);
            $this->_put('endobj');
        }

        foreach (\array_keys($this->readers) as $readerId) {
            $parser = $this->getPdfReader($readerId)->getParser();
            $this->currentReaderId = $readerId;

            while (($objectNumber = \array_pop($this->objectsToCopy[$readerId])) !== null) {
                try {
                    $object = $parser->getIndirectObject($objectNumber);
                } catch (CrossReferenceException $e) {
                    if ($e->getCode() === CrossReferenceException::OBJECT_NOT_FOUND) {
                        $object = PdfIndirectObject::create($objectNumber, 0, new PdfNull());
                    } else {
                        throw $e;
                    }
                }

                $this->writePdfType($object);
            }
        }

        // let's prepare resources for imported pages in templates
        foreach ($this->xobjects as $xObjectId => $data) {
            if (!isset($data['importedPages'])) {
                continue;
            }

            foreach ($data['importedPages'] as $id => $pageKey) {
                $page = $this->importedPages[$pageKey];
                $this->xobjects[$xObjectId]['xobjects'][$id] = ['n' => $page['objectNumber']];
            }
        }


        parent::_putxobjects();
        $this->currentObjectNumber = null;
    }

    /**
     * Append content to the buffer of TCPDF.
     *
     * @param string $s
     * @param bool $newLine
     */
    protected function _put($s, $newLine = true)
    {
        if ($newLine) {
            $this->setBuffer($s . "\n");
        } else {
            $this->setBuffer($s);
        }
    }

    /**
     * Begin a new object and return the object number.
     *
     * @param int|string $objid Object ID (leave empty to get a new ID).
     * @return int object number
     */
    protected function _newobj($objid = '')
    {
        $this->_out($this->_getobj($objid));
        return $this->n;
    }

    /**
     * Writes a PdfType object to the resulting buffer.
     *
     * @param PdfType $value
     * @throws PdfTypeException
     */
    protected function writePdfType(PdfType $value)
    {
        if (!$this->encrypted) {
            $this->fpdiWritePdfType($value);
            return;
        }

        if ($value instanceof PdfString) {
            $string = PdfString::unescape($value->value);
            $string = $this->_encrypt_data($this->currentObjectNumber, $string);
            $value->value = PdfString::escape($string);
        } elseif ($value instanceof PdfHexString) {
            $filter = new AsciiHex();
            $string = $filter->decode($value->value);
            $string = $this->_encrypt_data($this->currentObjectNumber, $string);
            $value->value = $filter->encode($string, true);
        } elseif ($value instanceof PdfStream) {
            $stream = $value->getStream();
            $stream = $this->_encrypt_data($this->currentObjectNumber, $stream);
            $dictionary = $value->value;
            $dictionary->value['Length'] = PdfNumeric::create(\strlen($stream));
            $value = PdfStream::create($dictionary, $stream);
        } elseif ($value instanceof PdfIndirectObject) {
            /**
             * @var PdfIndirectObject $value
             */
            $this->currentObjectNumber = $this->objectMap[$this->currentReaderId][$value->objectNumber];
        }

        $this->fpdiWritePdfType($value);
    }

    /**
     * This method will add additional data to the last created link/annotation.
     *
     * It will copy styling properties (supported by TCPDF) of the imported link.
     *
     * @param array $externalLink
     * @param float|int $xPt
     * @param float|int $scaleX
     * @param float|int $yPt
     * @param float|int $newHeightPt
     * @param float|int $scaleY
     * @param array $importedPage
     * @return void
     */
    protected function adjustLastLink($externalLink, $xPt, $scaleX, $yPt, $newHeightPt, $scaleY, $importedPage)
    {
        $parser = $this->getPdfReader($importedPage['readerId'])->getParser();

        if ($this->inxobj) {
            // store parameters for later use on template
            $lastAnnotationKey = \count($this->xobjects[$this->xobjid]['annotations']) - 1;
            $lastAnnotationOpt = &$this->xobjects[$this->xobjid]['annotations'][$lastAnnotationKey]['opt'];
        } else {
            $lastAnnotationKey = \count($this->PageAnnots[$this->page]) - 1;
            $lastAnnotationOpt = &$this->PageAnnots[$this->page][$lastAnnotationKey]['opt'];
        }

        // ensure we have a default value - otherwise TCPDF will set it to 4 throughout
        $lastAnnotationOpt['f'] = 0;

        // values in this dictonary are all direct objects and we don't need to resolve them here again.
        $values = $externalLink['pdfObject']->value;

        foreach ($values as $key => $value) {
            try {
                switch ($key) {
                    case 'BS':
                        $value = PdfDictionary::ensure($value);
                        $bs = [];
                        if (isset($value->value['W'])) {
                            $bs['w'] = PdfNumeric::ensure($value->value['W'])->value;
                        }

                        if (isset($value->value['S'])) {
                            $bs['s'] = PdfName::ensure($value->value['S'])->value;
                        }

                        if (isset($value->value['D'])) {
                            $d = [];
                            foreach (PdfArray::ensure($value->value['D'])->value as $item) {
                                $d[] = PdfNumeric::ensure($item)->value;
                            }
                            $bs['d'] = $d;
                        }

                        $lastAnnotationOpt['bs'] = $bs;
                        break;

                    case 'Border':
                        $borderArray = PdfArray::ensure($value)->value;
                        if (count($borderArray) < 3) {
                            continue 2;
                        }

                        $border = [
                            PdfNumeric::ensure($borderArray[0])->value,
                            PdfNumeric::ensure($borderArray[1])->value,
                            PdfNumeric::ensure($borderArray[2])->value,
                        ];
                        if (isset($borderArray[3])) {
                            $dashArray = [];
                            foreach (PdfArray::ensure($borderArray[3])->value as $item) {
                                $dashArray[] = PdfNumeric::ensure($item)->value;
                            }
                            $border[] = $dashArray;
                        }

                        $lastAnnotationOpt['border'] = $border;
                        break;

                    case 'C':
                        $c = [];
                        $colors = PdfArray::ensure(PdfType::resolve($value, $parser))->value;
                        $m = \count($colors) === 4 ? 100 : 255;
                        foreach ($colors as $item) {
                            $c[] = PdfNumeric::ensure($item)->value * $m;
                        }
                        $lastAnnotationOpt['c'] = $c;
                        break;

                    case 'F':
                        $lastAnnotationOpt['f'] = $value->value;
                        break;

                    case 'BE':
                        // is broken in current TCPDF version: "bc" key is checked but "bs" is used.
                        break;
                }
            // let's silence invalid/not supported values
            } catch (FpdiException $e) {
                continue;
            }
        }

        // QuadPoints are not supported by TCPDF
//        if (count($externalLink['quadPoints']) > 0) {
//            $quadPoints = [];
//            for ($i = 0, $n = count($externalLink['quadPoints']); $i < $n; $i += 2) {
//                $quadPoints[] = $xPt + $externalLink['quadPoints'][$i] * $scaleX;
//                $quadPoints[] = $this->hPt - $yPt - $newHeightPt + $externalLink['quadPoints'][$i + 1] * $scaleY;
//            }
//
//            ????? = $quadPoints;
//        }
    }
}

// ==========================================
// File: TcpdfFpdi.php
// ==========================================
/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2026 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 */

namespace setasign\Fpdi;

/**
 * Class TcpdfFpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for TCPDF.
 *
 * @deprecated Class was moved to \setasign\Fpdi\Tcpdf\Fpdi
 */
class TcpdfFpdi extends \setasign\Fpdi\Tcpdf\Fpdi
{
    // this class is moved to \setasign\Fpdi\Tcpdf\Fpdi
}
