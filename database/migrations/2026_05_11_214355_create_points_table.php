<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement($this->dropView());
        DB::statement($this->createView());
    }

    private function dropView(): string
    {
        return <<<SQL
DROP VIEW IF EXISTS `points`;
SQL;
    }

//COALESCE used for condition if sum is null then set the value 0
    private function createView(): string
{
    return <<<SQL
CREATE VIEW `points` AS
SELECT 
    user_id,

    COALESCE(SUM(points), 0) as total_points,

    COALESCE(SUM(CASE WHEN role = 'judge' THEN points ELSE 0 END), 0) as judge_points,
    COALESCE(SUM(CASE WHEN role = 'lawyer' THEN points ELSE 0 END), 0) as lawyer_points,
    COALESCE(SUM(CASE WHEN role = 'consultant' THEN points ELSE 0 END), 0) as consultant_points,
    COALESCE(SUM(CASE WHEN role = 'citizen' THEN points ELSE 0 END), 0) as citizen_points

FROM `point_transactions`
GROUP BY user_id;
SQL;
}

    public function down()
    {
        DB::statement($this->dropView());
    }

};
