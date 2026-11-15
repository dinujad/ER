<?php

namespace App\Charts;

class CommonChart
{
    protected array $labels  = [];
    protected string $title  = '';
    protected array $datasets = [];

    public function labels(array $labels): static
    {
        $this->labels = $labels;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function dataset(string $name, string $type, array $values): static
    {
        $this->datasets[] = compact('name', 'type', 'values');
        return $this;
    }

    public function container(): string
    {
        $id = 'chart_' . uniqid();
        return '<canvas id="' . $id . '"></canvas>';
    }

    public function script(): string
    {
        return '';
    }
}
