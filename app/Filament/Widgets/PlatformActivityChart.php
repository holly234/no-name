<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use App\Models\Message;
use Filament\Widgets\ChartWidget;

class PlatformActivityChart extends ChartWidget
{
    protected ?string $heading = 'Platform activity — last 7 days';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn (int $daysAgo) => today()->subDays($daysAgo));

        return [
            'datasets' => [
                [
                    'label' => 'Messages',
                    'data' => $days->map(fn ($day) => Message::query()->whereDate('created_at', $day)->count())->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.16)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'New conversations',
                    'data' => $days->map(fn ($day) => Conversation::query()->whereDate('created_at', $day)->count())->all(),
                    'borderColor' => '#38bdf8',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.12)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('D, M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
