<?php

declare(strict_types=1);

namespace Nektria\Util\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class OutputFormatterStyle implements OutputFormatterStyleInterface
{
    private string $background;

    private Color $color;

    private string $foreground;

    private bool $handlesHrefGracefully;

    private string $href;

    /** @var string[] */
    private array $options;

    /**
     * @param string[] $options
     */
    public function __construct(?string $foreground = null, ?string $background = null, array $options = [])
    {
        $this->handlesHrefGracefully = false;
        $this->foreground = $foreground ?? '';
        $this->options = $options;
        $this->background = $background ?? '';
        $this->href = '';
        $this->color = new Color($foreground ?? '', $background ?? '', $options);
    }

    public function apply(string $text): string
    {
        if ($this->handlesHrefGracefully) {
            $text = "\033]8;;$this->href\033\\$text\033]8;;\033\\";
        }

        return $this->color->apply($text);
    }

    public function setBackground(?string $color = null): void
    {
        $this->background = $color ?? '';
        $this->color = new Color($this->foreground, $this->background, $this->options);
    }

    public function setForeground(?string $color = null): void
    {
        $this->foreground = $color ?? '';
        $this->color = new Color($this->foreground, $this->background, $this->options);
    }

    public function setHref(string $url): void
    {
        $this->href = $url;
    }

    public function setOption(string $option): void
    {
        $this->options[] = $option;
        $this->color = new Color($this->foreground, $this->background, $this->options);
    }

    /**
     * @param mixed[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
        $this->color = new Color($this->foreground, $this->background, $options);
    }

    public function unsetOption(string $option): void
    {
        $pos = array_search($option, $this->options, true);
        if (false !== $pos) {
            unset($this->options[$pos]);
        }

        $this->color = new Color($this->foreground, $this->background, $this->options);
    }
}
