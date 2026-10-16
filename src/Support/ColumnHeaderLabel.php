<?php

namespace Alareqi\FilamentTranslatableFields\Support;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Stringable;

class ColumnHeaderLabel implements Htmlable, Stringable
{
    public function __construct(protected string|Htmlable $label, protected View $view) {}

    public function toHtml(): string
    {
        return $this->view->render();
    }

    public function __toString(): string
    {
        return trim(strip_tags($this->label instanceof Htmlable ? $this->label->toHtml() : $this->label));
    }
}
