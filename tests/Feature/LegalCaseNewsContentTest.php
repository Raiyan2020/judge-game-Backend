<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\LegalCaseNews;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins the viewer-aware rendering of a `case_created` news row (M-04): the row
 * stores `actor_id` = the filer and `subject_id` = the defendant, and the
 * sentence must read from the READER's side. It previously appended the ACTOR
 * after "against", so the person who FILED the case read that a case had been
 * filed against them.
 *
 * NOTE: `vendor/` is absent in this checkout, so this suite is UNEXECUTED here;
 * it is written to run under the project's normal phpunit once deps install.
 */
class LegalCaseNewsContentTest extends TestCase
{
    use RefreshDatabase;

    private function newsRow(User $filer, User $defendant, Group $group): LegalCaseNews
    {
        return LegalCaseNews::create([
            'type' => 'case_created',
            'content' => ['ar' => 'تم إنشاء القضية', 'en' => 'Legal case created'],
            'group_id' => $group->id,
            'legal_case_id' => null,
            'actor_id' => $filer->id,
            'subject_id' => $defendant->id,
        ]);
    }

    public function test_case_created_reads_from_each_viewers_side(): void
    {
        app()->setLocale('ar');

        $filer = User::factory()->create(['name' => 'سالم']);
        $defendant = User::factory()->create(['name' => 'ناصر']);
        $bystander = User::factory()->create(['name' => 'خالد']);

        $group = Group::create(['name' => 'News Group', 'user_id' => $filer->id]);
        $news = $this->newsRow($filer, $defendant, $group);

        // The filer filed it — never "against you".
        $this->assertSame('رفعت قضية ضد ناصر', $news->generateContent($filer));

        // The defendant is the target.
        $this->assertSame('رُفعت قضية ضدك من سالم', $news->generateContent($defendant));

        // Everyone else reads the neutral third person, with the REAL defendant
        // named (the old sentence named the filer on both sides).
        $this->assertSame('رفع سالم قضية ضد ناصر', $news->generateContent($bystander));

        // No viewer (e.g. an unauthenticated render) also stays neutral.
        $this->assertSame('رفع سالم قضية ضد ناصر', $news->generateContent());
    }

    public function test_case_created_falls_back_when_the_subject_is_missing(): void
    {
        app()->setLocale('ar');

        $filer = User::factory()->create(['name' => 'سالم']);
        $group = Group::create(['name' => 'News Group', 'user_id' => $filer->id]);

        $news = LegalCaseNews::create([
            'type' => 'case_created',
            'content' => ['ar' => 'تم إنشاء القضية', 'en' => 'Legal case created'],
            'group_id' => $group->id,
            'legal_case_id' => null,
            'actor_id' => $filer->id,
            'subject_id' => null,
        ]);

        // Never a dangling «ضد » with no name after it.
        $this->assertSame('رفعت قضية', $news->generateContent($filer));
        $this->assertSame('رفع سالم قضية', $news->generateContent());
    }

    public function test_subject_relation_resolves_a_user(): void
    {
        $filer = User::factory()->create();
        $defendant = User::factory()->create();
        $group = Group::create(['name' => 'News Group', 'user_id' => $filer->id]);

        $news = $this->newsRow($filer, $defendant, $group)->fresh();

        // `subject_id` stores a USER id (the table's own FK points at users), so
        // the relation must resolve a User — it used to point at LegalCase.
        $this->assertInstanceOf(User::class, $news->subject);
        $this->assertSame($defendant->id, $news->subject->id);
    }
}
