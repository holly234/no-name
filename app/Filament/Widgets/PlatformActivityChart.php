<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use App\Models\Message;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformActivityChart extends ChartWidget
{
    protected ?string $heading = 'Platform activity - last 7 days';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        return Cache::remember('owner-panel:platform-activity-chart', now()->addSeconds(60), function (): array {
            $days = collect(range(6, 0))->map(fn (int $daysAgo) => today()->subDays($daysAgo));
            $start = $days->first()->copy()->startOfDay();
            $end = today()->addDay()->startOfDay();

            $messageCounts = $this->dailyCounts(Message::class, $start, $end);
            $conversationCounts = $this->dailyCounts(Conversation::class, $start, $end);

            return [
                'datasets' => [
                    [
                        'label' => 'Messages',
                        'data' => $days->map(fn ($day) => $messageCounts[$day->toDateString()] ?? 0)->all(),
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.16)',
                        'fill' => true,
                        'tension' => 0.35,
                    ],
                    [
                        'label' => 'New conversations',
                        'data' => $days->map(fn ($day) => $conversationCounts[$day->toDateString()] ?? 0)->all(),
                        'borderColor' => '#38bdf8',
                        'backgroundColor' => 'rgba(56, 189, 248, 0.12)',
                        'tension' => 0.35,
                    ],
                ],
                'labels' => $days->map(fn ($day) => $day->format('D, M j'))->all(),
            ];
        });
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, int>
     */
    private function dailyCounts(string $model, mixed $start, mixed $end): array
    {
        return $model::query()
            ->selectRaw('DATE(created_at) as activity_date')
            ->selectRaw('COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'activity_date')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    protected function getType(): string
    {
        return 'line';
    }
}
