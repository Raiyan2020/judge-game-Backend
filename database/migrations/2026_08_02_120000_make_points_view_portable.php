<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement($this->viewSql('INVOKER'));
    }

    public function down(): void
    {
        DB::statement($this->viewSql('DEFINER'));
    }

    private function viewSql(string $security): string
    {
        return <<<SQL
CREATE OR REPLACE SQL SECURITY {$security} VIEW `points` AS
SELECT
    user_id,
    COALESCE(SUM(points), 0) AS total_points,
    COALESCE(SUM(CASE WHEN role = 'judge' THEN points ELSE 0 END), 0) AS judge_points,
    COALESCE(SUM(CASE WHEN role = 'lawyer' THEN points ELSE 0 END), 0) AS lawyer_points,
    COALESCE(SUM(CASE WHEN role = 'consultant' THEN points ELSE 0 END), 0) AS consultant_points,
    COALESCE(SUM(CASE WHEN role = 'citizen' THEN points ELSE 0 END), 0) AS citizen_points
FROM `point_transactions`
GROUP BY user_id
SQL;
    }
};
