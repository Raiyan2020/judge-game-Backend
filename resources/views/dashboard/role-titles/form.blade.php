<div class="admin-form-page">
    <div class="row match-height">
        <x-admin-form-section :title="__('basic information')" icon="icon-bookmark" col="col-lg-6">
            <x-translatable
                title="{{ __('title') }}"
                name="title"
                size="12"
                :item="isset($roleTitle) ? $roleTitle : null"
            />
            <x-number
                title="{{ __('tier') }}"
                name="tier"
                size="6"
                step="1"
                min="1"
                max="255"
                value="{{ old('tier', $roleTitle->tier ?? '') }}"
            ></x-number>
            <x-number
                title="{{ __('points') }}"
                name="reward_points"
                size="6"
                step="1"
                min="0"
                max="{{ \App\Models\RoleAction::MAX_POINTS }}"
                value="{{ old('reward_points', isset($roleTitle) ? (int) $roleTitle->reward_points : '') }}"
            ></x-number>
        </x-admin-form-section>

        <x-admin-form-section :title="__('role')" icon="icon-users" col="col-lg-6">
            <x-select
                name="role"
                :items="['' => __('Select Role')] + ($roles ?? [])"
                title="{{ __('role') }}"
                size="12"
                :selected="[old('role', $roleTitle->role ?? '')]"
                :defaultOption="__('Select Role')"
                extraClass="RoleSelect"
            />
            @error('role')
                <div class="col-12"><span style="color: red">{{ $message }}</span></div>
            @enderror
        </x-admin-form-section>

        <x-admin-form-section :title="__('required actions')" icon="icon-list" col="col-12">
            <div class="col-12">
                @error('actions')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('Required Count') }}</th>
                            </tr>
                        </thead>
                        <tbody id="actionsTableBody">
                            @if (old('actions') && ! isset($roleTitle))
                                @foreach (old('actions') as $index => $action)
                                    <tr>
                                        <td>
                                            <span class="action-title-placeholder" data-action-id="{{ $action['role_action_id'] ?? '' }}">-</span>
                                            <input type="hidden"
                                                name="actions[{{ $index }}][role_action_id]"
                                                value="{{ $action['role_action_id'] ?? '' }}">
                                        </td>
                                        <td>
                                            <input type="number"
                                                class="form-control"
                                                name="actions[{{ $index }}][required_count]"
                                                value="{{ (int) ($action['required_count'] ?? 0) }}"
                                                min="0"
                                                max="{{ \App\Models\RoleAction::MAX_POINTS }}"
                                                step="1">
                                            @error('actions.'.$index.'.required_count')
                                                <span style="color: red">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            @elseif (isset($roleTitle) && $roleTitle->requirements->isNotEmpty())
                                @foreach ($roleTitle->requirements as $requirement)
                                    <tr>
                                        <td>
                                            {{ $requirement->action?->localizedTitle() ?? '-' }}
                                            <input type="hidden"
                                                name="actions[{{ $loop->index }}][role_action_id]"
                                                value="{{ $requirement->role_action_id }}">
                                        </td>
                                        <td>
                                            <input type="number"
                                                class="form-control"
                                                name="actions[{{ $loop->index }}][required_count]"
                                                value="{{ old('actions.'.$loop->index.'.required_count', (int) $requirement->required_count) }}"
                                                min="0"
                                                max="{{ \App\Models\RoleAction::MAX_POINTS }}"
                                                step="1">
                                            @error('actions.'.$loop->index.'.required_count')
                                                <span style="color: red">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="actionsPlaceholderRow">
                                    <td colspan="2" class="text-center text-muted">
                                        {{ __('select role to load actions') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </x-admin-form-section>
    </div>

    <div class="admin-form-page__actions">
        <button type="submit" class="btn btn-success waves-effect waves-light">{{ __('save') }}</button>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const placeholderHtml = `
        <tr id="actionsPlaceholderRow">
            <td colspan="2" class="text-center text-muted">{{ __('select role to load actions') }}</td>
        </tr>
    `;

    function renderActions(actions, oldCounts) {
        oldCounts = oldCounts || {};

        if (!actions.length) {
            $('#actionsTableBody').html(`
                <tr>
                    <td colspan="2" class="text-center text-muted">{{ __('No data found') }}</td>
                </tr>
            `);
            return;
        }

        let html = '';

        actions.forEach(function (action, index) {
            const title = action.title?.ar || action.title?.en || '-';
            const count = Object.prototype.hasOwnProperty.call(oldCounts, action.id)
                ? oldCounts[action.id]
                : 0;

            html += `
                <tr>
                    <td>
                        ${title}
                        <input type="hidden"
                               name="actions[${index}][role_action_id]"
                               value="${action.id}">
                    </td>
                    <td>
                        <input type="number"
                               class="form-control"
                               min="0"
                               max="{{ \App\Models\RoleAction::MAX_POINTS }}"
                               step="1"
                               name="actions[${index}][required_count]"
                               value="${count}">
                    </td>
                </tr>
            `;
        });

        $('#actionsTableBody').html(html);
    }

    function loadActions(role, oldCounts) {
        if (!role) {
            $('#actionsTableBody').html(placeholderHtml);
            return;
        }

        $.get('/dashboard/roles-actions/' + role, function (actions) {
            renderActions(actions, oldCounts);
        });
    }

    $('.RoleSelect').on('change', function () {
        loadActions($(this).val());
    });

    @php
        $oldActionCounts = collect(old('actions', []))
            ->filter(fn ($action) => isset($action['role_action_id']))
            ->mapWithKeys(fn ($action) => [(int) $action['role_action_id'] => (int) ($action['required_count'] ?? 0)])
            ->all();
    @endphp

    @if (old('role') && ! isset($roleTitle))
        loadActions(@json(old('role')), @json($oldActionCounts));
    @elseif (isset($roleTitle) && $roleTitle->requirements->isEmpty() && old('role', $roleTitle->role))
        loadActions(@json(old('role', $roleTitle->role)));
    @endif
})();
</script>
@endpush
