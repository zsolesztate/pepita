<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('appointments')->insert([
            [
                'start_time' => '2023-09-08',
                'end_time' => '2023-09-08',
                'recurrence_type' => 'none',
                'day_of_week' => 'none',
                'time_within_day' => '08:00-10:00',
            ],
            [
                'start_time' => '2023-01-01',
                'end_time' => NULL,
                'recurrence_type' => 'even_weeks',
                'day_of_week' => 'Monday', 
                'time_within_day' => '10:00-12:00',
            ],
            [
                'start_time' => '2023-01-01',
                'end_time' => NULL,
                'recurrence_type' => 'odd_weeks',
                'day_of_week' => 'Wednesday',
                'time_within_day' => '12:00-16:00',
            ],
            [
                'start_time' => '2023-01-01',
                'end_time' => NULL,
                'recurrence_type' => 'weekly',
                'day_of_week' => 'Friday',
                'time_within_day' => '10:00-16:00',
            ],
            [
                'start_time' => '2023-06-01',
                'end_time' => '2023-11-30',
                'recurrence_type' => 'weekly',
                'day_of_week' => 'Thursday',
                'time_within_day' => '16:00-20:00',
            ],
        ]);
    }
}
