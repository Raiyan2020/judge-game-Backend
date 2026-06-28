<div class="row">

    <x-translatable
        title="{{ __('title') }}"
        name="title"
        size="6"
        :item="$roleTitle ?? null"
    />

    <x-select
        name="role"
        :items=" ['' => __('Select Role')] + $roles ?? []"
        title="{{ __('role') }}"
        size="6"
        :selected="[@$roleTitle->role]"
        :defaultOption="__('Select Role')"
        extraClass="RoleSelect"
    />

</div>

<div class="row mt-2">
    <div class="col-12">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>{{ __('Action') }}</th>
                <th>{{ __('Required Count') }}</th>
            </tr>
            </thead>

            <tbody id="actionsTableBody">

            @if(isset($roleTitle))

                @foreach($roleTitle->requirements as $requirement)

                    <tr>
                        <td>
                            {{ $requirement->action->title }}
                            <input type="hidden"
                                   name="actions[{{ $loop->index }}][role_action_id]"
                                   value="{{ $requirement->role_action_id }}">
                        </td>

                        <td>
                            <input type="number"
                                   class="form-control"
                                   name="actions[{{ $loop->index }}][required_count]"
                                   value="{{ old(
                                            'actions.'.$loop->index.'.required_count',
                                            $requirement->required_count
                                        ) }}">
                        </td>
                    </tr>

                @endforeach

            @endif

            </tbody>
        </table>
    </div>
</div>

<button type="submit"
        class="btn btn-success mr-1 mb-1 waves-effect waves-light">
    {{ __('save') }}
</button>

@push('scripts')

<script>

$('.RoleSelect').change(function () {

    let role = $(this).val();

    if (!role) {
        $('#actionsTableBody').html('');
        return;
    }

    $.get('/dashboard/roles-actions/' + role, function (actions) {

        let html = '';

        actions.forEach(function(action, index) {

            let title =  action.title.ar ?? action.title.en;

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
                               min="1"
                               name="actions[${index}][required_count]"
                               value="0">
                    </td>
                </tr>
            `;
        });

        $('#actionsTableBody').html(html);

    });

});

</script>

@endpush