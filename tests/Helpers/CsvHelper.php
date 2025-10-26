<?php

namespace Tests\Helpers;

use Illuminate\Http\UploadedFile;
use Faker\Factory as Faker;

class CsvHelper
{
    /**
     * Generate a fake CSV file with optional duplicates.
     *
     * @param int $rows
     * @param float $duplicateRate 0.0 to 1.0
     * @return UploadedFile
     */
    public static function generateCsv(int $rows = 1000, float $duplicateRate = 0.05): UploadedFile
    {
        $faker = Faker::create();
        $content = "company_name,email,phone_number\n";

        $records = [];

        for ($i = 0; $i < $rows; $i++) {
            $company = $faker->company;
            $email = $faker->unique()->safeEmail;
            $phone = $faker->numerify('+977-98########');

            $records[] = [$company, $email, $phone];
        }

        $dupCount = (int) ($rows * $duplicateRate);
        for ($i = 0; $i < $dupCount; $i++) {
            $records[] = $records[array_rand($records)];
        }

        foreach ($records as $row) {
            $content .= implode(',', $row) . "\n";
        }

        return UploadedFile::fake()->createWithContent('clients.csv', $content);
    }
}
