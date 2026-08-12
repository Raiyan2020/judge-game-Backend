<?php

namespace App\Http\Requests\Admin\Concerns;

trait UsesAdminAttributes
{
    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('admin.attributes');
    }
}
