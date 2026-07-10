<?php

namespace Tests\Unit;

use App\Support\Statistics;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase
{
    public function test_median_returns_null_for_empty_list(): void
    {
        $this->assertNull(Statistics::median([]));
    }

    public function test_median_of_single_value(): void
    {
        $this->assertSame(5.0, Statistics::median([5]));
    }

    public function test_median_of_odd_count_is_the_middle_value(): void
    {
        $this->assertSame(3.0, Statistics::median([1, 3, 100]));
    }

    public function test_median_of_even_count_averages_the_two_middle_values(): void
    {
        // The Monday chart shows a 3.5 point — even counts must average, not floor.
        $this->assertSame(3.5, Statistics::median([4, 3]));
        $this->assertSame(2.5, Statistics::median([1, 2, 3, 4]));
    }

    public function test_median_is_unaffected_by_input_order_and_handles_negatives(): void
    {
        $this->assertSame(0.0, Statistics::median([-5, 0, 5]));
        $this->assertSame(2.0, Statistics::median([10, 2, 1, 2, 3])); // sorted: 1,2,2,3,10
    }

    public function test_monthly_series_aligns_one_value_per_windowed_month_with_null_gaps(): void
    {
        $windowedPoints = [
            ['month' => '2025-01', 'label' => 'Jan 2025'],
            ['month' => '2025-02', 'label' => 'Feb 2025'],
            ['month' => '2025-03', 'label' => 'Mar 2025'],
        ];
        $valuesByMonth = [
            '2025-01' => [2, 4],   // median 3
            // 2025-02 has no data -> null gap
            '2025-03' => [5, 5, 5], // median 5
        ];

        $series = Statistics::medianSeries($windowedPoints, $valuesByMonth, 'monthly');

        $this->assertCount(3, $series);
        $this->assertSame([3.0, null, 5.0], $series);
    }

    public function test_quarterly_series_pools_raw_values_per_quarter_not_median_of_medians(): void
    {
        $windowedPoints = [
            ['month' => '2025-01', 'label' => 'Jan 2025'],
            ['month' => '2025-02', 'label' => 'Feb 2025'],
            ['month' => '2025-03', 'label' => 'Mar 2025'], // all Q1 2025
            ['month' => '2025-04', 'label' => 'Apr 2025'], // Q2 2025
        ];
        $valuesByMonth = [
            '2025-01' => [1, 1],
            '2025-02' => [3],
            '2025-03' => [9],
            '2025-04' => [4, 6],
        ];

        $series = Statistics::medianSeries($windowedPoints, $valuesByMonth, 'quarterly');

        // Q1 pooled = [1,1,3,9] -> sorted median of 1,1,3,9 = (1+3)/2 = 2.0
        // Q2 pooled = [4,6] -> 5.0
        $this->assertCount(2, $series);
        $this->assertSame([2.0, 5.0], $series);
    }

    public function test_quarter_with_no_values_yields_null(): void
    {
        $windowedPoints = [
            ['month' => '2024-07', 'label' => 'Jul 2024'], // Q3 2024, no data
            ['month' => '2024-10', 'label' => 'Oct 2024'], // Q4 2024
        ];
        $valuesByMonth = [
            '2024-10' => [8],
        ];

        $series = Statistics::medianSeries($windowedPoints, $valuesByMonth, 'quarterly');

        $this->assertSame([null, 8.0], $series);
    }

    public function test_empty_windowed_points_yields_empty_series(): void
    {
        $this->assertSame([], Statistics::medianSeries([], [], 'monthly'));
        $this->assertSame([], Statistics::medianSeries([], [], 'quarterly'));
    }
}
