<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['role', 'key', 'title', 'points'])]

class RoleAction extends Model
{
    use HasTranslations;

    public const MAX_POINTS = 999999999;

    /**
     * Citizen actions applied when a lawsuit is filed against the user
     * or a judgment is executed against them.
     *
     * @var list<string>
     */
    public const CITIZEN_AGAINST_KEYS = [
        'lawsuit_filed_against',
        'judgment_executed_against',
    ];

    public $translatable = ['title'];

    public function localizedTitle(): string
    {
        return (string) $this->getTranslation('title', app()->getLocale());
    }

    public function isCitizenAgainstAction(): bool
    {
        return $this->role === 'citizen'
            && in_array($this->key, self::CITIZEN_AGAINST_KEYS, true);
    }
 
    
}
