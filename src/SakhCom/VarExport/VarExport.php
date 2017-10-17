<?php

namespace SakhCom\VarExport;

/**
 * Class VarExport
 *
 * @package SakhCom\VarExport
 */
class VarExport
{

    private $variable;

    /**
     * VarExport constructor.
     *
     * @param mixed $variable
     */
    public function __construct($variable)
    {
        $this->variable = $variable;
    }

    /**
     * Export the variable
     *
     * @param int $maxDepth maximum depth
     * @return string
     */
    public function export($maxDepth = -1)
    {
        return $this->exportRef($this->variable, $maxDepth === -1 ? -1 : $maxDepth + 1);
    }

    /**
     * @param mixed $ref
     * @param int $maxDepth maximum depth
     * @param int $indent
     * @return string
     */
    private function exportRef($ref, $maxDepth, $indent = 0)
    {
        if ($maxDepth !== -1) {
            $maxDepth--;
        }

        switch (gettype($ref)) {
            case 'boolean':
                return $ref ? 'true' : 'false';
            case 'integer':
            case 'double':
                return $ref;
            case 'string':
                return "'" . $this->escapeString($ref) . "'";
            case 'resource':
            case 'NULL':
                return 'NULL';
            case 'array':
                $output = '';
                $baseIndentString = str_repeat(' ', $indent * 2);
                $indentString = str_repeat(' ', ($indent + 1) * 2);
                if ($indent > 0) {
                    $output .= PHP_EOL . $baseIndentString;
                }
                $output .= 'array (';
                if ($maxDepth === -1 || $maxDepth > 0) {
                    $output .= PHP_EOL;
                    foreach ($ref as $key => $value) {
                        $output .= $indentString . $this->exportElement($key, $value, $maxDepth, $indent);
                    }
                    $output .= $baseIndentString . ')';
                } else {
                    $output .= '...)';
                }
                return $output;
            case 'object':
                $output = '';
                $baseIndentString = str_repeat(' ', $indent * 2);
                $indentString = str_repeat(' ', ($indent + 1) * 2 + 1);
                if ($indent > 0) {
                    $output .= PHP_EOL . $baseIndentString;
                }
                $output .= get_class($ref) . '::__set_state(array(';
                if ($maxDepth === -1 || $maxDepth > 0) {
                    $output .= PHP_EOL;
                    $reflection = new \ReflectionClass($ref);
                    foreach (get_object_vars($ref) as $key => $value) {
                        $output .= $indentString . $this->exportElement($key, $value, $maxDepth, $indent);
                    }

                    foreach ($reflection->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED) as $property) {
                        $key = $property->getName();
                        $property->setAccessible(true);
                        $value = $property->getValue($ref);
                        $output .= $indentString . $this->exportElement($key, $value, $maxDepth, $indent);
                        $property->setAccessible(false);
                    }

                    $output .= $baseIndentString . '))';
                } else {
                    $output .= '...))';
                }
                return $output;
            default:
                return '{unknown}';
        }
    }

    /**
     * @param $key
     * @param $value
     * @param $maxDepth
     * @param $indent
     * @return string
     */
    private function exportElement($key, $value, $maxDepth, $indent)
    {
        return $this->exportRef($key, $maxDepth, $indent + 1) .
            ' => ' .
            $this->exportRef($value, $maxDepth, $indent + 1) .
            ',' .
            PHP_EOL;
    }

    /**
     * @param string $string
     * @return string
     */
    private function escapeString($string)
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = preg_replace("#(?!=\\\)'#", '\\\'', $string);
        return $string;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->export();
    }
}
