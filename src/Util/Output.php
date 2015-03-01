<?php

namespace League\CLImate\Util;

use League\CLImate\Util\Writer\WriterInterface;

class Output
{
    /**
     * The content to be output
     *
     * @var string $content
     */
    protected $content;

    /**
     * Whether or not to add a new line after the output
     *
     * @var boolean $new_line
     */
    protected $new_line = true;

    /**
     * The array of available writers
     *
     * @var array[] $writers
     */
    protected $writers = [];

    /**
     * Default writers when one isn't specifed
     *
     * @var WriterInterface[] $default
     */
    protected $default = [];

    /**
     * Writers to be used just once
     *
     * @var null|array $once
     */
    protected $once;

    public function __construct()
    {
        $this->add('out', new Writer\StdOut);
        $this->add('error', new Writer\StdErr);
        $this->add('buffer', new Writer\Buffer);

        $this->defaultTo('out');
    }

    /**
     * Dictate that a new line should not be added after the output
     */
    public function sameLine()
    {
        $this->new_line = false;

        return $this;
    }

    /**
     * Add a writer to the available writers
     *
     * @param string $key
     * @param WriterInterface|array $writer
     *
     * @return \League\CLImate\Util\Output
     */
    public function add($key, $writer)
    {
        $this->writers[$key] = $this->resolve(Helper::toArray($writer));

        return $this;
    }

    /**
     * Set the default writer
     *
     * @param string|array $keys
     */
    public function defaultTo($keys)
    {
        $this->default = $this->getWriters($keys);
    }

    /**
     * Add a default writer
     *
     * @param string|array $keys
     */
    public function addDefault($keys)
    {
        $this->default = array_merge($this->default, $this->getWriters($keys));
    }

    /**
     * Register a writer to be used just once
     *
     * @param string|array $keys
     *
     * @return \League\CLImate\Util\Output
     */
    public function once($keys)
    {
        $this->once = $this->getWriters($keys);

        return $this;
    }

    /**
     * Get the currently available writers
     *
     * @return array
     */
    public function getAvailable()
    {
        $writers = [];

        foreach ($this->writers as $key => $writer) {
            $writers[$key] = $this->getReadable($writer);
        }

        return $writers;
    }

    /**
     * Resolve the writer(s) down to an array of WriterInterface classes
     *
     * @throws Exception If passing a non-valid writer
     * @param WriterInterface|array|string $writer
     *
     * @return array
     */
    protected function resolve($writer)
    {
        if (is_array($writer)) {
            return Helper::flatten(array_map([$this, 'resolve'], $writer));
        }

        if ($writer instanceof WriterInterface) {
            return $writer;
        }

        if (is_string($writer) && array_key_exists($writer, $this->writers)) {
            return $this->writers[$writer];
        }

        // If we've gotten this far and don't know what it is,
        // let's at least try and give a helpful error message
        if (is_object($writer)) {
            throw new \Exception('Class [' . get_class($writer) . '] must implement '
                                    . 'League\CLImate\Util\Writer\WriterInterface.');
        }

        // No idea, just tell them we can't resolve it
        throw new \Exception('Unable to resolve writer [' . $writer . ']');
    }

    /**
     * Get the readable version of the writer(s)
     *
     * @param array $writer
     *
     * @return string|array
     */
    protected function getReadable(array $writer)
    {
        $classes = array_map('get_class', $writer);

        if (count($classes) == 1) {
            return reset($classes);
        }

        return $classes;
    }

    /**
     * Get the writers based on their keys
     *
     * @param string|array $keys
     *
     * @return array
     */
    protected function getWriters($keys)
    {
        return array_intersect_key($this->writers, array_flip(Helper::toArray($keys)));
    }

    /**
     * Write the content using the provided writer
     *
     * @param  string $content
     */
    public function write($content)
    {
        if ($this->new_line) {
            $content .= PHP_EOL;
        }

        $writers = $this->once ?: $this->default;

        foreach ($writers as $writer) {
            foreach ($writer as $write) {
                $write->write($content);
            }
        }

        // Reset new line flag for next time
        $this->new_line = true;

        // Reset once since we only want to use it... once.
        $this->once = null;
    }

}
