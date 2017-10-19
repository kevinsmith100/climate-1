<?php

namespace League\CLImate\TerminalObject\Dynamic;

class Spinner extends DynamicTerminalObject
{
    /**
     * The characters to be used to present progress.
     *
     * @var string $characters
     */
    private $characters = ["[=---]", "[-=--]", "[--=-]", "[---=]", "[--=-]", "[-=--]"];

    /**
     * The current item of the sequence
     *
     * @var integer $current
     */
    private $current = 0;

    /**
     * Flag indicating whether we are writing the bar for the first time
     *
     * @var boolean $first_line
     */
    private $first_line = true;

    /**
     * Current label
     *
     * @var string $label
     */
    private $label;

    /**
     * When the spinner was last drawn.
     *
     * @var float $last_drawn
     */
    private $last_drawn;

    /**
     * How long to wait in seconds between drawing each stage.
     *
     * @var float $time_limit
     */
    private $time_limit = 0.1;


    /**
     * If they pass in a sequence, set the sequence
     *
     * @param string $label
     * @param string[] $characters
     */
    public function __construct($label = null, ...$characters)
    {
        if ($label !== null) {
            $this->label = $label;
        }

        if (count($characters) < 1) {
            $characters = [];
            $size = 5;
            $positions = array_merge(range(0, $size - 1), range($size - 2, 1, -1));
            foreach ($positions as $pos) {
                $line = str_repeat("-", $size);
                $characters[] = "[" . substr($line, 0, $pos) . "=" . substr($line, $pos + 1) . "]";
            }
        }
        $this->characters(...$characters);
    }


    /**
     * Set the length of time to wait between drawing each stage.
     *
     * @param  float $time_limit
     *
     * @return Spinner
     */
    public function timeLimit($time_limit)
    {
        $this->time_limit = (float) $time_limit;

        return $this;
    }


    /**
     * Set the character to loop around.
     *
     * @param  string $characters
     *
     * @return Spinner
     */
    public function characters(...$characters)
    {
        if (count($characters) < 1) {
            throw new \UnexpectedValueException("You must specify the characters to use");
        }

        $this->characters = $characters;

        return $this;
    }


    /**
     * Re-writes the spinner
     *
     * @param string $label
     *
     * @return void
     */
    public function advance($label = null)
    {
        if ($label === null) {
            $label = $this->label;
        }

        if ($this->last_drawn) {
            $time = microtime(true) - $this->last_drawn;
            if ($time < $this->time_limit) {
                return;
            }
        }

        ++$this->current;
        if ($this->current >= count($this->characters)) {
            $this->current = 0;
        }

        $characters = $this->characters[$this->current];
        $this->drawSpinner($characters, $label);
        $this->last_drawn = microtime(true);
    }


    /**
     * Draw the spinner
     *
     * @param string $characters
     * @param string $label
     */
    private function drawSpinner($characters, $label)
    {
        $spinner = "";

        if ($this->first_line) {
            $this->first_line = false;
        } else {
            $spinner .= $this->util->cursor->up(1);
            $spinner .= $this->util->cursor->startOfCurrentLine();
            $spinner .= $this->util->cursor->deleteCurrentLine();
        }

        $spinner .= trim("{$characters} {$label}");

        $this->output->write($this->parser->apply($spinner));
    }
}
